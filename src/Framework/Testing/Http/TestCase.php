<?php

namespace Lightpack\Testing\Http;

use Lightpack\Http\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Lightpack\Auth\Identity;

class TestCase extends BaseTestCase
{
    use AssertionTrait;

    /** @var \Lightpack\Container\Container */
    protected $container;

    /** @var \Lightpack\Http\Response */
    protected $response;

    protected $isJsonRequest = false;
    
    protected $isMultipartFormdata = false;

    /** @var \Lightpack\Auth\Identity */
    protected $user;

    /**
     * Set the currently logged in user for the application.
     *
     * @param \Lightpack\Auth\Identity $user
     * @return $this
     */
    public function actingAs($user)
    {
        $this->user = $user;
        
        $_SESSION['auth_user'] = $user;
        $_SESSION['_logged_in'] = true;
        $_SESSION['_auth_id'] = $user->getId();
        
        return $this;
    }

    /**
     * Set bearer token for API authentication
     *
     * @param string $token
     * @return $this
     */
    public function withToken(string $token)
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        return $this;
    }

    /**
     * Set remember me cookie for cookie authentication
     *
     * @param \Lightpack\Auth\Identity $user
     * @param string $token
     * @return $this
     */
    public function withRememberToken($user, string $token)
    {
        $cookieValue = $user->getId() . '|' . $token;
        $_COOKIE['remember_token'] = $cookieValue;
        
        return $this;
    }

    public function request(string $method, string $route, array $params = []): Response
    {
        $method = strtoupper($method);
        $method === 'GET' ? ($_GET = $params) : ($_POST = $params);

        $_SERVER['REQUEST_URI'] = $route;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_USER_AGENT'] = 'fake-agent';

        if ($this->isJsonRequest) {
            $_SERVER['X_LIGHTPACK_RAW_INPUT'] = json_encode($params);
        }

        if ($this->isMultipartFormdata) {
            $_SERVER['X_LIGHTPACK_TEST_UPLOAD'] = true;
        }

        $this->setRequestContentType();

        return $this->response = $this->dispatchAppRequest($route);
    }

    public function requestJson(string $method, string $route, array $params = []): Response
    {
        $this->isJsonRequest = true;

        return $this->request($method, $route, $params);
    }

    protected function dispatchAppRequest(string $route): Response
    {
        $this->registerAppRequest();

        $this->container->get('router')->parse($route);

        \Lightpack\App::run($this->container);

        return $this->container->get('response');
    }

    protected function registerAppRequest()
    {
        $this->container->register('request', function () {
            return new \Lightpack\Http\Request('/');
        });
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] = $value;
        }

        return $this;
    }

    public function withCookies(array $cookies): self
    {
        foreach ($cookies as $cookie => $value) {
            $_COOKIE[$cookie] = $value;
        }

        return $this;
    }

    public function withSession(array $session): self
    {
        foreach ($session as $key => $value) {
            session()->set($key, $value);
        }

        return $this;
    }

    public function withFiles(array $files): self
    {
        $this->isMultipartFormdata = true;

        foreach ($files as $file => $value) {
            $_FILES[$file] = $value;
        }

        return $this;
    }

    public function getArrayResponse(): array
    {
        if (!$this->isJsonRequest) {
            return [];
        }

        return json_decode($this->response->getBody(), true);
    }

    protected function setRequestContentType()
    {
        if ($this->isMultipartFormdata) {
            $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
            return;
        }

        if ($this->isJsonRequest) {
            $_SERVER['CONTENT_TYPE'] = 'application/json';
            return;
        }

        $_SERVER['CONTENT_TYPE'] = 'text/html';
    }
}
