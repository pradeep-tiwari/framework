<?php

namespace Lightpack\ShortUrl;

use Lightpack\Http\Request;
use Lightpack\Http\Response;

class ShortUrlController
{
    public function __construct(
        protected Request $request,
        protected Response $response
    ) {
    }

    public function redirect(string $code)
    {
        $shortUrl = ShortUrl::query()->where('code', $code)->one();

        if (! $shortUrl || $shortUrl->isExpired()) {
            return $this->response->setStatus(404)->setBody('Not found');
        }

        $shortUrl->recordClick();

        return redirect()->to($shortUrl->url);
    }
}
