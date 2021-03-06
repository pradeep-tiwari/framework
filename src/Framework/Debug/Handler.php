<?php

namespace Lightpack\Debug;

use Error;
use Throwable;
use TypeError;
use ParseError;
use ErrorException;
use Psr\Log\LoggerInterface;
use Lightpack\Debug\ExceptionRenderer;

class Handler
{
    private $logger;
    private $exceptionRenderer;

    public function __construct(
        LoggerInterface $logger,
        ExceptionRenderer $exceptionRenderer
    ) {
        $this->logger = $logger;
        $this->exceptionRenderer = $exceptionRenderer;
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

        $this->log($exc);
        $this->exceptionRenderer->render($exc, 'Error');
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
        }
    }

    public function handleException(Throwable $exc)
    {
        if ($exc instanceof Error) {
            if ($exc instanceof ParseError) {
                $this->handleError(E_PARSE, "Parse error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
            } elseif ($exc instanceof TypeError) {
                $this->handleError(E_RECOVERABLE_ERROR, "Type error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
            } else {
                $this->handleError(E_ERROR, "Fatal error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
            }

            return;
        }

        $this->exceptionRenderer->render($exc);
    }

    private function log($exc)
    {
        $this->logger->error($exc);
    }
}
