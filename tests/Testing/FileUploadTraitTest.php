<?php

namespace Lightpack\Tests\Testing;

use Lightpack\Container\Container;
use Lightpack\Storage\LocalStorage;
use Lightpack\Testing\FileUploadTrait;
use PHPUnit\Framework\TestCase;

class FileUploadTraitTest extends TestCase
{
    use FileUploadTrait;

    protected Container $container;
    protected $isMultipartFormdata = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = Container::getInstance();
        $_FILES = [];
        $_SERVER['X_LIGHTPACK_TEST_UPLOAD'] = true;
    }

    protected function tearDown(): void
    {
        $this->tearDownFileUploads();
        Container::getInstance()->reset();
        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // withFiles() – single file
    // ---------------------------------------------------------------------

    public function testWithFilesSetsSingleFileInFilesArray()
    {
        $this->withFiles([
            'avatar' => ['name' => 'photo.jpg', 'content' => 'hello', 'mime' => 'image/jpeg'],
        ]);

        $this->assertArrayHasKey('avatar', $_FILES);
        $this->assertSame('photo.jpg', $_FILES['avatar']['name']);
        $this->assertSame('image/jpeg', $_FILES['avatar']['type']);
        $this->assertSame(UPLOAD_ERR_OK, $_FILES['avatar']['error']);
        $this->assertSame(5, $_FILES['avatar']['size']);
        $this->assertFileExists($_FILES['avatar']['tmp_name']);
    }

    public function testWithFilesCreatesRealTempFileWithContent()
    {
        $this->withFiles([
            'doc' => ['name' => 'note.txt', 'content' => 'test content here'],
        ]);

        $this->assertSame('test content here', file_get_contents($_FILES['doc']['tmp_name']));
    }

    public function testWithFilesUsesDefaultValuesWhenSpecIsMinimal()
    {
        $this->withFiles([
            'file' => [],
        ]);

        $this->assertSame('file.txt', $_FILES['file']['name']);
        $this->assertSame('text/plain', $_FILES['file']['type']);
        $this->assertSame('fake file content', file_get_contents($_FILES['file']['tmp_name']));
    }

    public function testWithFilesSetsMultipartFormdataFlag()
    {
        $this->withFiles([
            'avatar' => ['name' => 'photo.jpg'],
        ]);

        $this->assertTrue($this->isMultipartFormdata);
    }

    public function testWithFilesReturnsSelfForChaining()
    {
        $result = $this->withFiles([
            'avatar' => ['name' => 'photo.jpg'],
        ]);

        $this->assertSame($this, $result);
    }

    // ---------------------------------------------------------------------
    // withFiles() – multiple files
    // ---------------------------------------------------------------------

    public function testWithFilesSetsMultipleFilesInFilesArray()
    {
        $this->withFiles([
            'images' => [
                ['name' => 'a.jpg', 'content' => 'aaa', 'mime' => 'image/jpeg'],
                ['name' => 'b.jpg', 'content' => 'bbb', 'mime' => 'image/jpeg'],
            ],
        ]);

        $this->assertArrayHasKey('images', $_FILES);
        $this->assertCount(2, $_FILES['images']['name']);
        $this->assertSame('a.jpg', $_FILES['images']['name'][0]);
        $this->assertSame('b.jpg', $_FILES['images']['name'][1]);
        $this->assertSame('image/jpeg', $_FILES['images']['type'][0]);
        $this->assertSame('image/jpeg', $_FILES['images']['type'][1]);
        $this->assertSame(UPLOAD_ERR_OK, $_FILES['images']['error'][0]);
        $this->assertSame(UPLOAD_ERR_OK, $_FILES['images']['error'][1]);
        $this->assertFileExists($_FILES['images']['tmp_name'][0]);
        $this->assertFileExists($_FILES['images']['tmp_name'][1]);
    }

    public function testWithFilesMultipleFilesContentIsCorrect()
    {
        $this->withFiles([
            'images' => [
                ['name' => 'a.jpg', 'content' => 'content-a'],
                ['name' => 'b.jpg', 'content' => 'content-b'],
            ],
        ]);

        $this->assertSame('content-a', file_get_contents($_FILES['images']['tmp_name'][0]));
        $this->assertSame('content-b', file_get_contents($_FILES['images']['tmp_name'][1]));
    }

    // ---------------------------------------------------------------------
    // fakeStorage()
    // ---------------------------------------------------------------------

    public function testFakeStorageCreatesDirectoryAndRegistersInContainer()
    {
        $dir = $this->fakeStorage();

        $this->assertDirectoryExists($dir);
        $storage = $this->container->get('storage');
        $this->assertInstanceOf(LocalStorage::class, $storage);
    }

    public function testFakeStorageReturnsAbsolutePath()
    {
        $dir = $this->fakeStorage();

        $this->assertStringStartsWith(sys_get_temp_dir(), $dir);
    }

    public function testFakeStorageIsCalledAutomaticallyByWithFiles()
    {
        $this->withFiles([
            'avatar' => ['name' => 'photo.jpg'],
        ]);

        $storage = $this->container->get('storage');
        $this->assertInstanceOf(LocalStorage::class, $storage);
    }

    // ---------------------------------------------------------------------
    // Integration – store() writes to faked storage
    // ---------------------------------------------------------------------

    public function testStoreWritesToFakedStorageDirectory()
    {
        $dir = $this->fakeStorage();

        $this->withFiles([
            'avatar' => ['name' => 'photo.jpg', 'content' => 'image-bytes'],
        ]);

        $storage = $this->container->get('storage');
        $storage->store($_FILES['avatar']['tmp_name'], 'uploads/public/photo.jpg');

        $this->assertFileExists($dir . '/uploads/public/photo.jpg');
        $this->assertSame('image-bytes', file_get_contents($dir . '/uploads/public/photo.jpg'));
    }

    // ---------------------------------------------------------------------
    // tearDownFileUploads() – cleanup
    // ---------------------------------------------------------------------

    public function testTearDownDeletesTempFiles()
    {
        $this->withFiles([
            'avatar' => ['name' => 'photo.jpg', 'content' => 'hello'],
        ]);

        $tmpName = $_FILES['avatar']['tmp_name'];
        $this->assertFileExists($tmpName);

        $this->tearDownFileUploads();

        $this->assertFileDoesNotExist($tmpName);
    }

    public function testTearDownRemovesFakeStorageDirectory()
    {
        $dir = $this->fakeStorage();
        file_put_contents($dir . '/test.txt', 'data');
        $this->assertDirectoryExists($dir);

        $this->tearDownFileUploads();

        $this->assertDirectoryDoesNotExist($dir);
    }

    public function testTearDownHandlesEmptyStateGracefully()
    {
        // No files or storage faked — should not throw.
        $this->tearDownFileUploads();

        $this->assertTrue(true);
    }

    public function testTearDownRemovesNestedDirectoriesInFakeStorage()
    {
        $dir = $this->fakeStorage();
        mkdir($dir . '/a/b/c', 0777, true);
        file_put_contents($dir . '/a/b/c/file.txt', 'nested');

        $this->tearDownFileUploads();

        $this->assertDirectoryDoesNotExist($dir);
    }
}
