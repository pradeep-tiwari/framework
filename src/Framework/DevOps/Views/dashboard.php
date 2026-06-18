<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lightpack DevOps Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: #1a1a2e;
            --bg-card-hover: #222240;
            --border: rgba(255,255,255,0.06);
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --accent: #6366f1;
            --accent-glow: rgba(99,102,241,0.3);
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --terminal-bg: #0d0d12;
            --terminal-text: #a5b4fc;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
        }

        /* Animated background gradient */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 50%, rgba(99,102,241,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(236,72,153,0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0 32px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 32px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            box-shadow: 0 0 20px rgba(99,102,241,0.4);
        }

        .brand-text h1 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #e2e8f0, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-text span {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Environment selector */
        .env-selector {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .env-selector label {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .env-select {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
            outline: none;
            transition: all 0.2s;
        }

        .env-select:hover, .env-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        /* Environment info bar */
        .env-info {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .env-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .env-info-label {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .env-info-value {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Section cards */
        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .section-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
        }

        .section-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
        }

        .section-body {
            padding: 12px;
        }

        .command-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s;
            margin-bottom: 4px;
        }

        .command-row:last-child {
            margin-bottom: 0;
        }

        .command-row:hover {
            background: var(--bg-card-hover);
        }

        .command-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .command-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .command-desc {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .command-actions {
            display: flex;
            gap: 6px;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .command-row:hover .command-actions {
            opacity: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-sm { padding: 4px 10px; font-size: 11px; }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }

        .btn-primary:hover {
            box-shadow: 0 0 12px var(--accent-glow);
            transform: scale(1.02);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }

        /* Terminal panel */
        .terminal-panel {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 600px;
            max-width: calc(100vw - 48px);
            background: var(--terminal-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            transform: translateY(calc(100% + 30px));
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 100;
        }

        .terminal-panel.active {
            transform: translateY(0);
        }

        .terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
        }

        .terminal-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        .terminal-dots {
            display: flex;
            gap: 6px;
        }

        .terminal-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .terminal-dot.red { background: #ff5f56; }
        .terminal-dot.yellow { background: #ffbd2e; }
        .terminal-dot.green { background: #27c93f; }

        .terminal-body {
            padding: 16px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: var(--terminal-text);
        }

        .terminal-body pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .terminal-empty {
            color: var(--text-secondary);
            font-style: italic;
        }

        .terminal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            padding: 4px;
        }

        .terminal-close:hover {
            color: var(--text-primary);
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 14px 20px;
            border-radius: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            font-size: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            transform: translateX(calc(100% + 30px));
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 200;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success { border-left: 3px solid var(--success); }
        .toast.error { border-left: 3px solid var(--error); }

        /* Responsive */
        @media (max-width: 768px) {
            .section-grid {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            .terminal-panel {
                width: calc(100vw - 32px);
                right: 16px;
                bottom: 16px;
            }
            .command-actions {
                opacity: 1;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="brand">
                <div class="brand-logo">LP</div>
                <div class="brand-text">
                    <h1>DevOps Dashboard</h1>
                    <span>Manage deployments, workers, and infrastructure</span>
                </div>
            </div>
            <div class="env-selector">
                <label>Environment</label>
                <select class="env-select" onchange="switchEnv(this.value)">
                    <?php foreach ($environments as $env): ?>
                        <option value="<?= htmlspecialchars($env) ?>" <?= $env === $currentEnv ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($env)) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (empty($environments)): ?>
                        <option>No environments configured</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Environment Info -->
        <?php if ($envConfig): ?>
        <div class="env-info">
            <div class="env-info-item">
                <span class="status-dot"></span>
                <div>
                    <div class="env-info-label">Status</div>
                    <div class="env-info-value">Connected</div>
                </div>
            </div>
            <div class="env-info-item">
                <div>
                    <div class="env-info-label">Host</div>
                    <div class="env-info-value"><?= htmlspecialchars($envConfig['host'] ?? 'N/A') ?></div>
                </div>
            </div>
            <div class="env-info-item">
                <div>
                    <div class="env-info-label">Path</div>
                    <div class="env-info-value"><?= htmlspecialchars($envConfig['path'] ?? 'N/A') ?></div>
                </div>
            </div>
            <div class="env-info-item">
                <div>
                    <div class="env-info-label">Branch</div>
                    <div class="env-info-value"><?= htmlspecialchars($envConfig['branch'] ?? 'main') ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Command Grid -->
        <div class="section-grid">
            <?php foreach ($commands as $groupKey => $group): ?>
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-icon" style="background: <?= $group['color'] ?>15; color: <?= $group['color'] ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php if ($group['icon'] === 'rocket'): ?>
                                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4.5c1.5-1.5 4.5-2 4.5-2"/><path d="M15 12h5s-.55 3.03-2 4.5c-1.5 1.5-4.5 2-4.5 2"/>
                                <?php elseif ($group['icon'] === 'layers'): ?>
                                    <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                                <?php elseif ($group['icon'] === 'clock'): ?>
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                <?php elseif ($group['icon'] === 'file-text'): ?>
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>
                                <?php elseif ($group['icon'] === 'database'): ?>
                                    <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                                <?php elseif ($group['icon'] === 'server'): ?>
                                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
                                <?php endif; ?>
                            </svg>
                        </div>
                        <span class="section-title"><?= htmlspecialchars($group['label']) ?></span>
                    </div>
                    <div class="section-body">
                        <?php foreach ($group['commands'] as $cmd): ?>
                            <div class="command-row" data-cmd="<?= htmlspecialchars($cmd['cmd']) ?>">
                                <div class="command-info">
                                    <span class="command-label"><?= htmlspecialchars($cmd['label']) ?></span>
                                    <span class="command-desc"><?= htmlspecialchars($cmd['desc']) ?></span>
                                </div>
                                <div class="command-actions">
                                    <button class="btn btn-ghost btn-sm" onclick="copyCommand('<?= htmlspecialchars($cmd['cmd']) ?>')" title="Copy CLI command">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="runCommand('<?= htmlspecialchars($cmd['cmd']) ?>')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        Run
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Terminal Panel -->
    <div class="terminal-panel" id="terminal">
        <div class="terminal-header">
            <div class="terminal-dots">
                <span class="terminal-dot red"></span>
                <span class="terminal-dot yellow"></span>
                <span class="terminal-dot green"></span>
            </div>
            <div class="terminal-title">
                <span id="terminalCommand">Terminal</span>
            </div>
            <button class="terminal-close" onclick="closeTerminal()">&times;</button>
        </div>
        <div class="terminal-body" id="terminalBody">
            <div class="terminal-empty">Select a command and click Run to see output here.</div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        const currentEnv = '<?= htmlspecialchars($currentEnv) ?>';
        const baseUrl = window.location.pathname.replace(/\/?$/, '/');

        function switchEnv(env) {
            const url = new URL(window.location);
            url.searchParams.set('env', env);
            window.location.href = url.toString();
        }

        function showTerminal() {
            document.getElementById('terminal').classList.add('active');
        }

        function closeTerminal() {
            document.getElementById('terminal').classList.remove('active');
        }

        function setTerminalContent(html, command) {
            const body = document.getElementById('terminalBody');
            const title = document.getElementById('terminalCommand');
            body.innerHTML = html;
            title.textContent = command || 'Terminal';
            showTerminal();
        }

        function appendTerminal(text) {
            const body = document.getElementById('terminalBody');
            const pre = body.querySelector('pre');
            if (pre) {
                pre.textContent += text;
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function copyCommand(cmd) {
            const text = `php console ${cmd}${currentEnv ? ' ' + currentEnv : ''}`;
            navigator.clipboard.writeText(text).then(() => {
                showToast('Command copied to clipboard');
            }).catch(() => {
                showToast('Failed to copy', 'error');
            });
        }

        async function runCommand(cmd) {
            showTerminal();
            setTerminalContent('<pre>Running: php console ' + cmd + ' ' + currentEnv + '\n<span class="spinner"></span> Executing...</pre>', cmd);

            const urlParams = new URLSearchParams(window.location.search);
            const key = urlParams.get('key') || '';

            try {
                const response = await fetch(baseUrl + 'run?key=' + encodeURIComponent(key), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ command: cmd, env: currentEnv, args: [] }),
                });

                const data = await response.json();

                if (data.error) {
                    setTerminalContent('<pre style="color: var(--error)">Error: ' + data.error + '</pre>', cmd);
                    showToast(data.error, 'error');
                    return;
                }

                const status = data.success ? '\n\n[Exit: 0]' : '\n\n[Exit: ' + (data.exit_code || 1) + ']';
                const output = (data.output || '(no output)') + status;
                setTerminalContent('<pre>' + escapeHtml(output) + '</pre>', cmd);

                if (data.success) {
                    showToast('Command completed successfully');
                } else {
                    showToast('Command failed', 'error');
                }
            } catch (err) {
                setTerminalContent('<pre style="color: var(--error)">Network error: ' + escapeHtml(err.message) + '</pre>', cmd);
                showToast('Request failed', 'error');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Keyboard shortcut: Escape to close terminal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeTerminal();
        });
    </script>
</body>
</html>
