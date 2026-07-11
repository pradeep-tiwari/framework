<?php

namespace Lightpack\Tests\Http;

use Lightpack\Container\Container;
use Lightpack\Http\ApiResponseTrait;
use Lightpack\Http\Response;
use PHPUnit\Framework\TestCase;

class ControllerStub
{
    use ApiResponseTrait;
}

final class ApiResponseTraitTest extends TestCase
{
    private ControllerStub $controller;

    public function setUp(): void
    {
        Container::destroy();
        $container = Container::getInstance();
        $container->register('response', function () {
            $response = new Response;
            $response->setTestMode(true);

            return $response;
        });

        $this->controller = new ControllerStub;
    }

    public function tearDown(): void
    {
        Container::destroy();
    }

    public function testRespondSuccess()
    {
        $data = ['id' => 1, 'name' => 'John'];
        $response = $this->controller->respondSuccess($data);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json', $response->getType());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals($data, $body['data']);
        $this->assertArrayNotHasKey('message', $body);
        $this->assertArrayNotHasKey('errors', $body);
    }

    public function testRespondSuccessWithMessage()
    {
        $response = $this->controller->respondSuccess(['id' => 1], 'User created');

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('User created', $body['message']);
        $this->assertEquals(['id' => 1], $body['data']);
    }

    public function testRespondCreated()
    {
        $response = $this->controller->respondCreated(['id' => 1]);

        $this->assertEquals(201, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals(['id' => 1], $body['data']);
    }

    public function testRespondNoContent()
    {
        $response = $this->controller->respondNoContent();

        $this->assertEquals(204, $response->getStatus());
        $this->assertEquals('', $response->getBody());
        $this->assertEquals('application/json', $response->getType());
    }

    public function testRespondNotFound()
    {
        $response = $this->controller->respondNotFound();

        $this->assertEquals(404, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Resource not found.', $body['message']);
    }

    public function testRespondNotFoundWithCustomMessage()
    {
        $response = $this->controller->respondNotFound('User not found');

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('User not found', $body['message']);
    }

    public function testRespondUnauthorized()
    {
        $response = $this->controller->respondUnauthorized();

        $this->assertEquals(401, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Unauthorized.', $body['message']);
    }

    public function testRespondForbidden()
    {
        $response = $this->controller->respondForbidden();

        $this->assertEquals(403, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Forbidden.', $body['message']);
    }

    public function testRespondBadRequest()
    {
        $response = $this->controller->respondBadRequest('Invalid input', ['field' => 'Required']);

        $this->assertEquals(400, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Invalid input', $body['message']);
        $this->assertEquals(['field' => 'Required'], $body['errors']);
    }

    public function testRespondValidationError()
    {
        $errors = ['email' => ['Email is invalid'], 'name' => ['Name is required']];
        $response = $this->controller->respondValidationError($errors);

        $this->assertEquals(422, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Validation failed.', $body['message']);
        $this->assertEquals($errors, $body['errors']);
    }

    public function testRespondAccepted()
    {
        $response = $this->controller->respondAccepted(['job_id' => 123], 'Job queued');

        $this->assertEquals(202, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Job queued', $body['message']);
        $this->assertEquals(['job_id' => 123], $body['data']);
    }

    public function testRespondErrorWithDefaults()
    {
        $response = $this->controller->respondError();

        $this->assertEquals(500, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('An error occurred.', $body['message']);
        $this->assertArrayNotHasKey('data', $body);
        $this->assertArrayNotHasKey('errors', $body);
    }

    public function testRespondErrorWithCustomStatusAndErrors()
    {
        $response = $this->controller->respondError('Payment failed', 402, ['card' => 'Declined']);

        $this->assertEquals(402, $response->getStatus());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Payment failed', $body['message']);
        $this->assertEquals(['card' => 'Declined'], $body['errors']);
    }

    public function testRespondSuccessWithEmptyData()
    {
        $response = $this->controller->respondSuccess([]);

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals([], $body['data']);
    }

    public function testRespondBadRequestWithoutErrorsOmitsErrorsKey()
    {
        $response = $this->controller->respondBadRequest('Missing field');

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayNotHasKey('errors', $body);
    }
}
