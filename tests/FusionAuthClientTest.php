<?php
namespace fusionauth;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/FusionAuthClient.php';
require_once __DIR__ . '/../src/RESTClient.php';
require_once __DIR__ . '/../src/ClientResponse.php';

/**
 * @covers FusionAuthClient
 */
final class FusionAuthClientTest extends TestCase
{
  private $applicationId;

  /**
   * @var FusionAuthClient
   */
  private $client;

  private $userId;

  public function setUp()
  {
    $this->client = new FusionAuthClient('bf69486b-4733-4470-a592-f1bfce7af580', 'http://localhost:9011');
  }

  public function tearDown()
  {
    $this->client->deleteApplication($this->applicationId);
    $this->client->deleteUser($this->userId);
  }

  public function test_applications()
  {
    // Create it
    $response = $this->client->createApplication(null, ["application" => ["name" => "PHP Client Application"]]);
    $this->handleResponse($response);
    $this->applicationId = $response->successResponse->application->id;

    // Retrieve it
    $response = $this->client->retrieveApplication($this->applicationId);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->application->name, "PHP Client Application");

    // Update it
    $response = $this->client->updateApplication($this->applicationId, [ "application" => ["name" => "PHP Client Application Updated"]]);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->application->name, "PHP Client Application Updated");

    // Retrieve it again
    $response = $this->client->retrieveApplication($this->applicationId);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->application->name, "PHP Client Application Updated");

    // Deactivate it
    $response = $this->client->deactivateApplication($this->applicationId);
    $this->handleResponse($response);

    // Retrieve it again
    $response = $this->client->retrieveApplication($this->applicationId);
    $this->handleResponse($response);
    $this->assertFalse($response->successResponse->application->active);

    // Retrieve inactive
    $response = $this->client->retrieveInactiveApplications();
    $this->assertEquals($response->successResponse->applications[0]->name, "PHP Client Application Updated");

    // Reactivate it
    $response = $this->client->reactivateApplication($this->applicationId);
    $this->handleResponse($response);

    // Retrieve it again
    $response = $this->client->retrieveApplication($this->applicationId);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->application->name, "PHP Client Application Updated");
    $this->assertTrue($response->successResponse->application->active);

    // Delete it
    $response = $this->client->deleteApplication($this->applicationId);
    $this->handleResponse($response);

    // Retrieve it again
    $response = $this->client->retrieveApplication($this->applicationId);
    $this->assertEquals($response->status, 404);

    // Retrieve inactive
    $response = $this->client->retrieveInactiveApplications();
    $this->assertFalse(isset($response->successResponse->applications));
  }

  public function test_users()
  {
    // Create it
    $response = $this->client->createUser(null, ["user" => ["email" => "test@fusionauth.io", "password" => "password", "firstName" => "Jäne"]]);
    $this->handleResponse($response);
    $this->userId = $response->successResponse->user->id;

    // Retrieve it
    $response = $this->client->retrieveUser($this->userId);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->user->email, "test@fusionauth.io");

    // Login
    $response = $this->client->login(["loginId" => "test@fusionauth.io", "password" => "password"]);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->user->email, "test@fusionauth.io");

    // Update it
    $response = $this->client->updateUser($this->userId, [ "user" => ["email" => "test+2@fusionauth.io"]]);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->user->email, "test+2@fusionauth.io");

    // Retrieve it again
    $response = $this->client->retrieveUser($this->userId);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->user->email, "test+2@fusionauth.io");

    // Deactivate it
    $response = $this->client->deactivateUser($this->userId);
    $this->handleResponse($response);

    // Retrieve it again
    $response = $this->client->retrieveUser($this->userId);
    $this->handleResponse($response);
    $this->assertFalse($response->successResponse->user->active);

    // Reactivate it
    $response = $this->client->reactivateUser($this->userId);
    $this->handleResponse($response);

    // Retrieve it again
    $response = $this->client->retrieveUser($this->userId);
    $this->handleResponse($response);
    $this->assertEquals($response->successResponse->user->email, "test+2@fusionauth.io");
    $this->assertTrue($response->successResponse->user->active);

    // Delete it
    $response = $this->client->deleteUser($this->userId);
    $this->handleResponse($response);

    // Retrieve it again
    $response = $this->client->retrieveUser($this->userId);
    $this->assertEquals($response->status, 404);
  }

  public function test_logout() {
    // Without parameter
    $response = $this->client->logout(true);
    $this->handleResponse($response);

    // With NULL
    $response = $this->client->logout(true, NULL);
    $this->handleResponse($response);

    // With bogus token
    $response = $this->client->logout(false, "token");
    $this->handleResponse($response);
  }

  /**
   * @param $response ClientResponse
   */
  private function handleResponse($response)
  {
    if (!$response->wasSuccessful()) {
      print "Status: " . $response->status . "\n";
      print json_encode($response->errorResponse, JSON_PRETTY_PRINT);
    }

    $this->assertTrue($response->wasSuccessful());
  }
}