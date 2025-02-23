<?php

namespace Lightpack\Auth;

interface Identifier
{
    public function findById($id): ?AuthIdentity;
    public function findByAuthToken(string $token): ?AuthIdentity;
    public function findByRememberToken($id, string $token): ?AuthIdentity;
    public function findByCredentials(array $credentials): ?AuthIdentity;
    public function updateLogin($id, array $fields);
}