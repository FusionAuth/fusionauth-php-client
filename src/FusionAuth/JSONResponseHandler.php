<?php
namespace FusionAuth;




class JSONResponseHandler implements ResponseHandler
{
  public function call(&$response)
  {
    return json_decode($response);
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