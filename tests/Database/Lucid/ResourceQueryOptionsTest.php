<?php

require_once __DIR__ . '/Fixtures/RqUser.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\ResourceQuery;
use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

class ResourceQueryOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_GET = [];
        Container::getInstance()->register('request', fn () => new Request);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    // includes ----------------------------------------------------------------

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
        $this->assertEquals(1, count(array_filter($options['includes'], fn ($i) => $i === 'roles')));
    }

    // fields ------------------------------------------------------------------

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

    // transformOptions combos -------------------------------------------------

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
        $_GET['fields'] = 'name,email';
        $options = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->allowFields(['name', 'email'])
            ->transformOptions();
        $this->assertEquals(['name', 'email'], $options['fields']['self']);
        $this->assertContains('roles', $options['includes']);
    }

    // per-page ----------------------------------------------------------------

    public function testMaxPerPageCapsRequestValue()
    {
        $_GET['per_page'] = '9999';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(50);
        $m = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(50, $m->invoke($rq, null));
    }

    public function testExplicitPerPageHonouredUpToMax()
    {
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(20, $m->invoke($rq, 20));
    }

    public function testPerPageZeroBecomesOne()
    {
        $_GET['per_page'] = '0';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(1, $m->invoke($rq, null));
    }

    public function testPerPageNegativeBecomesOne()
    {
        $_GET['per_page'] = '-5';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
        $m->setAccessible(true);
        $this->assertEquals(1, $m->invoke($rq, null));
    }

    public function testLimitParamUsedWhenPerPageAbsent()
    {
        $_GET['limit'] = '25';
        $rq = ResourceQuery::for(RqUser::class)->maxPerPage(100);
        $m = (new \ReflectionClass($rq))->getMethod('resolvePerPage');
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

    // counts ------------------------------------------------------------------

    public function testAllowedCountIsRecognised()
    {
        $_GET['count'] = 'roles';
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles']);
        $m = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $this->assertEquals(['roles'], $m->invoke($rq));
    }

    public function testDisallowedCountIsIgnored()
    {
        $_GET['count'] = 'secret_relation';
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles']);
        $m = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $this->assertEmpty($m->invoke($rq));
    }

    public function testMixedCountsFilterCorrectly()
    {
        $_GET['count'] = 'roles,secret,posts';
        $rq = ResourceQuery::for(RqUser::class)->allowCounts(['roles', 'posts']);
        $m = (new \ReflectionClass($rq))->getMethod('parsedCounts');
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
        $m = (new \ReflectionClass($rq))->getMethod('parsedCounts');
        $m->setAccessible(true);
        $this->assertEmpty($m->invoke($rq));
    }

    public function testTransformOptionsIsIdempotent()
    {
        $_GET['include'] = 'roles';
        $_GET['fields'] = 'name,email';
        $rq = ResourceQuery::for(RqUser::class)
            ->allowIncludes(['roles'])
            ->allowFields(['name', 'email']);
        $first = $rq->transformOptions();
        $second = $rq->transformOptions();
        $this->assertEquals($first, $second);
    }
}
