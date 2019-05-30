## FusionAuth PHP Client ![semver 2.0.0 compliant](http://img.shields.io/badge/semver-2.0.0-brightgreen.svg?style=flat-square)
If you're integrating FusionAuth with a PHP application, this library will speed up your development time.

For additional information and documentation on FusionAuth refer to [https://fusionauth.io](https://fusionauth.io).

### Examples Usages:

#### Install the Code

To use the client library on your project simply copy the PHP source files from the `src` directory to your project or the following
 Composer package.

Packagist

* https://packagist.org/packages/fusionauth/fusionauth-client

```bash
composer require fusionauth/fusionauth-client
```

#### Create the Client

```PHP
$apiKey = "5a826da2-1e3a-49df-85ba-cd88575e4e9d";
$client = new FusionAuth\FusionAuthClient($apiKey, "http://localhost:9011");
```

#### Login a user

```PHP
$applicationId = "68364852-7a38-4e15-8c48-394eceafa601";

$request = array();
$request["applicationId"] = $applicationId;
$request["email"] = "joe@fusionauth.io";
$request["password"] = "abc123";
$result = client->login(json_encode($request));
if (!$result->wasSuccessful()) {
 // Error
}

// Hooray! Success
```
