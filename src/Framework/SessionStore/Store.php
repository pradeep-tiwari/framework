<?php

namespace Lightpack\SessionStore;

use Lightpack\Http\Cookie;
use Lightpack\Utils\Arr;
use Lightpack\SessionStore\Contracts\StoreInterface;

class Store
{
    private StoreInterface $driver;
    private ?string $id = null;
    private array $data = [];
    private bool $started = false;
    private Cookie $cookie;
    private string $cookieName;
    private Arr $arr;

    public function __construct(
        StoreInterface $driver,
        string $secret,
        string $cookieName = 'LPSESSID'
    ) {
        $this->driver = $driver;
        $this->cookie = new Cookie($secret);
        $this->cookieName = $cookieName;
        $this->arr = new Arr();
    }

    /**
     * Start a new session or load existing one
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        // Try to get session ID from cookie
        $id = $this->cookie->get($this->cookieName);

        if ($id !== null && $this->driver->isValid($id)) {
            $data = $this->driver->load($id);
            
            if ($data !== null) {
                $this->id = $id;
                $this->data = $data;
                $this->started = true;
                $this->ageFlashData();
                return true;
            }
        }

        // Create new session
        $this->id = $this->driver->create();
        $this->data = [];
        $this->started = true;

        // Set cookie with new session ID
        $this->setCookie();

        return true;
    }

    /**
     * Get session ID
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Check if key uses dot notation
     */
    private function hasDotNotation(string $key): bool
    {
        return str_contains($key, '.');
    }

    /**
     * Get data for dot notation operations
     */
    private function getDataForDotNotation(string $key): array
    {
        $topKey = explode('.', $key)[0];
        return [$topKey, [$topKey => $this->get($topKey) ?? []]];
    }

    /**
     * Set session data
     */
    public function set(string $key, $value): void
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        if (!$this->hasDotNotation($key)) {
            $this->data[$key] = $value;
            $this->driver->save($this->id, $this->data);
            return;
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        $this->arr->set($key, $value, $data);
        $this->data[$topKey] = $data[$topKey];
        $this->driver->save($this->id, $this->data);
    }

    /**
     * Get session data
     */
    public function get(?string $key = null, $default = null)
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        if ($key === null) {
            return $this->data;
        }

        if (!$this->hasDotNotation($key)) {
            return $this->data[$key] ?? $default;
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        return $this->arr->get($key, $data) ?? $default;
    }

    /**
     * Delete session data
     */
    public function delete(string $key): void
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        if (!$this->hasDotNotation($key)) {
            unset($this->data[$key]);
            $this->driver->save($this->id, $this->data);
            return;
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        $this->arr->delete($key, $data);
        $this->data[$topKey] = $data[$topKey];
        $this->driver->save($this->id, $this->data);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        if (!$this->hasDotNotation($key)) {
            return isset($this->data[$key]);
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        return $this->arr->has($key, $data);
    }

    /**
     * Get/Set flash message
     */
    public function flash(string $key, $value = null)
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        if ($value !== null) {
            $flash = $this->get('_flash', []);
            $flash['new'][$key] = $value;
            $this->set('_flash', $flash);
            return;
        }

        $flash = $this->get('_flash', []);
        $value = $flash['current'][$key] ?? null;
        unset($flash['current'][$key]);
        $this->set('_flash', $flash);
        return $value;
    }

    /**
     * Age flash data - move new to current
     */
    private function ageFlashData(): void
    {
        $flash = $this->get('_flash', []);
        $this->set('_flash', [
            'current' => $flash['new'] ?? [],
            'new' => []
        ]);
    }

    /**
     * Get/Generate CSRF token
     */
    public function token(): string
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        $token = $this->get('_token');

        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->set('_token', $token);
        }

        return $token;
    }

    /**
     * Verify CSRF token
     */
    public function verifyToken(): bool
    {
        if (!$this->started) {
            return false;
        }

        $token = null;

        // Check headers first (for AJAX/API requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        // Check POST data
        else if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? null;
        }
        // Check JSON body
        else if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody) {
                $jsonData = json_decode($rawBody, true);
                $token = $jsonData['_token'] ?? null;
            }
        }

        if (!$token) {
            return false;
        }

        return hash_equals($this->get('_token'), $token);
    }

    public function hasInvalidToken(): bool
    {
        return !$this->verifyToken();
    }

    /**
     * Remove key from session
     */
    public function remove(string $key): void
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        unset($this->data[$key]);
        $this->driver->save($this->id, $this->data);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        if (!$this->started) {
            return [];
        }

        return $this->data;
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        $this->data = [];
        $this->driver->save($this->id, $this->data);
    }

    /**
     * Destroy the session
     */
    public function destroy(): void
    {
        if (!$this->started || !$this->id || !$this->driver->isValid($this->id)) {
            return;
        }

        $this->driver->destroy($this->id);
        $this->removeCookie();
        $this->id = null;
        $this->data = [];
        $this->started = false;
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(): bool
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        $oldId = $this->id;
        $this->id = $this->driver->create();
        
        if ($this->driver->save($this->id, $this->data)) {
            $this->driver->destroy($oldId);
            $this->setCookie();
            return true;
        }

        return false;
    }

    /**
     * Get session creation time
     */
    public function getCreatedAt(): ?int
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        return $this->driver->getCreatedAt($this->id);
    }

    /**
     * Get session last access time
     */
    public function getLastAccessedAt(): ?int
    {
        if (!$this->started) {
            throw new \RuntimeException('Session not started');
        }

        return $this->driver->getLastAccessedAt($this->id);
    }

    /**
     * Set session cookie
     */
    private function setCookie(): void
    {
        $this->cookie->set(
            $this->cookieName,
            $this->id,
            0, // Until browser closes
            ['secure' => true] // Force HTTPS for sessions
        );
    }

    /**
     * Remove session cookie
     */
    private function removeCookie(): void
    {
        $this->cookie->delete($this->cookieName);
    }
}
