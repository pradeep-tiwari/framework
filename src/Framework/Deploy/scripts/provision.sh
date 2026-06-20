#!/bin/bash

################################################################################
# Lightpack Server Provisioning Script
# Target: Ubuntu 22.04 LTS / 24.04 LTS
#
# This script is executed as root on a fresh server to prepare it for
# Lightpack application deployment. It:
#   - Creates a deploy user with restricted privileges
#   - Installs PHP (CLI), FrankenPHP, MySQL, Composer
#   - Configures FrankenPHP with Caddy
#   - Hardens SSH and firewall
#   - Generates secure credentials
#
# Security model:
#   - Root SSH is DISABLED after provisioning
#   - Deploy user has limited passwordless sudo (reload services only)
#   - Deploy user CANNOT install packages or run arbitrary commands as root
################################################################################

set -euo pipefail

# -----------------------------------------------------------------------------
# Configuration (override via environment variables)
# -----------------------------------------------------------------------------
SERVER_NAME="${SERVER_NAME:-lightpack}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
PHP_VERSION="${PHP_VERSION:-8.3}"
TIMEZONE="${TIMEZONE:-UTC}"
DB_TYPE="${DB_TYPE:-mysql}"
WEB_SERVER="${WEB_SERVER:-frankenphp}"
GIT_HOST="${GIT_HOST:-github.com}"

MYSQL_DB="${MYSQL_DB:-lightpack}"
MYSQL_USER="${MYSQL_USER:-lightpack}"

# -----------------------------------------------------------------------------
# Colors for output
# -----------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info()    { echo -e "${GREEN}[INFO]${NC}  $1"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC}  $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; }
log_step()    { echo -e "${BLUE}[STEP]${NC}  $1"; }

# -----------------------------------------------------------------------------
# Error trap: cleanup on failure
# -----------------------------------------------------------------------------
CLEANUP_NEEDED=false
trap 'if [ "$CLEANUP_NEEDED" = true ]; then log_error "Provisioning failed. Check /var/log/lightpack-provision.log"; fi' ERR

exec > >(tee -a /var/log/lightpack-provision.log) 2>&1

# -----------------------------------------------------------------------------
# 0. Pre-flight checks
# -----------------------------------------------------------------------------
log_step "Running pre-flight checks..."

if [ "$EUID" -ne 0 ]; then
    log_error "This script must be run as root"
    exit 1
fi

if ! command -v lsb_release &>/dev/null; then
    apt-get update -qq
    apt-get install -y -qq lsb-release
fi

LSB_RELEASE=$(lsb_release -s -c)
SUPPORTED_CODENAMES="jammy noble"

if ! echo "$SUPPORTED_CODENAMES" | grep -qw "$LSB_RELEASE"; then
    log_error "Unsupported Ubuntu version: $LSB_RELEASE"
    log_error "Only jammy (22.04) and noble (24.04) are supported. Aborting."
    exit 1
fi

# -----------------------------------------------------------------------------
# 1. Generate passwords (only on first run)
# -----------------------------------------------------------------------------
log_step "Generating secure credentials..."

if [ -f /root/.lightpack-credentials ]; then
    log_warn "Existing credentials found - reusing passwords"
    source /root/.lightpack-credentials
else
    DEPLOY_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)
    MYSQL_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)

    cat > /root/.lightpack-credentials <<EOF
DEPLOY_PASSWORD='${DEPLOY_PASSWORD}'
MYSQL_PASSWORD='${MYSQL_PASSWORD}'
EOF
    chmod 600 /root/.lightpack-credentials
fi

CLEANUP_NEEDED=true

# -----------------------------------------------------------------------------
# 2. System update
# -----------------------------------------------------------------------------
log_step "Updating system packages..."

export DEBIAN_FRONTEND=noninteractive

for i in {1..30}; do
    if ! fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
        break
    fi
    log_warn "Waiting for apt lock (attempt $i/30)..."
    sleep 5
done

apt-get update -qq
apt-get upgrade -y -qq
apt-get autoremove -y -qq

# -----------------------------------------------------------------------------
# 3. Install essential packages
# -----------------------------------------------------------------------------
log_step "Installing essential packages..."

apt-get install -y -qq \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    zip \
    htop \
    vim \
    ufw \
    fail2ban \
    certbot \
    acl \
    bc \
    supervisor

# -----------------------------------------------------------------------------
# 4. Create deploy user
# -----------------------------------------------------------------------------
log_step "Creating deploy user..."

if id "$DEPLOY_USER" &>/dev/null; then
    log_warn "User '$DEPLOY_USER' already exists, skipping creation"
else
    useradd -m -s /bin/bash "$DEPLOY_USER"
    echo "${DEPLOY_USER}:${DEPLOY_PASSWORD}" | chpasswd
    log_info "User '$DEPLOY_USER' created"
fi

SUDOERS_FILE="/etc/sudoers.d/${DEPLOY_USER}"
rm -f "$SUDOERS_FILE"

cat >> "$SUDOERS_FILE" <<EOF
# Lightpack deploy user - restricted passwordless sudo
# DO NOT ADD /bin/bash, /usr/bin/apt, or other privileged commands here
# These are the ONLY commands allowed without password:

${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload frankenphp
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl restart frankenphp
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl status frankenphp
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-frankenphp-write *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-frankenphp-remove *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-frankenphp-reload
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-frankenphp-test

# SSL certificate management (certbot standalone fallback)
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/certbot certonly *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/certbot renew

# Supervisor queue management
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-supervisor-write *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-supervisorctl * *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl reread
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl update

# MySQL database creation (runs as root via socket auth)
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-mysql-create *
EOF

chmod 0440 "$SUDOERS_FILE"
visudo -c >/dev/null || {
    log_error "Sudoers file syntax error - aborting"
    rm -f "$SUDOERS_FILE"
    exit 1
}

log_info "Sudo privileges configured (FrankenPHP reloads, site management, certbot)"

# Create FrankenPHP site management wrapper scripts
cat > /usr/local/sbin/lp-frankenphp-write <<'WSCRIPT'
#!/bin/bash
site="${1:-}"
if [ -z "$site" ] || [[ "$site" == */* ]] || [[ "$site" == *..* ]]; then
    echo "Usage: lp-frankenphp-write <site-name>" >&2; exit 1
fi
cat > "/etc/frankenphp/sites/${site}.caddy"
WSCRIPT

cat > /usr/local/sbin/lp-frankenphp-remove <<'WSCRIPT'
#!/bin/bash
site="${1:-}"
if [ -z "$site" ] || [[ "$site" == */* ]] || [[ "$site" == *..* ]]; then
    echo "Usage: lp-frankenphp-remove <site-name>" >&2; exit 1
fi
rm -f "/etc/frankenphp/sites/${site}.caddy"
WSCRIPT

cat > /usr/local/sbin/lp-frankenphp-reload <<'WSCRIPT'
#!/bin/bash
if [ ! -f /etc/frankenphp/Caddyfile ]; then
    echo "ERROR: Caddyfile not found" >&2; exit 1
fi
if ! /usr/local/bin/frankenphp validate --config /etc/frankenphp/Caddyfile; then
    echo "ERROR: Caddyfile validation failed" >&2; exit 1
fi
/bin/systemctl reload frankenphp
WSCRIPT

cat > /usr/local/sbin/lp-frankenphp-test <<'WSCRIPT'
#!/bin/bash
if [ ! -f /etc/frankenphp/Caddyfile ]; then
    echo "ERROR: Caddyfile not found" >&2; exit 1
fi
/usr/local/bin/frankenphp validate --config /etc/frankenphp/Caddyfile
WSCRIPT

cat > /usr/local/sbin/lp-supervisor-write <<'WSCRIPT'
#!/bin/bash
name="${1:-worker}"
if ! [[ "$name" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    echo "Invalid name: only alphanumeric, hyphens, and underscores allowed" >&2; exit 1
fi
cat > "/etc/supervisor/conf.d/lightpack-${name}.conf"
WSCRIPT

cat > /usr/local/sbin/lp-supervisorctl <<'WSCRIPT'
#!/bin/bash
action="${1:-}"
program="${2:-}"
case "$action" in
    start|stop|restart|status) ;;
    *) echo "Invalid action: $action" >&2; exit 1 ;;
esac
if ! [[ "$program" =~ ^lightpack-[a-zA-Z0-9_-]+:\*$ ]]; then
    echo "Invalid program: must match lightpack-{name}:*" >&2; exit 1
fi
exec /usr/bin/supervisorctl "$action" "$program"
WSCRIPT

cat > /usr/local/sbin/lp-mysql-create <<'WSCRIPT'
#!/bin/bash
dbname="${1:-}"
dbuser="${2:-}"
dbpass="${3:-}"

if [ -z "$dbname" ] || [ -z "$dbuser" ] || [ -z "$dbpass" ]; then
    echo "Usage: lp-mysql-create <dbname> <dbuser> <dbpass>" >&2; exit 1
fi

if ! [[ "$dbname" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "Invalid database name" >&2; exit 1
fi

if ! [[ "$dbuser" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "Invalid username" >&2; exit 1
fi

mysql <<ENDSQL
CREATE DATABASE IF NOT EXISTS \`${dbname}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${dbuser}'@'localhost' IDENTIFIED BY '${dbpass}';
GRANT ALL PRIVILEGES ON \`${dbname}\`.* TO '${dbuser}'@'localhost';
FLUSH PRIVILEGES;
ENDSQL
WSCRIPT

chmod 0750 /usr/local/sbin/lp-frankenphp-write \
           /usr/local/sbin/lp-frankenphp-remove \
           /usr/local/sbin/lp-frankenphp-reload \
           /usr/local/sbin/lp-frankenphp-test \
           /usr/local/sbin/lp-supervisor-write \
           /usr/local/sbin/lp-supervisorctl \
           /usr/local/sbin/lp-mysql-create

log_info "FrankenPHP management scripts installed to /usr/local/sbin/"

# Setup SSH directory
mkdir -p "/home/${DEPLOY_USER}/.ssh"
chmod 700 "/home/${DEPLOY_USER}/.ssh"

if [ -f /root/.ssh/authorized_keys ]; then
    cp /root/.ssh/authorized_keys "/home/${DEPLOY_USER}/.ssh/authorized_keys"
    chmod 600 "/home/${DEPLOY_USER}/.ssh/authorized_keys"
fi

chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "/home/${DEPLOY_USER}/.ssh"

# Add deploy user to www-data group for shared file access
usermod -aG www-data "$DEPLOY_USER"

grep -qF 'umask 002' "/home/${DEPLOY_USER}/.profile" || echo 'umask 002' >> "/home/${DEPLOY_USER}/.profile"
grep -qF 'umask 002' "/home/${DEPLOY_USER}/.bashrc"  || echo 'umask 002' >> "/home/${DEPLOY_USER}/.bashrc"

mkdir -p /var/www
setfacl -R  -m g:www-data:rwX,g:"${DEPLOY_USER}":rwX /var/www 2>/dev/null || true
setfacl -dR -m g:www-data:rwX,g:"${DEPLOY_USER}":rwX /var/www 2>/dev/null || true
log_info "Default ACLs set on /var/www (group-writable for www-data and ${DEPLOY_USER})"

# Generate SSH key for GitHub deployments
if [ ! -f "/home/${DEPLOY_USER}/.ssh/id_ed25519" ]; then
    su - "$DEPLOY_USER" -c "ssh-keygen -t ed25519 -C 'deploy@${SERVER_NAME}' -f ~/.ssh/id_ed25519 -N ''"
    su - "$DEPLOY_USER" -c "ssh-keyscan ${GIT_HOST} >> ~/.ssh/known_hosts 2>/dev/null"
    log_info "SSH key generated for GitHub access"
fi

DEPLOY_SSH_KEY=$(cat "/home/${DEPLOY_USER}/.ssh/id_ed25519.pub")

# -----------------------------------------------------------------------------
# 5. Configure timezone
# -----------------------------------------------------------------------------
log_step "Setting timezone to ${TIMEZONE}..."
timedatectl set-timezone "$TIMEZONE" || log_warn "Could not set timezone"

# -----------------------------------------------------------------------------
# 6. Configure swap (critical for small servers)
# -----------------------------------------------------------------------------
log_step "Configuring swap..."

if [ ! -f /swapfile ]; then
    RAM_MB=$(free -m | awk '/^Mem:/ {print $2}')
    SWAP_MB=$((RAM_MB * 2))
    [ "$SWAP_MB" -gt 2048 ] && SWAP_MB=2048

    dd if=/dev/zero of=/swapfile bs=1M count=$SWAP_MB status=none
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    log_info "Swap file created (${SWAP_MB}MB)"
else
    log_warn "Swap file already exists"
fi

# -----------------------------------------------------------------------------
# 7. Install PHP (CLI only — FrankenPHP has its own PHP runtime)
# -----------------------------------------------------------------------------
log_step "Installing PHP ${PHP_VERSION} CLI..."

UBUNTU_MAJOR=$(lsb_release -sr | cut -d. -f1)
if   [ "$UBUNTU_MAJOR" -ge 24 ]; then PHP_PPA_CODENAME="noble"
elif [ "$UBUNTU_MAJOR" -ge 22 ]; then PHP_PPA_CODENAME="jammy"
else                                   PHP_PPA_CODENAME=$(lsb_release -sc)
fi

log_info "Using PPA codename: ${PHP_PPA_CODENAME} (detected Ubuntu ${UBUNTU_MAJOR}.x)"

if [ ! -f /usr/share/keyrings/ondrej-php.gpg ]; then
    curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14AA40EC0831756756D7F66C4F4EA0AAE5267A6C" \
        | gpg --batch --yes --dearmor -o /usr/share/keyrings/ondrej-php.gpg
fi

echo "deb [arch=amd64 signed-by=/usr/share/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu ${PHP_PPA_CODENAME} main" \
    > /etc/apt/sources.list.d/ondrej-php.list

apt-get update -qq

PHP_PACKAGES=(
    "php${PHP_VERSION}-cli"
    "php${PHP_VERSION}-common"
    "php${PHP_VERSION}-mysql"
    "php${PHP_VERSION}-pgsql"
    "php${PHP_VERSION}-xml"
    "php${PHP_VERSION}-mbstring"
    "php${PHP_VERSION}-curl"
    "php${PHP_VERSION}-zip"
    "php${PHP_VERSION}-gd"
    "php${PHP_VERSION}-intl"
    "php${PHP_VERSION}-bcmath"
    "php${PHP_VERSION}-opcache"
    "php${PHP_VERSION}-redis"
    "php${PHP_VERSION}-sqlite3"
)

apt-get install -y -qq "${PHP_PACKAGES[@]}"

current_php=$(php -v 2>/dev/null | head -n 1 | grep -oP 'PHP \K[0-9]+\.[0-9]+')
if [ -z "$current_php" ]; then
    log_error "PHP installation failed"
    exit 1
fi
log_info "PHP ${current_php} CLI installed"

# PHP optimizations for CLI (used by console, queue workers, migrations)
PHP_CLI_INI="/etc/php/${PHP_VERSION}/cli/conf.d/99-lightpack.ini"
mkdir -p "$(dirname "$PHP_CLI_INI")"

cat > "$PHP_CLI_INI" <<EOF
; Lightpack Production Optimizations
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 64M
upload_max_filesize = 64M

; OPcache (useful for long-running CLI processes like queue workers)
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.fast_shutdown = 1

; Security
expose_php = Off
display_errors = Off
log_errors = On

; Performance
realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF

# -----------------------------------------------------------------------------
# 8. Install FrankenPHP
# -----------------------------------------------------------------------------
log_step "Installing FrankenPHP..."

FRANKENPHP_BIN="/usr/local/bin/frankenphp"
FRANKENPHP_DIR="/etc/frankenphp"
FRANKENPHP_SITES="${FRANKENPHP_DIR}/sites"

mkdir -p "$FRANKENPHP_SITES"

if [ ! -f "$FRANKENPHP_BIN" ]; then
    log_info "Downloading FrankenPHP..."
    curl -fsSL -o "$FRANKENPHP_BIN" \
        "https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64"
    chmod +x "$FRANKENPHP_BIN"
    log_info "FrankenPHP installed to ${FRANKENPHP_BIN}"
else
    log_warn "FrankenPHP already exists — skipping download"
fi

if ! "$FRANKENPHP_BIN" version >/dev/null 2>&1; then
    log_error "FrankenPHP binary is not working"
    exit 1
fi

# Create main Caddyfile with site import pattern
cat > "${FRANKENPHP_DIR}/Caddyfile" <<'EOF'
{
    frankenphp
    admin off
}

# Import all site configs
import /etc/frankenphp/sites/*.caddy
EOF

# Create catch-all: unmatched hostnames get 444 (connection closed)
cat > "${FRANKENPHP_SITES}/000-catchall.caddy" <<'EOF'
:80 {
    # Allow Let's Encrypt ACME challenges for auto-HTTPS
    handle /.well-known/acme-challenge/* {
        respond "OK" 200
    }
    respond "Not Found" 404
}
EOF

# Create systemd service for FrankenPHP
cat > /etc/systemd/system/frankenphp.service <<EOF
[Unit]
Description=FrankenPHP Web Server
Documentation=https://frankenphp.dev
After=network.target

[Service]
Type=simple
ExecStart=${FRANKENPHP_BIN} run --config ${FRANKENPHP_DIR}/Caddyfile
ExecReload=/bin/kill -USR1 $MAINPID
Restart=on-abnormal
User=www-data
Group=www-data
AmbientCapabilities=CAP_NET_BIND_SERVICE
LimitNOFILE=65535
Environment="HOME=/var/lib/caddy"
Environment="XDG_DATA_HOME=/var/lib/caddy/.local/share"
Environment="XDG_CONFIG_HOME=/var/lib/caddy/.config"

[Install]
WantedBy=multi-user.target
EOF

# Create Caddy data directory (certificates, config storage)
mkdir -p /var/lib/caddy
chown -R www-data:www-data /var/lib/caddy

# Create deployment directory
mkdir -p /var/www
chown -R "${DEPLOY_USER}:www-data" /var/www
chmod 775 /var/www

systemctl daemon-reload
systemctl start frankenphp
systemctl enable frankenphp

log_info "FrankenPHP configured and started"

# -----------------------------------------------------------------------------
# 9. Install MySQL (if requested)
# -----------------------------------------------------------------------------
if [ "$DB_TYPE" = "mysql" ]; then
    log_step "Installing MySQL..."

    apt-get install -y -qq mysql-server

    mysql -u root <<EOF
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ${MYSQL_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON ${MYSQL_DB}.* TO '${MYSQL_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
    log_info "MySQL configured and secured (root access via socket auth only)"

    TOTAL_RAM_MB=$(free -m | awk '/^Mem:/ {print $2}')
    MYSQL_BUFFER_MB=$(( TOTAL_RAM_MB / 4 ))
    [ "$MYSQL_BUFFER_MB" -lt 128 ] && MYSQL_BUFFER_MB=128
    [ "$MYSQL_BUFFER_MB" -gt 2048 ] && MYSQL_BUFFER_MB=2048

    cat > /etc/mysql/mysql.conf.d/99-lightpack.cnf <<EOF
[mysqld]
max_connections = 100
wait_timeout = 600
max_allowed_packet = 64M
innodb_buffer_pool_size = ${MYSQL_BUFFER_MB}M
innodb_log_file_size = 64M
innodb_file_per_table = 1
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
EOF

    systemctl restart mysql
    systemctl enable mysql
else
    log_info "Skipping MySQL installation (DB_TYPE=${DB_TYPE})"
fi

# -----------------------------------------------------------------------------
# 10. Install Composer
# -----------------------------------------------------------------------------
log_step "Installing Composer..."

if [ ! -f /usr/local/bin/composer ]; then
    EXPECTED_SIGNATURE=$(curl -s https://composer.github.io/installer.sig)
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    ACTUAL_SIGNATURE=$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")

    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
        log_error "Composer installer signature mismatch - possible tampering"
        rm -f /tmp/composer-setup.php
        exit 1
    fi

    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
    log_info "Composer installed"
else
    log_warn "Composer already exists"
fi

# -----------------------------------------------------------------------------
# 11. Configure firewall
# -----------------------------------------------------------------------------
log_step "Configuring firewall..."

ufw --force reset >/dev/null 2>&1 || true
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow http
ufw allow https
ufw --force enable

log_info "Firewall active: SSH, HTTP, HTTPS allowed"

# -----------------------------------------------------------------------------
# 12. Configure fail2ban
# -----------------------------------------------------------------------------
log_step "Configuring fail2ban..."

cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
EOF

systemctl restart fail2ban
systemctl enable fail2ban

# Enable and start supervisor
systemctl enable supervisor
systemctl start supervisor
log_info "Supervisor enabled and started"

# -----------------------------------------------------------------------------
# 13. Automatic security updates
# -----------------------------------------------------------------------------
log_step "Enabling automatic security updates..."

apt-get install -y -qq unattended-upgrades

cat > /etc/apt/apt.conf.d/50unattended-upgrades <<EOF
Unattended-Upgrade::Allowed-Origins {
    "\${distro_id}:\${distro_codename}-security";
    "\${distro_id}ESMApps:\${distro_codename}-apps-security";
};
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::MinimalSteps "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
EOF

systemctl enable unattended-upgrades
systemctl start unattended-upgrades

# -----------------------------------------------------------------------------
# 14. SSH hardening
# -----------------------------------------------------------------------------
log_step "Hardening SSH configuration..."

sed -i 's/^#*PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
sed -i 's/^PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sed -i 's/^#*PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sed -i 's/^#*MaxAuthTries.*/MaxAuthTries 3/' /etc/ssh/sshd_config

systemctl restart ssh

log_info "SSH hardened: root disabled, password auth disabled, key-only"

# -----------------------------------------------------------------------------
# 15. System optimizations
# -----------------------------------------------------------------------------
log_step "Applying system optimizations..."

cat > /etc/security/limits.d/99-lightpack.conf <<EOF
* soft nofile 65535
* hard nofile 65535
www-data soft nofile 65535
www-data hard nofile 65535
EOF

cat > /etc/sysctl.d/99-lightpack.conf <<EOF
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 8192
net.core.netdev_max_backlog = 5000
net.ipv4.tcp_fin_timeout = 30
net.ipv4.tcp_keepalive_time = 300
fs.file-max = 2097152
vm.swappiness = 10
EOF

sysctl -p /etc/sysctl.d/99-lightpack.conf >/dev/null

# -----------------------------------------------------------------------------
# 16. Final checks
# -----------------------------------------------------------------------------
log_step "Running final checks..."

services=("frankenphp" "fail2ban" "supervisor")
[ "$DB_TYPE" = "mysql" ] && services+=("mysql")

for service in "${services[@]}"; do
    if systemctl is-active --quiet "$service"; then
        log_info "  [OK] $service is running"
    else
        log_error "  [FAIL] $service is NOT running"
    fi
done

# -----------------------------------------------------------------------------
# Done
# -----------------------------------------------------------------------------
CLEANUP_NEEDED=false

SERVER_IP=$(hostname -I | awk '{print $1}')

echo ""
echo "================================================================================"
echo "  SERVER PROVISIONING COMPLETE"
echo "================================================================================"
echo ""
echo "  Host:        ${SERVER_IP}"
echo "  Deploy user: ${DEPLOY_USER}"
echo "  PHP:         ${current_php}"
echo "  Web Server:  FrankenPHP"
echo ""
echo "  DEPLOY USER PASSWORD (save now — shown once):"
echo "    ${DEPLOY_PASSWORD}"
echo ""
if [ "$DB_TYPE" = "mysql" ]; then
echo "  DATABASE CREDENTIALS (save now — shown once):"
echo "    DB_NAME: ${MYSQL_DB}"
echo "    DB_USER: ${MYSQL_USER}"
echo "    DB_PSWD: ${MYSQL_PASSWORD}"
echo ""
fi
echo "  DEPLOY SSH PUBLIC KEY (add to your Git repo):"
echo "    ${DEPLOY_SSH_KEY}"
echo ""
echo "  Security: root SSH disabled · UFW active · Fail2Ban active"
echo "================================================================================"

rm -f "$0"
