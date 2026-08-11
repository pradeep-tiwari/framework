<?php

namespace Lightpack\Testing;

use Lightpack\Storage\LocalStorage;

trait FileUploadTrait
{
    /** @var string[] Temp files created by fakeFile(), cleaned up after each test. */
    private array $fakeFiles = [];

    /** @var string|null Temp storage dir created by fakeStorage(), cleaned up after each test. */
    private ?string $fakeStorageDir = null;

    /**
     * Simulate file uploads for the next request().
     *
     * Accepts file specs keyed by form field name. Each spec may contain:
     *   - 'name'    (string) Original filename, e.g. 'photo.jpg'
     *   - 'content' (string) File content (defaults to a small placeholder)
     *   - 'mime'    (string) MIME type (defaults to 'text/plain')
     *
     * Single file:
     *
     *   $this->withFiles([
     *       'avatar' => ['name' => 'photo.jpg', 'mime' => 'image/jpeg'],
     *   ])->request('POST', '/settings/avatar');
     *
     * Multiple files (array-syntax for fields that accept multiple uploads):
     *
     *   $this->withFiles([
     *       'images' => [
     *           ['name' => 'a.jpg', 'mime' => 'image/jpeg'],
     *           ['name' => 'b.jpg', 'mime' => 'image/jpeg'],
     *       ],
     *   ])->request('POST', '/gallery');
     *
     * Storage is automatically faked to an isolated temp directory so that
     * store() never writes to the real storage directory. All temp files and
     * the temp storage directory are cleaned up after the test.
     */
    public function withFiles(array $files): self
    {
        $this->isMultipartFormdata = true;

        // Auto-fake storage so store() doesn't write to the real storage directory.
        if ($this->fakeStorageDir === null) {
            $this->fakeStorage();
        }

        foreach ($files as $key => $spec) {
            if (!empty($spec) && is_array(reset($spec))) {
                // Multi-file specs: [['name' => 'a.jpg', ...], ['name' => 'b.jpg', ...]]
                $_FILES[$key] = $this->fakeFiles($spec);
            } else {
                // Single-file spec: ['name' => 'photo.jpg', 'content' => 'fake', 'mime' => 'image/jpeg']
                $_FILES[$key] = $this->fakeFile(
                    $spec['name']    ?? 'file.txt',
                    $spec['content'] ?? 'fake file content',
                    $spec['mime']    ?? 'text/plain'
                );
            }
        }

        return $this;
    }

    /**
     * Create a fake uploaded file and return a valid $_FILES entry.
     *
     * Creates a real temp file so that store() can copy it. The temp file is
     * tracked and deleted automatically after the test.
     */
    protected function fakeFile(
        string $name,
        string $content = 'fake file content',
        string $mime = 'text/plain'
    ): array {
        $tmp = tempnam(sys_get_temp_dir(), 'lightpack_test_');
        file_put_contents($tmp, $content);
        $this->fakeFiles[] = $tmp;

        return [
            'name'     => $name,
            'type'     => $mime,
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($content),
        ];
    }

    /**
     * Create multiple fake uploaded files in PHP's multi-file array syntax.
     *
     * Each spec may contain 'name', 'content', and 'mime' — all optional with
     * the same defaults as fakeFile(). Returns the $_FILES array-syntax
     * structure ready for assignment to $_FILES.
     */
    protected function fakeFiles(array $specs): array
    {
        $result = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];

        foreach ($specs as $spec) {
            $file = $this->fakeFile(
                $spec['name']    ?? 'file.txt',
                $spec['content'] ?? 'fake file content',
                $spec['mime']    ?? 'text/plain'
            );

            $result['name'][]     = $file['name'];
            $result['type'][]     = $file['type'];
            $result['tmp_name'][] = $file['tmp_name'];
            $result['error'][]    = $file['error'];
            $result['size'][]     = $file['size'];
        }

        return $result;
    }

    /**
     * Swap the storage service to an isolated temp directory.
     *
     * Called automatically by withFiles(). Exposed as protected for the rare
     * case where a test needs storage faking without a file upload (e.g.
     * testing a DELETE route that removes a file from storage).
     *
     * @return string The absolute path of the temp storage root, for assertions.
     */
    protected function fakeStorage(): string
    {
        $dir = sys_get_temp_dir() . '/lightpack_test_storage_' . uniqid();
        mkdir($dir, 0777, true);
        $this->container->register('storage', fn () => new LocalStorage($dir));
        $this->fakeStorageDir = $dir;

        return $dir;
    }

    /**
     * Called by TestCase::tearDown() to clean up temp files and storage directory.
     */
    protected function tearDownFileUploads(): void
    {
        foreach ($this->fakeFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->fakeFiles = [];

        if ($this->fakeStorageDir !== null) {
            $this->removeTempDirectory($this->fakeStorageDir);
            $this->fakeStorageDir = null;
        }
    }

    private function removeTempDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeTempDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
