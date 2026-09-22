<?php

return [
    'number' => env('APP_VERSION', '0.1.0'),
    'released_at' => env('APP_RELEASED_AT', now()->format('Y-m-d')),
];