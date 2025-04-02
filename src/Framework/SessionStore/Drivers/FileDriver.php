<?php

namespace Lightpack\SessionStore\Drivers;

use Lightpack\SessionStore\Contracts\StoreInterface;
use RuntimeException;

class FileDriver implements StoreInterface
{
    private string $path;
    private int $lifetime;
    private string $userAgent;

    public function __construct(string $path, int $lifetime = 7200)
    {
        $this->path = rtrim($path, '/');
        $this->lifetime = $lifetime;
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new RuntimeException("Failed to create session directory: {$path}");
            }
        }

        if (!is_writable($path)) {
            throw new RuntimeException("Session directory is not writable: {$path}");
        }
    }

    public function create(): string
    {
        do {
            $id = bin2hex(random_bytes(32));
            $file = $this->getFilePath($id);
        } while (file_exists($file));

        $data = [
            'data' => [],
            'created_at' => time(),
            'last_accessed_at' => time(),
            'user_agent' => $this->userAgent,
        ];

        if ($this->saveToFile($id, $data)) {
            return $id;
        }

        throw new RuntimeException('Failed to create session file');
    }

    public function load(string $id): ?array
    {
        if (!$this->isValid($id)) {
            return null;
        }

        $file = $this->getFilePath($id);
        $content = file_get_contents($file);
        
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        return $data['data'] ?? null;
    }

    public function save(string $id, array $data): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        $file = $this->getFilePath($id);
        $content = file_get_contents($file);
        
        if ($content === false) {
            return false;
        }

        $session = json_decode($content, true);
        $session['data'] = $data;
        $session['last_accessed_at'] = time();

        return $this->saveToFile($id, $session);
    }

    public function destroy(string $id): bool
    {
        $file = $this->getFilePath($id);
        
        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function isValid(string $id): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $id)) {
            return false;
        }

        $file = $this->getFilePath($id);
        
        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        
        if (!isset($data['last_accessed_at'], $data['user_agent'])) {
            return false;
        }

        // Check if session has expired
        if (time() - $data['last_accessed_at'] > $this->lifetime) {
            $this->destroy($id);
            return false;
        }

        // Verify user agent
        if ($data['user_agent'] !== $this->userAgent) {
            return false;
        }

        return true;
    }

    public function touch(string $id): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        $file = $this->getFilePath($id);
        $content = file_get_contents($file);
        
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        $data['last_accessed_at'] = time();

        return $this->saveToFile($id, $data);
    }

    public function getCreatedAt(string $id): ?int
    {
        if (!$this->isValid($id)) {
            return null;
        }

        $file = $this->getFilePath($id);
        $content = file_get_contents($file);
        
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        return $data['created_at'] ?? null;
    }

    public function getLastAccessedAt(string $id): ?int
    {
        if (!$this->isValid($id)) {
            return null;
        }

        $file = $this->getFilePath($id);
        $content = file_get_contents($file);
        
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        return $data['last_accessed_at'] ?? null;
    }

    private function getFilePath(string $id): string
    {
        return $this->path . '/' . $id . '.json';
    }

    private function saveToFile(string $id, array $data): bool
    {
        $file = $this->getFilePath($id);
        return file_put_contents($file, json_encode($data)) !== false;
    }
}
