<?php

namespace Lightpack\Tests\Http;

use Lightpack\Http\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private string $jsonApi = 'https://jsonplaceholder.typicode.com';
    private string $httpBin = 'https://httpbin.org';

    public function testCanMakeGetRequest()
    {
        $client = new Client();
        $response = $client->get($this->jsonApi . '/posts/1');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->data());
    }

    public function testCanMakeGetRequestWithQueryParams()
    {
        $client = new Client();
        $response = $client->get($this->jsonApi . '/posts', [
            'userId' => 1,
            'id' => 5
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->data();
        $this->assertIsArray($data);
    }

    public function testCanMakePostRequest()
    {
        $client = new Client();
        $response = $client
            ->post($this->jsonApi . '/posts', [
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1,
            ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($response->data());
    }

    public function testCanMakePutRequest()
    {
        $client = new Client();
        $response = $client
            ->put($this->jsonApi . '/posts/1', [
                'title' => 'updated',
            ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->data();
        $this->assertEquals('updated', $data['title']);
    }

    public function testCanMakeDeleteRequest()
    {
        $client = new Client();
        $response = $client->delete($this->jsonApi . '/posts/1');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanSetCustomHeaders()
    {
        $client = new Client();
        $response = $client
            ->headers([
                'X-Custom' => 'test',
                'Accept' => 'application/json'
            ])
            ->get($this->httpBin . '/headers');
        
        $data = $response->data();
        $this->assertEquals('test', $data['headers']['X-Custom']);
    }

    public function testCanSetBearerToken()
    {
        $client = new Client();
        $response = $client
            ->token('xyz123')
            ->get($this->httpBin . '/headers');
        
        $data = $response->data();
        $this->assertEquals('Bearer xyz123', $data['headers']['Authorization']);
    }

    public function testCanUploadFile()
    {
        $client = new Client();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $response = $client
            ->files(['file' => $tempFile])
            ->post($this->httpBin . '/post');

        unlink($tempFile);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->data();
        $this->assertArrayHasKey('files', $data);
    }

    public function testCanSetTimeout()
    {
        $client = new Client();
        $response = $client
            ->timeout(5)
            ->get($this->httpBin . '/get');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanMakeInsecureRequest()
    {
        $client = new Client();
        $response = $client
            ->insecure()
            ->get($this->httpBin . '/get');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanGetResponseAsText()
    {
        $client = new Client();
        $response = $client->get($this->httpBin . '/get');
        
        $this->assertIsString($response->getText());
        $this->assertJson($response->getText());
    }

    public function testCanMakePatchRequest()
    {
        $client = new Client();
        $response = $client
            ->patch($this->jsonApi . '/posts/1', [
                'title' => 'patched'
            ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->data();
        $this->assertEquals('patched', $data['title']);
    }

    public function testReturnsErrorOnFailure()
    {
        $client = new Client();
        $response = $client->get('http://non-existent-domain-123456.com');
        
        $this->assertTrue($response->failed());
        $this->assertEquals(0, $response->getStatusCode());
    }
}
