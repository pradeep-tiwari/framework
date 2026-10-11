<?php

namespace Lightpack\Testing;

use Lightpack\Http\Http;

trait HttpAssertionTrait
{
    /**
     * Fake outbound HTTP. $stubs maps URL patterns (exact or glob '*')
     * to a stub: string body, Http::response() array, or a callable
     * receiving the request array.
     *
     *     $this->fakeHttp(['api.example.com/*' => Http::fakeResponse(['id' => 'x'], 200)]);
     *     $this->fakeHttp(); // stub everything with 200 empty body
     */
    protected function fakeHttp(array $stubs = []): void
    {
        Http::fake($stubs);
    }

    public function assertRequestSent(): self
    {
        $this->assertNotEmpty(
            Http::recordedRequests(),
            'Expected an outbound HTTP request, but none were made.'
        );

        return $this;
    }

    /**
     * Assert some recorded request URL contained the given text.
     */
    public function assertRequestSentTo(string $url): self
    {
        foreach (Http::recordedRequests() as $request) {
            if (str_contains($request['url'], $url)) {
                $this->assertTrue(true);

                return $this;
            }
        }

        $this->fail("Expected an outbound HTTP request to '{$url}', but none matched.");

        return $this;
    }

    public function assertRequestSentTimes(int $count): self
    {
        $this->assertCount(
            $count,
            Http::recordedRequests(),
            "Expected {$count} outbound HTTP requests, but " . count(Http::recordedRequests()) . ' were made.'
        );

        return $this;
    }

    public function assertNothingSent(): self
    {
        $this->assertEmpty(
            Http::recordedRequests(),
            'Expected no outbound HTTP requests, but some were made.'
        );

        return $this;
    }
}
