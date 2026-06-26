<?php

use Lightpack\Pwa\Controllers\PwaController;

route()->post('/pwa/subscribe', PwaController::class, 'subscribe');
route()->post('/pwa/unsubscribe', PwaController::class, 'unsubscribe');
route()->get('/pwa/subscription', PwaController::class, 'status');