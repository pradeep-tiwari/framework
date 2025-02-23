<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\AuthIdentity;
use Lightpack\Auth\Models\AuthToken;

class BearerAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?AuthIdentity
    {
        $token = request()->bearerToken();

        if (null === $token) {
            return null;
        }

        $authToken = (new AuthToken)->verify($token);
        
        if (!$authToken) {
            return null;
        }

        return $this->identifier->findById($authToken->getUserId());
    }
}
