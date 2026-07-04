<?php

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Builder;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\ResourceQuery;
use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

// ─── Shared test model fixture ───────────────────────────────────────────────

class RqUser extends Model
{
    protected $table = 'users';

    protected function scopeStatus(Builder $query, $value): void
    {
        $query->where('status', $value);
    }

    protected function scopeRole(Builder $query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('role', $value);
        } else {
            $query->where('role', $value);
        }
    }

    protected function scopeSearch(Builder $query, $value): void
    {
        $query->where('name', 'LIKE', '%' . $value . '%');
    }
}

// ─── Tests that only need request in container (no DB) ───────────────────────

class ResourceQueryOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_GET = [];
        Container::getInstance()->register('request', fn() => new Request);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    // includes ─────────────────────────────────────────────────────────────────

    public function testAllowedIncludeIsApplied()
    {
        $_GET['include'] = 'roles';
        $options = ResourceQuery::for(RqUser::class)->allowIncludes(['roles'])->transformOptions();
        $this->assertContains('roles', $options['includes']);
    }

    public function testDisallowedIncludeIsIgnored()
    {
        $_GET['include'] = 'secret_data';
        $options = ResourceQuery::for(RqUser::class)->allowIncludes(['roles'])->transformOptions();
        $this->assertEmpty($options['includes'] ?? []);
    }

    public function testMultipleIncludesAreApplied()
    {
        $_GET['include'] = 'roles,profile';
        $options = ResourceQuery::for(RqUser::class)->allowIncludes(['roles', 'profile'])->transformOptions();
        $this->assertContains('roles', $options['includes']);
        $this->assertContains('profile', $options['includes']);
    }

    public function testDefaultIncludesAppliedWithoutRequest()
    {
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->defaultIncludes(['roles'])
            ->transformOptions();
        $this->assertContains('roles', $options['includes']);
    }

    public function testDefaultIncludesMergeWithRequestIncludes()
    {
        $_GET['include'] = 'profile';
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles', 'profile'])
            ->defaultIncludes(['roles'])
            ->transformOptions();
        $this->assertContains('roles', $options['includes']);
        $this->assertContains('profile', $options['includes']);
    }

    public function testDuplicateIncludesAreDeduped()
    {
        $_GET['include'] = 'roles';
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->defaultIncludes(['roles'])
            ->transformOptions();
        $this->assertEquals(1, count(array_filter($options['includes'], fn($i) => $i === 'roles')));
    }

    // fields ───────────────────────────────────────────────────────────────────

    public function testStringFieldsFilteredByAllowList()
    {
        $_GET['fields'] = 'name,email';
        $options = ResourceQuery::for(RqUser::class)->allowFields(['name', 'email', 'created_at'])->transformOptions();
        $this->assertEquals(['name', 'email'], $options['fields']['self']);
    }

    public function testDisallowedFieldsAreExcluded()
    {
        $_GET['fields'] = 'name,password,secret';
        $options = ResourceQuery::for(RqUser::class)->allowFields(['name', 'email'])->transformOptions();
        $this->assertEquals(['name'], $options['fields']['self']);
        $this->assertNotContains('password', $options['fields']['self'] ?? []);
    }

    public function testBracketedRootFieldsByTableName()
    {
        $_GET['fields'] = ['users' => 'name,email'];
        $options = ResourceQuery::for(RqUser::class)->allowFields(['name', 'email'])->transformOptions();
        $this->assertEquals(['name', 'email'], $options['fields']['self']);
    }

    public function testRelationFieldsAcceptedForAllowedInclude()
    {
        $_GET['fields'] = ['roles' => 'name,slug'];
        $options = ResourceQuery::for(RqUser::class)
            ->allowFields(['name', 'email'])
            ->allowIncludes(['roles'])
            ->transformOptions();
        $this->assertEquals(['name', 'slug'], $options['fields']['roles']);
    }

    public function testRelationFieldsRejectedIfNotInAllowedIncludes()
    {
        $_GET['fields'] = ['secret_relation' => 'field'];
        $options = ResourceQuery::for(RqUser::class)
            ->allowFields(['name'])
            ->allowIncludes(['roles'])
            ->transformOptions();
        $this->assertArrayNotHasKey('secret_relation', $options['fields'] ?? []);
    }

    public function testNoFieldsProducesNoFieldsKey()
    {
        $options = ResourceQuery::for(RqUser::class)->allowFields(['name', 'email'])->transformOptions();
        $this->assertArrayNotHasKey('fields', $options);
    }

    // transformOptions combos ──────────────────────────────────────────────────

    public function testTransformOptionsEmptyWhenNothingRequested()
    {
        $options = ResourceQuery::for(RqUser::class)
            ->allowFilters(['status'])
            ->allowSorts(['name'])
            ->allowIncludes(['roles'])
            ->allowFields(['name'])
            ->transformOptions();
        $this->assertEmpty($options);
    }

    public function testTransformOptionsCombinesFieldsAndIncludes()
    {
        $_GET['include'] = 'roles';
        $_GET['fields']  = 'name,email';
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->allowFields(['name', 'email'])
            ->transformOptions();
        $this->assertEquals(['name', 'email'], $options['fields']['self']);
        $this->assertContains('roles', $options['includes']);
    }

    // per-page ─────────────────────────────────────────────────────────────────

    public function testMaxPerPageCapsRequestValue()
    {
        $_GET['per_page'] = '9999';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(50);
        $m  = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(50, $m->invoke($rq, null));
    }

    public function testExplicitPerPageHonouredUpToMax()
    {
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m  = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(20, $m->invoke($rq, 20));
    }

    public function testPerPageZeroBecomesOne()
    {
        $_GET['per_page'] = '0';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m  = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(1, $m->invoke($rq, null));
    }

    public function testPerPageNegativeBecomesOne()
    {
        $_GET['per_page'] = '-5';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m  = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(1, $m->invoke($rq, null));
    }

    public function testLimitParamUsedWhenPerPageAbsent()
    {
        $_GET['limit'] = '25';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m  = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(25, $m->invoke($rq, null));
    }

    public function testIncludeWithEmptySegmentsIsHandled()
    {
        $_GET['include'] = ',roles,';
        $options = ResourceQuery::for(RqUser::class)->allowIncludes(['roles'])->transformOptions();
        $this->assertContains('roles', $options['includes']);
        $this->assertEquals(1, count($options['includes']));
    }

    public function testMixedValidAndInvalidIncludesFilterCorrectly()
    {
        $_GET['include'] = 'roles,secret_data,profile';
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles', 'profile'])
            ->transformOptions();
        $this->assertContains('roles', $options['includes']);
        $this->assertContains('profile', $options['includes']);
        $this->assertNotContains('secret_data', $options['includes']);
        $this->assertEquals(2, count($options['includes']));
    }

    public function testDefaultIncludesLoadEvenIfNotInAllowedIncludes()
    {
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->defaultIncludes(['profile'])
            ->transformOptions();
        $this->assertContains('profile', $options['includes']);
    }

    // counts ───────────────────────────────────────────────────────────────────

    public function testAllowedCountIsRecognised()
    {
        $_GET['count'] = 'roles';
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles']);
        $m  = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $this->assertEquals(['roles'], $m->invoke($rq));
    }

    public function testDisallowedCountIsIgnored()
    {
        $_GET['count'] = 'secret_relation';
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles']);
        $m  = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $this->assertEmpty($m->invoke($rq));
    }

    public function testMixedCountsFilterCorrectly()
    {
        $_GET['count'] = 'roles,secret,posts';
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles', 'posts']);
        $m  = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $result = $m->invoke($rq);
        $this->assertContains('roles', $result);
        $this->assertContains('posts', $result);
        $this->assertNotContains('secret', $result);
    }

    public function testNonStringCountParamIsIgnored()
    {
        $_GET['count'] = ['roles', 'posts'];
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles']);
        $m  = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $this->assertEmpty($m->invoke($rq));
    }

    public function testTransformOptionsIsIdempotent()
    {
        $_GET['include'] = 'roles';
        $_GET['fields']  = 'name,email';
        $rq = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->allowFields(['name', 'email']);
        $first  = $rq->transformOptions();
        $second = $rq->transformOptions();
        $this->assertEquals($first, $second);
    }
}

// ─── Tests that need MySQL DB (builder / SQL generation) ─────────────────────

class ResourceQueryBuilderTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $_GET = [];

        $config   = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql      = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt     = $this->db->query($sql);
        $stmt->closeCursor();

        $container = Container::getInstance();

        $container->register('db', fn() => $this->db);

        $container->register('request', fn() => new Request);

        $container->register('logger', function () {
            return new class {
                public function error($m, $c = []) {}
                public function critical($m, $c = []) {}
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

    // filters ──────────────────────────────────────────────────────────────────

    public function testAllowedFilterIsApplied()
    {
        $_GET['filter'] = ['status' => 'active'];
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertStringContainsString('WHERE `status` = ?', $query->toSql());
        $this->assertContains('active', $query->bindings);
    }

    public function testDisallowedFilterIsIgnored()
    {
        $_GET['filter'] = ['secret' => 'hack'];
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertStringNotContainsString('WHERE', $query->toSql());
    }

    public function testMultipleFiltersAreApplied()
    {
        $_GET['filter'] = ['status' => 'active', 'role' => 'admin'];
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['status', 'role'])->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('`status` = ?', $sql);
        $this->assertStringContainsString('`role` = ?', $sql);
        $this->assertEquals(['active', 'admin'], $query->bindings);
    }

    public function testArrayValueFilter()
    {
        $_GET['filter'] = ['role' => ['admin', 'editor']];
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['role'])->getBuilder();
        $this->assertStringContainsString('`role` IN (?, ?)', $query->toSql());
        $this->assertEquals(['admin', 'editor'], $query->bindings);
    }

    public function testNoFilterProducesNoWhereClause()
    {
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertEquals('SELECT * FROM `users`', $query->toSql());
    }

    public function testFilterWithNoMatchingScopeIsIgnored()
    {
        $_GET['filter'] = ['noscopeforthis' => 'value'];
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['noscopeforthis'])->getBuilder();
        $this->assertEquals('SELECT * FROM `users`', $query->toSql());
    }

    // sorts ────────────────────────────────────────────────────────────────────

    public function testAscendingSortIsApplied()
    {
        $_GET['sort'] = 'name';
        $query = ResourceQuery::for(RqUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringContainsString('ORDER BY `name` ASC', $query->toSql());
    }

    public function testDescendingSortIsApplied()
    {
        $_GET['sort'] = '-created_at';
        $query = ResourceQuery::for(RqUser::class)->allowSorts(['created_at'])->getBuilder();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $query->toSql());
    }

    public function testMultipleSortsAreApplied()
    {
        $_GET['sort'] = '-created_at,name';
        $query = ResourceQuery::for(RqUser::class)->allowSorts(['created_at', 'name'])->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $sql);
        $this->assertStringContainsString('`name` ASC', $sql);
    }

    public function testDisallowedSortIsIgnored()
    {
        $_GET['sort'] = 'password';
        $query = ResourceQuery::for(RqUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    public function testDefaultSortAppliedWhenNoRequestSort()
    {
        $query = ResourceQuery::for(RqUser::class)
            ->allowSorts(['created_at'])
            ->defaultSort('-created_at')
            ->getBuilder();
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $query->toSql());
    }

    public function testRequestSortOverridesDefaultSort()
    {
        $_GET['sort'] = 'name';
        $query = ResourceQuery::for(RqUser::class)
            ->allowSorts(['name', 'created_at'])
            ->defaultSort('-created_at')
            ->getBuilder();
        $sql = $query->toSql();
        $this->assertStringContainsString('ORDER BY `name` ASC', $sql);
        $this->assertStringNotContainsString('`created_at`', $sql);
    }

    public function testNoSortAndNoDefaultProducesNoOrderBy()
    {
        $query = ResourceQuery::for(RqUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    // combinations ─────────────────────────────────────────────────────────────

    public function testFilterAndSortCombined()
    {
        $_GET['filter'] = ['status' => 'active'];
        $_GET['sort']   = '-created_at';
        $query = ResourceQuery::for(RqUser::class)
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
        $builder = ResourceQuery::for(RqUser::class)
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
        $query = ResourceQuery::for(RqUser::class)->allowFilters(['status'])->getBuilder();
        $this->assertEquals('SELECT * FROM `users`', $query->toSql());
    }

    public function testSortWithBareDashProducesNoOrderBy()
    {
        $_GET['sort'] = '-';
        $query = ResourceQuery::for(RqUser::class)->allowSorts(['name'])->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    public function testDefaultSortWithDisallowedColumnIsIgnored()
    {
        $query = ResourceQuery::for(RqUser::class)
            ->allowSorts(['name'])
            ->defaultSort('-internal_score')
            ->getBuilder();
        $this->assertStringNotContainsString('ORDER BY', $query->toSql());
    }

    public function testGetBuilderIsIdempotent()
    {
        $rq = ResourceQuery::for(RqUser::class)->allowSorts(['name']);
        $b1 = $rq->getBuilder();
        $b2 = $rq->getBuilder();
        $this->assertSame($b1, $b2);
    }

    // counts ───────────────────────────────────────────────────────────────────

    public function testAllowedCountIsWiredIntoBuilder()
    {
        $_GET['count'] = 'roles';
        $builder = ResourceQuery::for(RqUser::class)->allowCounts(['roles'])->getBuilder();

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
        $builder = ResourceQuery::for(RqUser::class)->allowCounts(['roles'])->getBuilder();

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
        $builder = ResourceQuery::for(RqUser::class)->allowCounts(['roles', 'posts'])->getBuilder();

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
        $builder = ResourceQuery::for(RqUser::class)->allowCounts(['roles'])->getBuilder();

        $loader = (new \ReflectionClass($builder))->getProperty('relationLoader');
        $loader->setAccessible(true);
        $rl = $loader->getValue($builder);

        $prop = (new \ReflectionClass($rl))->getProperty('countIncludes');
        $prop->setAccessible(true);

        $this->assertEmpty($prop->getValue($rl));
    }
}
