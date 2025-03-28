<?php

namespace Lightpack\Cache\Drivers;

use Lightpack\Cache\DriverInterface;

class FileDriver implements DriverInterface
{
    private $path;

    public function __construct(string $path)
    {
        $this->setPath($path);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key)
    {
        $file = $this->getFilename($key);

		if(!file_exists($file)) {
            return null;
        }
        
        $contents = unserialize(file_get_contents($file));

        if($contents['ttl'] > time()) {
            return $contents['value'];
        }

        $this->delete($key); 
        return null;
    }

    public function set(string $key, $value, int $lifetime, bool $preserveTtl = false)
    {
        $file = $this->getFilename($key);
        
        if ($preserveTtl && file_exists($file)) {
            // Keep existing TTL
            $current = unserialize(file_get_contents($file));
            $ttl = $current['ttl'];
        } else {
            $ttl = $lifetime;
        }

        $value = serialize([
            'ttl' => $ttl,
            'value' => $value,
        ]);

		file_put_contents($file, $value, LOCK_EX);
    }

    public function delete($key)
    {
        $file = $this->getFilename($key);

        if(file_exists($file)) {
            unlink($file);
        }
    }

    public function flush()
    {
		array_map('unlink', glob($this->path . '/*'));
    }

    private function setPath(string $path)
    {
        $this->path = rtrim($path, '/');

        if (!file_exists($this->path)) {
            mkdir($this->path, 0775, true);
        }
    }

    private function getFilename($key)
    {
        return $this->path . DIRECTORY_SEPARATOR . sha1($key);
    }
}