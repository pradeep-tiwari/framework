<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\AuthIdentity;

class FormAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?AuthIdentity
    {
        $credentials = request()->input();

        if (empty($credentials)) {
            return null;
        }

        return $this->identifier->findByCredentials($credentials);
    }
}
