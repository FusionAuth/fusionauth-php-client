## FusionAuth PHP Client ![semver 2.0.0 compliant](http://img.shields.io/badge/semver-2.0.0-brightgreen.svg?style=flat-square)
If you're integrating FusionAuth with a PHP application, this library will speed up your development time.

For additional information and documentation on FusionAuth refer to [https://fusionauth.io](https://fusionauth.io).

### Example Usage

#### Install the Code

To use the client library on your project simply copy the PHP source files from the `src` directory to your project or the following
 Composer package.

Packagist

* https://packagist.org/packages/fusionauth/fusionauth-client

```bash
composer require fusionauth/fusionauth-client
```

Include composer autoloader

```PHP
require __DIR__ . '/vendor/autoload.php';
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
$request["loginId"] = "joe@fusionauth.io";
$request["password"] = "abc123";
$result = $client->login($request);
if (!$result->wasSuccessful()) {
 // Error
}

// Hooray! Success
```

## Questions and support

If you have a question or support issue regarding this client library, we'd love to hear from you.

If you have a paid edition with support included, please [open a ticket with via your account portal](https://account.fusionauth.io/account/support/). Learn more about [paid editions here](https://fusionauth.io/pricing/).

Otherwise, please [post your question in the community forum](https://fusionauth.io/community/forum/).

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/FusionAuth/fusionauth-php-client.

## License

This code is available as open source under the terms of the [Apache v2.0 License](https://opensource.org/licenses/Apache-2.0).

