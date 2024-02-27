# FusionAuth PHP Client ![semver 2.0.0 compliant](http://img.shields.io/badge/semver-2.0.0-brightgreen.svg?style=flat-square)

## Intro

If you're integrating FusionAuth with a PHP application, this library will speed up your development time. Please also make sure to check our [SDK Usage Suggestions  page](https://fusionauth.io/docs/sdks/#usage-suggestions).

For additional information and documentation on FusionAuth refer to [https://fusionauth.io](https://fusionauth.io).

## Install

The most preferred way to use the client library is to install the [`fusionauth/fusionauth-client`](https://packagist.org/packages/fusionauth/fusionauth-client) package via Composer by running the command below at your project root folder.

```bash
composer require fusionauth/fusionauth-client
```

Then, include the autoloader in your PHP files.

```php
require __DIR__ . '/vendor/autoload.php';
```

## Questions and support

If you have a question or support issue regarding this client library, we'd love to hear from you.

If you have a paid edition with support included, please [open a ticket in your account portal](https://account.fusionauth.io/account/support/). Learn more about [paid editions here](https://fusionauth.io/pricing).

Otherwise, please [post your question in the community forum](https://fusionauth.io/community/forum/).

## Examples

### Create the Client

```php
$apiKey = "5a826da2-1e3a-49df-85ba-cd88575e4e9d";
$client = new FusionAuth\FusionAuthClient($apiKey, "http://localhost:9011");
```

### Create an Application

```php
$result = $client->createApplication(
    applicationId: null, // Leave this empty to automatically generate the UUID
    request: [
        'application' => [
            'name' => 'ChangeBank',
        ],
    ],
);
if (!$result->wasSuccessful()) {
    // Check HTTP Code at $result->status
    // and error message at $result->errorResponse
}

// Hooray! Success
var_dump($result->successResponse->application);
```

### Adding Roles to an Existing Application

```php
$result = $client->createApplicationRole(
    applicationId: 'd564255e-f767-466b-860d-6dcb63afe4cc', // Existing Application Id
    roleId: null, // Leave this empty to automatically generate the UUID
    request: [
        'role' => [
            'name' => 'customer',
            'description' => 'Default role for regular customers',
            'isDefault' => true,
        ],
    ],
);

if (!$result->wasSuccessful()) {
    // Check HTTP Code at $result->status
    // and error message at $result->errorResponse
}

// Hooray! Success
var_dump($result->successResponse->role);
```

### Retrieve Application Details

```php
$result = $client->retrieveApplication(
    applicationId: 'd564255e-f767-466b-860d-6dcb63afe4cc',
);

if (!$result->wasSuccessful()) {
    // Check HTTP Code at $result->status
    // and error message at $result->errorResponse
}

// Hooray! Success
var_dump($result->successResponse->application);
```

### Delete an Application

```php
$result = $client->deleteApplication(
    applicationId: 'd564255e-f767-466b-860d-6dcb63afe4cc',
);

if (!$result->wasSuccessful()) {
    // Check HTTP Code at $result->status
    // and error message at $result->errorResponse
}

// Hooray! Success
// Note that $result->successResponse will be empty
```

### Lock a User

```php
$result = $client->deactivateUser(
    'fa0bc822-793e-45ee-a7f4-04bfb6a28199',
);

if (!$result->wasSuccessful()) {
    // Check HTTP Code at $result->status
    // and error message at $result->errorResponse
}

// Hooray! Success
```

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/FusionAuth/fusionauth-php-client.

Note: if you want to change the `FusionAuthClient` class, you have to do it on the [FusionAuth Client Builder repository](https://github.com/FusionAuth/fusionauth-client-builder/blob/master/src/main/client/php.client.ftl), which is responsible for generating all client libraries we support.

## License

This code is available as open source under the terms of the [Apache v2.0 License](https://opensource.org/blog/license/apache-2-0).

