<?php

require_once __DIR__ . '/Fixtures/ResourceQueryUser.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\ResourceQuery;
use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

class ResourceQueryBuilderTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $_GET = [];

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        $container = Container::getInstance();

        $container->register('db', fn () => $this->db);

        $container->register('request', fn () => new Request);

        $container->register('logger', function () {
            return new class {
                public function error($m, $c = [])
                {
                }

                public function critical($m, $c = [])
                {
                }
            };
        });

        $_SERVER['REQUEST_URI'] = '';
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $this->db->query('DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations');
        $this->db = null;
        parent::tearDown();
    }

    // filters -----------------------------------------------------------------

    public function testAllowedFilterIsApplied()
    {
        $_GET['filter'] = ['status' => 'active'];
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertStringContainsString('WHERE `status` = ?', $query->toSql());
        $this->assertContains('active', $query->bindings);
    }

    public function testDisallowedFilterIsIgnored()
    {
        $_GET['filter'] = ['secret' => 'hack'];
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertStringNotContainsString('WHERE', $query->toSql());
    }

    public function testMultipleFiltersAreApplied()
    {
        $_GET['filter'] = ['status' => 'active', 'role' => 'admin'];
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['status', 'role'])->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('`status` = ?', $sql);
        $this->assertStringContainsString('`role` = ?', $sql);
        $this->assertEquals(['active', 'admin'], $query->bindings);
    }

    public function testArrayValueFilter()
    {
        $_GET['filter'] = ['role' => ['admin', 'editor']];
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['role'])->getBuilder();
        $this->assertStringContainsString('`role` IN (?, ?)', $query->toSql());
        $this->assertEquals(['admin', 'editor'], $query->bindings);
    }

    public function testNoFilterProducesNoWhereClause()
    {
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertEquals('SELECT * FROM `users`', $query->toSql());
    }

    public function testFilterWithNoMatchingScopeIsIgnored()
    {
        $_GET['filter'] = ['noscopeforthis' => 'value'];
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['noscopeforthis'])->getBuilder();
        $this->assertEquals('SELECT * FROM `users`', $query->toSql());
    }

    // sorts -------------------------------------------------------------------

    public function testAscendingSortIsApplied()
    {
        $_GET['sort'] = 'name';
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringContainsString('ORDER BY `name` ASC', $query->toSql());
    }

    public function testDescendingSortIsApplied()
    {
        $_GET['sort'] = '-created_at';
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['created_at'])->getBuilder();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $query->toSql());
    }

    public function testMultipleSortsAreApplied()
    {
        $_GET['sort'] = '-created_at,name';
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['created_at', 'name'])->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $sql);
        $this->assertStringContainsString('`name` ASC', $sql);
    }

    public function testDisallowedSortIsIgnored()
    {
        $_GET['sort'] = 'password';
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    public function testDefaultSortAppliedWhenNoRequestSort()
    {
        $query = ResourceQuery::for(ResourceQueryUser::class)
            ->allowSorts(['created_at'])
            ->defaultSort('-created_at')
            ->getBuilder();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $query->toSql());
    }

    public function testRequestSortOverridesDefaultSort()
    {
        $_GET['sort'] = 'name';
        $query = ResourceQuery::for(ResourceQueryUser::class)
            ->allowSorts(['name', 'created_at'])
            ->defaultSort('-created_at')
            ->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('ORDER BY `name` ASC', $sql);
        $this->assertStringNotContainsString('`created_at`', $sql);
    }

    public function testNoSortAndNoDefaultProducesNoOrderBy()
    {
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    // combinations ------------------------------------------------------------

    public function testFilterAndSortCombined()
    {
        $_GET['filter'] = ['status' => 'active'];
        $_GET['sort'] = '-created_at';
        $query = ResourceQuery::for(ResourceQueryUser::class)
            ->allowFilters(['status'])
            ->allowSorts(['created_at'])
            ->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('`status` = ?', $sql);
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $sql);
    }

    public function testBuilderCanBeExtendedAfterGetBuilder()
    {
        $_GET['filter'] = ['status' => 'active'];
        $builder = ResourceQuery::for(ResourceQueryUser::class)
            ->allowFilters(['status'])
            ->getBuilder()
            ->select('name', 'email');
        $sql = $builder->toSql();
        $this->assertStringContainsString('SELECT `name`, `email`', $sql);
        $this->assertStringContainsString('`status` = ?', $sql);
    }

    public function testNonArrayFilterStringIsIgnoredSafely()
    {
        $_GET['filter'] = 'hack_attempt';
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertEquals('SELECT * FROM `users`', $query->toSql());
    }

    public function testSortWithBareDashProducesNoOrderBy()
    {
        $_GET['sort'] = '-';
        $query = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    public function testDefaultSortWithDisallowedColumnIsIgnored()
    {
        $query = ResourceQuery::for(ResourceQueryUser::class)
            ->allowSorts(['name'])
            ->defaultSort('-internal_score')
            ->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    public function testGetBuilderIsIdempotent()
    {
        $rq = ResourceQuery::for(ResourceQueryUser::class)->allowSorts(['name']);
        $b1 = $rq->getBuilder();
        $b2 = $rq->getBuilder();
        $this->assertSame($b1, $b2);
    }

    // all / one ---------------------------------------------------------------

    public function testAllReturnsCollection()
    {
        $this->db->query("INSERT INTO users (name, active) VALUES ('Alice', 1), ('Bob', 1)");

        $collection = ResourceQuery::for(ResourceQueryUser::class)->all();

        $this->assertInstanceOf(\Lightpack\Database\Lucid\Collection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function testOneReturnsSingleModel()
    {
        $this->db->query("INSERT INTO users (name, active) VALUES ('Alice', 1)");

        $model = ResourceQuery::for(ResourceQueryUser::class)->one();

        $this->assertInstanceOf(ResourceQueryUser::class, $model);
        $this->assertEquals('Alice', $model->name);
    }

    public function testOneReturnsNullWhenEmpty()
    {
        $model = ResourceQuery::for(ResourceQueryUser::class)->one();

        $this->assertNull($model);
    }

    public function testAllWithAppliedFilter()
    {
        $this->db->query("INSERT INTO users (name, active) VALUES ('Alice', 1), ('Bob', 1), ('Charlie', 1)");

        $_GET['filter'] = ['search' => 'li'];

        $collection = ResourceQuery::for(ResourceQueryUser::class)
            ->allowFilters(['search'])
            ->all();

        $this->assertCount(2, $collection);
    }

    public function testOneWithAppliedFilter()
    {
        $this->db->query("INSERT INTO users (name, active) VALUES ('Alice', 1), ('Bob', 1)");

        $_GET['filter'] = ['search' => 'ob'];

        $model = ResourceQuery::for(ResourceQueryUser::class)
            ->allowFilters(['search'])
            ->one();

        $this->assertInstanceOf(ResourceQueryUser::class, $model);
        $this->assertEquals('Bob', $model->name);
    }

    // counts ------------------------------------------------------------------

    public function testAllowedCountIsWiredIntoBuilder()
    {
        $_GET['count'] = 'roles';
        $builder = ResourceQuery::for(ResourceQueryUser::class)->allowCounts(['roles'])->getBuilder();

        $loader = (new \ReflectionClass($builder))->getProperty('relationLoader');
        $loader->setAccessible(true);
        $rl = $loader->getValue($builder);

        $prop = (new \ReflectionClass($rl))->getProperty('countIncludes');
        $prop->setAccessible(true);

        $this->assertContains('roles', $prop->getValue($rl));
    }

    public function testDisallowedCountIsNotWiredIntoBuilder()
    {
        $_GET['count'] = 'secret';
        $builder = ResourceQuery::for(ResourceQueryUser::class)->allowCounts(['roles'])->getBuilder();

        $loader = (new \ReflectionClass($builder))->getProperty('relationLoader');
        $loader->setAccessible(true);
        $rl = $loader->getValue($builder);

        $prop = (new \ReflectionClass($rl))->getProperty('countIncludes');
        $prop->setAccessible(true);

        $this->assertEmpty($prop->getValue($rl));
    }

    public function testMultipleCountsWiredCorrectly()
    {
        $_GET['count'] = 'roles,posts,secret';
        $builder = ResourceQuery::for(ResourceQueryUser::class)->allowCounts(['roles', 'posts'])->getBuilder();

        $loader = (new \ReflectionClass($builder))->getProperty('relationLoader');
        $loader->setAccessible(true);
        $rl = $loader->getValue($builder);

        $prop = (new \ReflectionClass($rl))->getProperty('countIncludes');
        $prop->setAccessible(true);
        $counts = $prop->getValue($rl);

        $this->assertContains('roles', $counts);
        $this->assertContains('posts', $counts);
        $this->assertNotContains('secret', $counts);
    }

    public function testNoCountParamProducesNoCountIncludes()
    {
        $builder = ResourceQuery::for(ResourceQueryUser::class)->allowCounts(['roles'])->getBuilder();

        $loader = (new \ReflectionClass($builder))->getProperty('relationLoader');
        $loader->setAccessible(true);
        $rl = $loader->getValue($builder);

        $prop = (new \ReflectionClass($rl))->getProperty('countIncludes');
        $prop->setAccessible(true);

        $this->assertEmpty($prop->getValue($rl));
    }
}
