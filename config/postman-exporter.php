<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Postman API Configuration
    |--------------------------------------------------------------------------
    |
    | Get your API Key from: https://web.postman.co/settings/me/api-keys
    | Get your Workspace ID from: Workspace > Info
    |
    */

    'api_key'      => env('POSTMAN_EXPORTER_API_KEY'),
    
    'workspace_id' => env('POSTMAN_EXPORTER_WORKSPACE_ID'),
];
