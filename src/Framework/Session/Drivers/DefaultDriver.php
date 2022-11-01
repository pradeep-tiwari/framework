<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;

class DefaultDriver implements DriverInterface
{
    public function __construct(string $name)
    {
        if (!$this->started() || !headers_sent()) {
            ini_set('session.use_only_cookies', TRUE);
            ini_set('session.use_trans_sid', FALSE);
            session_name($name);
            session_start();
        }

        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Lightpack PHP';
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION;
        }

        return $_SESSION[$key] ?? $default;
    }

    public function delete(string $key)
    {
        if ($_SESSION[$key] ?? null) {
            unset($_SESSION[$key]);
        }
    }

    public function regenerate(): bool
    {
        return session_regenerate_id();
    }

    public function verifyAgent(): bool
    {
        if ($this->get('user_agent') == $_SERVER['HTTP_USER_AGENT']) {
            return true;
        }

        return false;
    }

    public function destroy()
    {
        session_unset();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function started(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
