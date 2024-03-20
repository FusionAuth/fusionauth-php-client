# FusionAuth PHP Client ![semver 2.0.0 compliant](http://img.shields.io/badge/semver-2.0.0-brightgreen.svg?style=flat-square)

## Intro

<!--
tag::forDocSite[]
-->

If you're integrating FusionAuth with a PHP application, this library will speed up your development time. Please also make sure to check our [SDK Usage Suggestions  page](https://fusionauth.io/docs/sdks/#usage-suggestions).

For additional information and documentation on FusionAuth refer to [https://fusionauth.io](https://fusionauth.io).

## Install

The most preferred way to use the client library is to install the [`fusionauth/fusionauth-client`](https://packagist.org/packages/fusionauth/fusionauth-client) package via Composer by running the command below at your project root folder.

```bash
composer require fusionauth/fusionauth-client
```

Then, include the `composer` autoloader in your PHP files.

```php
require __DIR__ . '/vendor/autoload.php';
```

## Examples

### Set Up

First, you have to make sure you have a running FusionAuth instance. If you don't have one already, the easiest way to install FusionAuth is [via Docker](https://fusionauth.io/docs/get-started/download-and-install/docker), but there are [other ways](https://fusionauth.io/docs/get-started/download-and-install). By default, it'll be running on `localhost:9011`.

Then, you have to [create an API Key](https://fusionauth.io/docs/apis/authentication#managing-api-keys) in the admin UI to allow calling API endpoints.

You are now ready to use this library!

### Error Handling

After every request is made, you need to check for any errors and handle them. To avoid cluttering things up, we'll omit the error handling in the next examples, but you should do something like the following.

```php
// $result is the response of one of the endpoint invocations from the examples below

if (!$result->wasSuccessful()) {
    echo "Error!" . PHP_EOL;
    echo "Got HTTP {$result->status}" . PHP_EOL;
    if (isset($result->errorResponse->fieldErrors)) {
        echo "There are some errors with the payload:" . PHP_EOL;
        var_dump($result->errorResponse->fieldErrors);
    }
    if (isset($result->errorResponse->generalErrors)) {
        echo "There are some general errors:" . PHP_EOL;
        var_dump($result->errorResponse->generalErrors);
    }
}
```

### Create the Client

To make requests to the API, first you need to create a `FusionAuthClient` instance with [the API Key created](https://fusionauth.io/docs/apis/authentication#managing-api-keys) and the server address where FusionAuth is running.

```php
$client = new FusionAuth\FusionAuthClient(
    apiKey: "<paste the API Key you generated here>",
    baseURL: "http://localhost:9011", // or change this to whatever address FusionAuth is running on
);
```

### Create an Application

To create an [Application](https://fusionauth.io/docs/get-started/core-concepts/applications), use the `createApplication()` method.

```php
$result = $client->createApplication(
    applicationId: null, // Leave this empty to automatically generate the UUID
    request: [
        'application' => [
            'name' => 'ChangeBank',
        ],
    ],
);

// Handle errors as shown in the beginning of the Examples section

// Otherwise parse the successful response
var_dump($result->successResponse->application);
```

[Check the API docs for this endpoint](https://fusionauth.io/docs/apis/applications#create-an-application)

### Adding Roles to an Existing Application

To add [roles to an Application](https://fusionauth.io/docs/get-started/core-concepts/applications#roles), use `createApplicationRole()`.  

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

// Handle errors as shown in the beginning of the Examples section

// Otherwise parse the successful response
var_dump($result->successResponse->role);
```

[Check the API docs for this endpoint](https://fusionauth.io/docs/apis/applications#create-an-application-role)

### Retrieve Application Details

To fetch details about an [Application](https://fusionauth.io/docs/get-started/core-concepts/applications), use `retrieveApplication()`. 

```php
$result = $client->retrieveApplication(
    applicationId: 'd564255e-f767-466b-860d-6dcb63afe4cc',
);

// Handle errors as shown in the beginning of the Examples section

// Otherwise parse the successful response
var_dump($result->successResponse->application);
```

[Check the API docs for this endpoint](https://fusionauth.io/docs/apis/applications#retrieve-an-application)

### Delete an Application

To delete an [Application](https://fusionauth.io/docs/get-started/core-concepts/applications), use `deleteApplication()`.

```php
$result = $client->deleteApplication(
    applicationId: 'd564255e-f767-466b-860d-6dcb63afe4cc',
);

// Handle errors as shown in the beginning of the Examples section

// Otherwise parse the successful response
// Note that $result->successResponse will be empty
```

[Check the API docs for this endpoint](https://fusionauth.io/docs/apis/applications#delete-an-application)

### Lock a User

To [prevent a User from logging in](https://fusionauth.io/docs/get-started/core-concepts/users), use `deactivateUser()`. 

```php
$result = $client->deactivateUser(
    'fa0bc822-793e-45ee-a7f4-04bfb6a28199',
);

// Handle errors as shown in the beginning of the Examples section

// Otherwise parse the successful response
```

[Check the API docs for this endpoint](https://fusionauth.io/docs/apis/users#delete-a-user)

### Registering a User

To [register a User in an Application](https://fusionauth.io/docs/get-started/core-concepts/users#registrations), use `register()`.

The code below also adds a `customer` role and a custom `appBackgroundColor` property to the User Registration.

```php
$result = $client->register(
    userId: 'fa0bc822-793e-45ee-a7f4-04bfb6a28199',
    request: [
        'registration' => [
            'applicationId' => 'd564255e-f767-466b-860d-6dcb63afe4cc',
            'roles' => [
                'customer',
            ],
            'data' => [
                'appBackgroundColor' => '#096324',
            ],
        ],    
    ],
);

// Handle errors as shown in the beginning of the Examples section

// Otherwise parse the successful response
```

[Check the API docs for this endpoint](https://fusionauth.io/docs/apis/registrations#create-a-user-registration-for-an-existing-user)

<!--
end::forDocSite[]
-->

## Questions and support

If you find any bugs in this library, [please open an issue](https://github.com/FusionAuth/fusionauth-php-client/issues). Note that changes to the `FusionAuthClient` class have to be done on the [FusionAuth Client Builder repository](https://github.com/FusionAuth/fusionauth-client-builder/blob/master/src/main/client/php.client.ftl), which is responsible for generating that file.

But if you have a question or support issue, we'd love to hear from you.

If you have a paid plan with support included, please [open a ticket in your account portal](https://account.fusionauth.io/account/support/). Learn more about [paid plan here](https://fusionauth.io/pricing).

Otherwise, please [post your question in the community forum](https://fusionauth.io/community/forum/).

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/FusionAuth/fusionauth-php-client.

Note: if you want to change the `FusionAuthClient` class, you have to do it on the [FusionAuth Client Builder repository](https://github.com/FusionAuth/fusionauth-client-builder/blob/master/src/main/client/php.client.ftl), which is responsible for generating all client libraries we support.

## License

This code is available as open source under the terms of the [Apache v2.0 License](https://opensource.org/blog/license/apache-2-0).


## Upgrade Policy

This library is built automatically to keep track of the FusionAuth API, and may also receive updates with bug fixes, security patches, tests, code samples, or documentation changes.

These releases may also update dependencies, language engines, and operating systems, as we\'ll follow the deprecation and sunsetting policies of the underlying technologies that it uses.

This means that after a dependency (e.g. language, framework, or operating system) is deprecated by its maintainer, this library will also be deprecated by us, and will eventually be updated to use a newer version.
