<?php
namespace FusionAuth;

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


