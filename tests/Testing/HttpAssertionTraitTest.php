<?php

namespace Lightpack\Tests\Testing;

use Lightpack\Http\Http;
use Lightpack\Testing\HttpAssertionTrait;
use PHPUnit\Framework\TestCase;

class HttpAssertionTraitTest extends TestCase
{
    use HttpAssertionTrait;

    protected function tearDown(): void
    {
        Http::clearFakes();
        parent::tearDown();
    }

    public function testFakeReturnsStubForExactUrl()
    {
        Http::fake([
            'https://api.example.com/users' => Http::fakeResponse(['id' => 1], 201),
        ]);

        $http = (new Http)->get('https://api.example.com/users');

        $this->assertSame(201, $http->status());
        $this->assertSame(['id' => 1], $http->json());
    }

    public function testFakeMatchesGlobPattern()
    {
        Http::fake([
            'api.example.com/*' => Http::fakeResponse('matched', 202),
        ]);

        $http = (new Http)->post('https://api.example.com/emails', ['a' => 'b']);

        $this->assertSame(202, $http->status());
        $this->assertSame('matched', $http->body());
    }

    public function testFakeMatchesSubstring()
    {
        Http::fake([
            'api.example.com' => 'plain body',
        ]);

        $http = (new Http)->get('https://api.example.com/anything/here');

        $this->assertSame('plain body', $http->body());
    }

    public function testFakeStringStub()
    {
        Http::fake(['*' => 'OK']);

        $http = (new Http)->get('https://anything.test/path');

        $this->assertSame('OK', $http->body());
    }

    public function testFakeCallableStubReceivesRequest()
    {
        Http::fake([
            '*' => fn (array $request) => Http::fakeResponse($request['data'], 200),
        ]);

        $http = (new Http)->post('https://api.test/echo', ['hello' => 'world']);

        $this->assertSame(['hello' => 'world'], $http->json());
    }

    public function testFakeDefaultsTo200EmptyBodyWhenUnmatched()
    {
        Http::fake(['https://other.test' => 'nope']);

        $http = (new Http)->get('https://unmatched.test');

        $this->assertSame(200, $http->status());
        $this->assertSame('', $http->body());
    }

    public function testFakeWithNoStubsInterceptsEverything()
    {
        $this->fakeHttp();

        $http = (new Http)->get('https://api.test/users');

        $this->assertSame(200, $http->status());
        $this->assertRequestSent();
    }

    public function testRequestsAreRecorded()
    {
        Http::fake();

        (new Http)->post('https://api.test/emails', ['to' => 'a@b.c']);

        $requests = Http::recordedRequests();

        $this->assertCount(1, $requests);
        $this->assertSame('POST', $requests[0]['method']);
        $this->assertSame('https://api.test/emails', $requests[0]['url']);
        $this->assertSame(['to' => 'a@b.c'], $requests[0]['data']);
    }

    public function testGetQueryParamsAreBakedIntoRecordedUrl()
    {
        Http::fake();

        (new Http)->get('https://api.test/search', ['q' => 'php', 'page' => 2]);

        $requests = Http::recordedRequests();

        $this->assertSame('https://api.test/search?q=php&page=2', $requests[0]['url']);
    }

    public function testPostRecordsJsonContentTypeHeader()
    {
        Http::fake();

        (new Http)->post('https://api.test/data', ['x' => 1]);

        $requests = Http::recordedRequests();

        $this->assertSame('application/json', $requests[0]['headers']['Content-Type']);
    }

    public function testFakeInterceptsStream()
    {
        Http::fake(['*' => 'streamed body']);

        $chunks = [];
        (new Http)->stream('POST', 'https://api.test/stream', ['x' => 1], function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });

        $this->assertSame(['streamed body'], $chunks);
        $this->assertRequestSent();
    }

    public function testFakeStreamThrowsOnErrorStatus()
    {
        Http::fake(['*' => Http::fakeResponse('server error', 500)]);

        $this->expectException(\Exception::class);

        (new Http)->stream('GET', 'https://api.test/stream', null, fn ($chunk) => null);
    }

    public function testFakeInterceptsDownload()
    {
        Http::fake(['*' => 'file contents']);

        $path = tempnam(sys_get_temp_dir(), 'dl');
        $result = (new Http)->download('https://api.test/file.zip', $path);

        $this->assertTrue($result);
        $this->assertSame('file contents', file_get_contents($path));

        unlink($path);
    }

    public function testFakeDownloadFailsOnErrorStatus()
    {
        Http::fake(['*' => Http::fakeResponse('not found', 404)]);

        $path = tempnam(sys_get_temp_dir(), 'dl');
        $result = (new Http)->download('https://api.test/missing', $path);

        $this->assertFalse($result);

        unlink($path);
    }

    public function testClearFakesDisablesFaking()
    {
        Http::fake();
        Http::clearFakes();

        $property = new \ReflectionProperty(Http::class, 'fakeStubs');
        $property->setAccessible(true);

        $this->assertNull($property->getValue());
        $this->assertSame([], Http::recordedRequests());
    }

    public function testAssertRequestSentTo()
    {
        $this->fakeHttp();

        (new Http)->get('https://api.resend.com/emails');

        $this->assertRequestSentTo('api.resend.com');
    }

    public function testAssertRequestSentTimes()
    {
        $this->fakeHttp();

        (new Http)->get('https://api.test/one');
        (new Http)->get('https://api.test/two');

        $this->assertRequestSentTimes(2);
    }

    public function testAssertNothingSent()
    {
        $this->fakeHttp();

        $this->assertNothingSent();
    }

    public function testFluentChaining()
    {
        $this->fakeHttp();

        (new Http)->post('https://api.test/emails', ['x' => 1]);

        $this->assertRequestSent()
            ->assertRequestSentTo('api.test')
            ->assertRequestSentTimes(1);
    }
}
