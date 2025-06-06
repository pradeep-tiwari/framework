<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;

class SignedFilter implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        if ($request->hasInValidSignature()) {
            if ($request->expectsJson()) {
                return response()->setStatus(403)->json([
                    'error' => 'Invalid URL signature'
                ]);
            }

            return response()->setStatus(403)->view('errors/403');
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}
