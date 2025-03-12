<?php

namespace Lightpack\Debug;

use Throwable;
use TypeError;
use ParseError;
use ErrorException;
use Exception;
use Lightpack\Container\Container;
use Lightpack\Debug\ExceptionRenderer;
use Lightpack\Exceptions\ValidationException;
use Lightpack\Logger\Logger;
use Lightpack\Debugger\Debug;
use Lightpack\Debugger\Output;
use Lightpack\Exceptions\HttpException;

class Handler
{
    private $logger;
    private $exceptionRenderer;
    private $environment;
    private $hasRendered = false;

    public function __construct(Logger $logger, string $environment = 'development')
    {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->exceptionRenderer = new ExceptionRenderer($environment);
    }

    public function handleError(int $code, string $message, string $file, int $line)
    {
        $exc = new ErrorException(
            $message,
            $code,
            $code,
            $file,
            $line
        );

        if ($this->environment === 'development') {
            Debug::log($message, [
                'code' => $code,
                'file' => $file,
                'line' => $line,
            ]);
        }

        $this->logAndRenderException($exc);
    }

    public function handleShutdown()
    {
        $error = error_get_last();

        if ($error) {
            $this->handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        } else if ($this->environment === 'development' && !$this->hasRendered && PHP_SAPI !== 'cli') {
            // For normal requests in development, show debug panel
            Output::render(Debug::getDebugData());
            $this->hasRendered = true;
        }
    }

    public function handleException(Throwable $exc)
    {
        if ($exc instanceof ParseError) {
            return $this->handleError(E_PARSE, "Parse error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
        }

        if ($exc instanceof TypeError) {
            return $this->handleError(E_RECOVERABLE_ERROR, "Type error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
        }

        if ($exc instanceof ValidationException) {
            return Container::getInstance()->get('redirect')->send();
        }

        if ($this->environment === 'development') {
            Debug::exception($exc);
            
            // Set appropriate HTTP status code
            $statusCode = $exc instanceof HttpException ? $exc->getCode() : 500;
            if (!headers_sent()) {
                header("HTTP/1.1 $statusCode", true, $statusCode);
            }
            
            if (!$this->hasRendered) {
                Output::render(Debug::getDebugData());
                $this->hasRendered = true;
            }
            return;
        }

        if ($exc instanceof Exception) {
            return $this->logAndRenderException($exc, 'Exception');
        }

        $this->handleError(E_ERROR, "Fatal error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
    }

    private function logAndRenderException(Throwable $exc, $type = 'Error')
    {
        $this->logger->error($exc->getMessage(), [
            'stack_trace' => [
                'file' => $exc->getFile(),
                'line' => $exc->getLine(),
                'trace' => $exc->getTraceAsString(),
            ],
        ]);

        if ($this->environment === 'development') {
            Debug::exception($exc);
            
            // Set appropriate HTTP status code
            $statusCode = $exc instanceof HttpException ? $exc->getCode() : 500;
            if (!headers_sent()) {
                header("HTTP/1.1 $statusCode", true, $statusCode);
            }
            
            if (!$this->hasRendered) {
                Output::render(Debug::getDebugData());
                $this->hasRendered = true;
            }
        } else {
            $this->exceptionRenderer->render($exc, $type);
        }
    }
}
