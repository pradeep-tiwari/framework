<?php

route()->get('/fixture-users', 'App\Controllers\FixtureUserController', 'index')
    ->name('fixture.users')
    ->filter(['auth', 'csrf']);

route()->post('/fixture-login', 'FixtureAuthController', 'authenticate')
    ->filter('guest');
