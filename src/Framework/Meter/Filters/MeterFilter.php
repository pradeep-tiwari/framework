<?php

namespace Lightpack\Meter\Filters;

use Lightpack\Exceptions\TooManyRequestsException;
use Lightpack\Filters\FilterInterface;
use Lightpack\Http\Request;
use Lightpack\Http\Response;

class MeterFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        $name = $params[0] ?? null;
        $limit = (int) ($params[1] ?? 0);

        if (! $name || $limit <= 0) {
            throw new \InvalidArgumentException('Meter filter requires meter name and limit.');
        }

        $key = $this->resolveKey($request);
        $meter = meter("{$name}:{$key}");

        if (! $meter->consume(1, $limit)) {
            $exception = new TooManyRequestsException(
                'Meter quota exceeded.',
                429
            );

            $exception->setHeaders([
                'X-MeterLimit-Limit' => (string) $limit,
                'X-MeterLimit-Remaining' => '0',
            ]);

            throw $exception;
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }

    private function resolveKey(Request $request): string
    {
        if ($user = auth()->user()) {
            return 'user:' . $user->id;
        }

        return 'ip:' . $request->ip();
    }
}
