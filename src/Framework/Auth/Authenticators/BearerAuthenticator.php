<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identity;
use Lightpack\Auth\Models\AuthToken;

class BearerAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?Identity
    {
        $token = request()->bearerToken();

        if (null === $token) {
            return null;
        }

        $authToken = AuthToken::verify($token);
        
        if (!$authToken) {
            return null;
        }

        return $this->identifier->findById($authToken->getUserId());
    }
}
