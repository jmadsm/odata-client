# Get started with the OData Client for PHP

See the original repository here: https://github.com/saintsystems/odata-client-php

## DSM specific additions
### Usage
#### Install package
In your ```composer.json``` add this repository.
Example ```composer.json```
```json
{
    "name": "test/test",
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jmadsm/odata-client"
        }
    ],
    "require": {
        ...
    }
    ...
}
```

Once this repository has been added, you can install this package by running the following command:
```console
composer require saintsystems/odata-client:dev-jma
```

#### Laravel config publish
```console
php artisan vendor:publish --tag=tag=odata-config --ansi
```

#### Constructing client
```php
<?php

use SaintSystems\OData\ODataClient;

$client = ODataClient::dsmFactory(
	'Tenant Company Id', 'Tenant Name', 'Tenant Base Url',
	'Tenant Username', 'Tenant Password', 'Tenant Api Version',
	false // Verify ssl
);
```

#### Using client in laravel
```php
<?php
use Illuminate\Support\Facades\App;
use SaintSystems\OData\ODataClient;

$bcClient = App::make(ODataClient::class);
```

#### Using client in laravel with specific tenant(By id)
```php
<?php

use Illuminate\Support\Facades\App;
use JmaDsm\TenantService\Client as TenantServiceClient;
use SaintSystems\OData\ODataClient;

$tenant = (App::make(TenantServiceClient::class))->getById($tenantId);
$oDataClient = ODataClient::dsmFactoryFromTenantArray($tenant, config('odata.verify_ssl'));
```

#### Getting data
```php
<?php
...
$result = $client->from('contacts')->where('E_Mail', 'contact email')->get();
```

#### Updating data
```php
...
$client->patch("contacts(Field='123456789')", [
	"Other_Field" => "test"
], "*");
```

#### Creating data
This has not been used yet

#### Deleting data
This has not been used yet
