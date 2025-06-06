<?php

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Logger\Drivers\NullLogger;
use Lightpack\Logger\Logger;
use Lightpack\Tags\Tag;
use Lightpack\Tags\TagsTrait;

class TagsIntegrationTest extends TestCase
{
    private $db;
    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);

        // configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
        $container->register('logger', fn() => new Logger(new NullLogger));

        // Create tables
        $this->schema = new Schema($this->db);
        $this->schema->createTable('posts', function(Table $table) {
            $table->id();
            $table->varchar('title');
            $table->timestamps();
        });
        $this->schema->createTable('tags', function(Table $table) {
            $table->id();
            $table->varchar('name');
            $table->varchar('slug');
            $table->timestamps();
        });
        $this->schema->createTable('tag_models', function(Table $table) {
            $table->column('tag_id')->type('bigint')->attribute('unsigned');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('model_type', 191);
            $table->primary(['tag_id', 'model_id', 'model_type']);
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('tag_models');
        $this->schema->dropTable('tags');
        $this->schema->dropTable('posts');
        $this->db = null;
    }

    protected function getPostModelInstance() {
        return new class extends Model {
            use TagsTrait;
            protected $table = 'posts';
            protected $primaryKey = 'id';
            protected $timestamps = true;
        };
    }

    protected function getTagModelInstance() {
        return new class extends Tag {};
    }

    protected function seedTagsData()
    {
        // Insert tags
        $this->db->table('tags')->insert([
            ['id' => 1, 'name' => 'news', 'slug' => 'news'],
            ['id' => 2, 'name' => 'featured', 'slug' => 'featured'],
            ['id' => 3, 'name' => 'opinion', 'slug' => 'opinion'],
        ]);
        // Insert posts
        $this->db->table('posts')->insert([
            ['id' => 101, 'title' => 'First Post'],
            ['id' => 102, 'title' => 'Second Post'],
            ['id' => 103, 'title' => 'Third Post'],
        ]);
        // Tag posts
        $this->db->table('tag_models')->insert([
            ['tag_id' => 1, 'model_id' => 101, 'model_type' => 'posts'],
            ['tag_id' => 2, 'model_id' => 101, 'model_type' => 'posts'],
            ['tag_id' => 2, 'model_id' => 102, 'model_type' => 'posts'],
            ['tag_id' => 3, 'model_id' => 103, 'model_type' => 'posts'],
        ]);
    }

    public function testAttachAndDetachTag()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->detachTags([1]);
        $this->assertCount(1, $post->tags()->all());
        $post->attachTags([1]);
        $this->assertCount(2, $post->tags()->all());
    }

    public function testSyncTags()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->syncTags([3]);
        $tagIds = array_column($post->tags()->all()->toArray(), 'id');
        $this->assertEquals([3], $tagIds);
    }

    public function testHasTagByIdAndSlug()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $tagIds = array_column($post->tags()->all()->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(2, $tagIds);
    }

    public function testDetachNonExistentTag()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->detachTags([9999]); // Should not error
        $this->assertCount(2, $post->tags()->all());
    }

    public function testAttachDuplicateTag()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(101);
        $post->attachTags([1]);
        $this->assertCount(2, $post->tags()->all());
    }

    public function testBulkAttachAndDetachTags()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(102);
        $post->attachTags([1, 3]);
        $tagIds = array_column($post->tags()->all()->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(2, $tagIds);
        $this->assertContains(3, $tagIds);
        $post->detachTags([1, 2]);
        $tagIds = array_column($post->tags()->all()->toArray(), 'id');
        $this->assertNotContains(1, $tagIds);
        $this->assertNotContains(2, $tagIds);
        $this->assertContains(3, $tagIds);
    }

    public function testFilterPostsByTagId()
    {
        $this->seedTagsData();
        $posts = $this->getPostModelInstance()::filters(['tags' => [2]])->all();
        $postIds = $posts->column('id');
        $this->assertContains(101, $postIds);
        $this->assertContains(102, $postIds);
        $this->assertNotContains(103, $postIds);
    }

    public function testFilterPostsByMultipleTags()
    {
        $this->seedTagsData();
        $posts = $this->getPostModelInstance()::filters(['tags' => [1,2]])->all();
        $postIds = $posts->column('id');
        $this->assertContains(101, $postIds);
        $this->assertContains(102, $postIds);
        $this->assertNotContains(103, $postIds);
    }

    public function testFilterPostsByNonExistentTag()
    {
        $this->seedTagsData();
        $posts = $this->getPostModelInstance()::filters(['tags' => [9999]])->all();
        $this->assertEmpty($posts);
    }

    public function testBulkSyncTags()
    {
        $this->seedTagsData();
        $post = $this->getPostModelInstance();
        $post->find(102);
        $post->syncTags([1, 3]);
        $tagIds = array_column($post->tags()->all()->toArray(), 'id');
        $this->assertContains(1, $tagIds);
        $this->assertContains(3, $tagIds);
        $this->assertNotContains(2, $tagIds);
    }

    public function testTaggableTypeIsolation()
    {
        $this->seedTagsData();
        // Add a different taggable type
        $this->db->table('posts')->insert([
            'id' => 201,
            'title' => 'Fake Post for Isolation',
        ]);
        $this->db->table('tag_models')->insert([
            ['tag_id' => 1, 'model_id' => 201, 'model_type' => 'other_model'],
        ]);
        $post = $this->getPostModelInstance();
        $post->find(201);
        $this->assertCount(0, $post->tags()->all());
    }
}
