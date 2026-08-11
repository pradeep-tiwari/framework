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
     * Create a fake uploaded file for use with withFiles().
     *
     * Creates a real temp file so that store() can copy it. The temp file is
     * deleted automatically after the test.
     *
     * @param string $name    Original filename (e.g. 'photo.jpg')
     * @param string $content File content (defaults to a small placeholder)
     * @param string $mime    MIME type reported in the $_FILES array
     * @return array          A valid $_FILES entry ready for withFiles()
     */
    public function fakeFile(
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
     * Each spec may contain:
     *   - 'name'    (string) Original filename, e.g. 'photo.jpg'
     *   - 'content' (string) File content (defaults to a small placeholder)
     *   - 'mime'    (string) MIME type (defaults to 'text/plain')
     *
     * Returns the $_FILES array-syntax structure ready for withFiles():
     *
     *   $this->withFiles([
     *       'photos' => $this->fakeFiles([
     *           ['name' => 'a.jpg', 'mime' => 'image/jpeg'],
     *           ['name' => 'b.jpg', 'mime' => 'image/jpeg'],
     *       ]),
     *   ]);
     *
     * All temp files are tracked and deleted automatically after the test.
     */
    public function fakeFiles(array $specs): array
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
     * Prevents store() from writing to the real storage directory during tests.
     * The temp directory and all files within it are deleted after the test.
     *
     * @return string The absolute path of the temp storage root, for assertions.
     */
    public function fakeStorage(): string
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
