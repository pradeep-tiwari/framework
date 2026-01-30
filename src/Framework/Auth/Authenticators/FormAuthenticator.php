<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\IdentityInterface;

class FormAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?IdentityInterface
    {
        $credentials = request()->input();

        if (empty($credentials)) {
            return null;
        }

        return $this->identifier->findByCredentials($credentials);
    }
}
