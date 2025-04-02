<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;

class NativeDriver implements DriverInterface
{
    private bool $started = false;
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'name' => 'LPSESSID',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'save_path' => '',
        ], $options);

        // Set save path before any session operations
        if ($this->options['save_path']) {
            ini_set('session.save_path', $this->options['save_path']);
        }

        // Configure session settings
        ini_set('session.name', $this->options['name']);
        ini_set('session.cookie_lifetime', (string) $this->options['lifetime']);
        ini_set('session.cookie_path', $this->options['path']);
        ini_set('session.cookie_domain', $this->options['domain']);
        ini_set('session.cookie_secure', $this->options['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $this->options['httponly'] ? '1' : '0');
        ini_set('session.gc_maxlifetime', (string) $this->options['lifetime']);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '1');
    }

    /**
     * Create a new session and return its ID
     */
    public function create(): string
    {
        if (!$this->started) {
            $this->start();
        }

        $_SESSION = [
            '_created_at' => time(),
            '_last_accessed_at' => time(),
        ];

        return session_id();
    }

    /**
     * Load session data by ID
     */
    public function load(string $id): ?array
    {
        if (!$this->isValid($id)) {
            return null;
        }

        if (!$this->started) {
            session_id($id);
            $this->start();
        }

        $this->touch($id);
        return $_SESSION;
    }

    /**
     * Save session data
     */
    public function save(string $id, array $data): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        if (!$this->started) {
            session_id($id);
            $this->start();
        }

        $_SESSION = array_merge($_SESSION, $data);
        $_SESSION['_last_accessed_at'] = time();
        
        return true;
    }

    /**
     * Destroy session data
     */
    public function destroy(string $id): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        if (!$this->started) {
            session_id($id);
            $this->start();
        }

        session_destroy();
        $this->started = false;
        return true;
    }

    /**
     * Check if session is valid
     */
    public function isValid(string $id): bool
    {
        // Check if session file exists
        $path = ini_get('session.save_path') ?: sys_get_temp_dir();
        $file = $path . '/sess_' . $id;
        
        if (!file_exists($file)) {
            return false;
        }

        // Check file modification time
        if (filemtime($file) + $this->options['lifetime'] < time()) {
            unlink($file);
            return false;
        }

        return true;
    }

    /**
     * Update session last access time
     */
    public function touch(string $id): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        if (!$this->started) {
            session_id($id);
            $this->start();
        }

        $_SESSION['_last_accessed_at'] = time();
        return true;
    }

    /**
     * Get session creation time
     */
    public function getCreatedAt(string $id): ?int
    {
        if (!$this->isValid($id)) {
            return null;
        }

        if (!$this->started) {
            session_id($id);
            $this->start();
        }

        return $_SESSION['_created_at'] ?? null;
    }

    /**
     * Get session last access time
     */
    public function getLastAccessedAt(string $id): ?int
    {
        if (!$this->isValid($id)) {
            return null;
        }

        if (!$this->started) {
            session_id($id);
            $this->start();
        }

        return $_SESSION['_last_accessed_at'] ?? null;
    }

    /**
     * Start the session
     */
    private function start(): void
    {
        if ($this->started) {
            return;
        }

        session_start();
        $this->started = true;
    }
}
