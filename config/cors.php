<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    
    // URL Spesifik (Lokal & Production)
    'allowed_origins' => [
        'http://localhost:5173', 
        'http://127.0.0.1:5173',
        'https://absensi-karyawan-frontend.vercel.app',
    ], 
    
    // Regex patterns untuk Vercel dan ngrok URLs
    'allowed_origins_patterns' => [
        '#^https?://.*$#',
    ],
    
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,


];