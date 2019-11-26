<?php
namespace FusionAuth;

/*
 * Copyright (c) 2018-2019, FusionAuth, All Rights Reserved
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific
 * language governing permissions and limitations under the License.
 */

class RESTClient
{
  /**
   * @var BodyHandler
   */
  public $bodyHandler;

  /**
   * @var string
   */
  public $certificate;

  /**
   * @var int
   */
  public $connectTimeout = 2000;

  /**
   * @var ResponseHandler
   */
  public $errorResponseHandler;

  /**
   * @var array
   */
  public $headers = array();

  /**
   * @var string
   */
  public $key;

  /**
   * @var string
   */
  public $method;

  /**
   * @var array
   */
  public $parameters = array();

  /**
   * @var array
   */
  public $proxy;

  /**
   * @var int
   */
  public $readTimeout = 2000;

  /**
   * @var ResponseHandler
   */
  public $successResponseHandler;

  /**
   * @var string
   */
  public $url;

  public function __construct()
  {
    include_once 'ClientResponse.php';
  }

  public function authorization($key)
  {
    // Remove any Authorization headers before adding a new one.
    $this->resetAuthorizationHeaders();

    // Add the Authorization header.
    $this->headers[] = 'Authorization: ' . $key;

    return $this;
  }

  public function basicAuthorization($username, $password)
  {
    if (!is_null($username) && !is_null($password)) {
      // Remove any Authorization headers before adding a new one.
      $this->resetAuthorizationHeaders();

      // Add the Authorization header.
      $credentials = $username . ':' . $password;
      $encoded = base64_encode($credentials);
      $this->headers[] = 'Authorization: ' . 'Basic ' . $encoded;
    }

    return $this;
  }

  protected function resetAuthorizationHeaders()
  {
    $headers = [];
    foreach ($this->headers as $value) {
      if (stripos($value, "Authorization:") !== 0) {
        $headers[] = $value;
      }
    }
    $this->headers = $headers;
  }

  public function bodyHandler($bodyHandler)
  {
    $this->bodyHandler = $bodyHandler;
    return $this;
  }

  public function certificate($certificate)
  {
    $this->certificate = $certificate;
    return $this;
  }

  public function connectTimeout($connectTimeout)
  {
    $this->connectTimeout = $connectTimeout;
    return $this;
  }

  public function delete()
  {
    $this->method = 'DELETE';
    return $this;
  }

  public function errorResponseHandler($errorResponseHandler)
  {
    $this->errorResponseHandler = $errorResponseHandler;
    return $this;
  }

  public function get()
  {
    $this->method = 'GET';
    return $this;
  }

  public function go()
  {
    if (!$this->url || (bool)parse_url($this->url, PHP_URL_HOST) === FALSE) {
      throw new \Exception('You must specify a URL');
    }

    if (!$this->method) {
      throw new \Exception('You must specify a HTTP method');
    }

    $response = new ClientResponse();
    $response->request = ($this->bodyHandler != NULL) ? $this->bodyHandler->bodyObject() : NULL;
    $response->method = $this->method;

    try {
      if ($this->parameters) {
        if (substr($this->url, -1) != '?') {
          $this->url = $this->url . '?';
        }

        $parts = array();
        foreach ($this->parameters as $key => $value) {
          if (is_array($value)) {
            foreach ($value as $value2) {
              $parts[] = http_build_query(array($key => $value2));
            }
          } else {
            $parts[] = http_build_query(array($key => $value));
          }
        }
        $params = join('&', $parts);
        $this->url = $this->url . $params;
      }

      $curl = curl_init();
      if (substr($this->url, 0, 5) == 'https' && $this->certificate) {
        if ($this->certificate) {
          curl_setopt($curl, CURLOPT_SSLCERT, $this->certificate);
        }

        if ($this->key) {
          curl_setopt($curl, CURLOPT_SSLKEY, $this->key);
        }
      }

      if ($this->proxy) {
        curl_setopt($curl, CURLOPT_PROXY, $this->proxy['url']);
        if (isset($this->proxy['auth'])) {
          curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy['auth']);
        }
      }

      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
      curl_setopt($curl, CURLOPT_TIMEOUT_MS, $this->readTimeout);
      curl_setopt($curl, CURLOPT_URL, $this->url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_POST, false);
      curl_setopt($curl, CURLOPT_FAILONERROR, false);

      if ($this->method == 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
      } elseif ($this->method != 'GET') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
      }

      if ($this->bodyHandler) {
        $this->bodyHandler->setHeaders($this->headers);
      }

      curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

      if ($this->bodyHandler) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->bodyHandler->body());
      }

      $result = curl_exec($curl);

      $response->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      if ($response->status < 200 || $response->status > 299) {
        if ($result) {
          $response->errorResponse = $this->errorResponseHandler->call($result);
        }
      } else {
        if ($result) {
          $response->successResponse = $this->successResponseHandler->call($result);
        }
      }

      curl_close($curl);
      return $response;
    } catch (\Exception $e) {
      if (isset($curl)) {
        curl_close($curl);
      }

      $response->exception = $e;
      return $response;
    }
  }

  public function header($name, $value)
  {
    $this->headers[$name] = $value;
    return $this;
  }

  public function headers($headers)
  {
    $this->headers = $headers;
    return $this;
  }

  public function patch()
  {
    $this->method = 'PATCH';
    return $this;
  }

  public function post()
  {
    $this->method = 'POST';
    return $this;
  }

  public function put()
  {
    $this->method = 'PUT';
    return $this;
  }

  public function readTimeout($readTimeout)
  {
    $this->readTimeout = $readTimeout;
    return $this;
  }

  public function successResponseHandler($successResponseHandler)
  {
    $this->successResponseHandler = $successResponseHandler;
    return $this;
  }

  public function uri($uri)
  {
    if (!$this->url) {
      return $this;
    }

    if (substr($this->url, -1) == '/' && substr($uri, 1, 1) == '/') {
      $this->url = $this->url . ltrim($uri, '/');
    } else if (substr($this->url, -1) != '/' && substr($uri, 0, 1) != '/') {
      $this->url = $this->url . '/' . $uri;
    } else {
      $this->url = $this->url . $uri;
    }

    return $this;
  }

  public function url($url)
  {
    $this->url = $url;
    return $this;
  }

  public function urlParameter($name, $value)
  {
    if (!isset($value)) {
      return $this;
    }

    if (is_array($value)) {
      $this->parameters[$name] = $value;
    } else {
      if (!isset($this->parameters[$name])) {
        $this->parameters[$name] = array();
      }

      if (is_bool($value)) {
        $this->parameters[$name][] = var_export($value, true);
      } else {
        $this->parameters[$name][] = $value;
      }
    }

    return $this;
  }

  public function urlSegment($value)
  {
    if (isset($value)) {
      if (substr($this->url, -1) != '/') {
        $this->url = $this->url . '/';
      }
      $this->url = $this->url . $value;
    }
    return $this;
  }
}

interface BodyHandler
{
  /**
   * @return string The body as a string.
   */
  public function body();

  /**
   * @return mixed The body as an object (usually an array).
   */
  public function bodyObject();

  /**
   * Sets body handler specific headers (like Content-Type).
   *
   * @param array $headers The headers array to add headers to.
   */
  public function setHeaders(&$headers);
}

class FormDataBodyHandler implements BodyHandler
{
  private $body;

  private $bodyObject;

  public function __construct(&$bodyObject)
  {
    $this->bodyObject = $bodyObject;
    $this->body = http_build_query($bodyObject);
  }

  public function body()
  {
    return $this->body;
  }

  public function bodyObject()
  {
    return $this->bodyObject;
  }

  public function setHeaders(&$headers)
  {
    /* body() will return a URL encoded body, CURLOPT_POSTFIELDS will then set the header
       to ContentType: application/x-www-form-urlencoded
    */
  }
}

class JSONBodyHandler implements BodyHandler
{
  private $body;

  private $bodyObject;

  public function __construct(&$bodyObject)
  {
    $this->bodyObject = $bodyObject;
    $this->body = json_encode(array_filter($bodyObject));
  }

  public function body()
  {
    return $this->body;
  }

  public function bodyObject()
  {
    return $this->bodyObject;
  }

  public function setHeaders(&$headers)
  {
    $headers[] = 'Content-Length: ' . strlen($this->body);
    $headers[] = 'Content-Type: application/json';
  }
}

interface ResponseHandler
{
  /**
   * Handles the HTTP response.
   *
   * @param string $response The HTTP response as a String.
   * @return mixed The response as an object.
   */
  public function call(&$response);
}

class JSONResponseHandler implements ResponseHandler
{
  public function call(&$response)
  {
    return json_decode($response);
  }
}
