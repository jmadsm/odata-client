<?php

return [
    'verify_ssl' => env('ODATA_VERIFY_SSL', true),
    'exeption_without_tenant_token' => env('ODATA_EXCEPTION_NO_TENANT_TOKEN', true),
    'tenant_token_cache_ttl' => env('ODATA_TENANT_TOKEN_CACHE_TTL', 10),
];
