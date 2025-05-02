<?php

declare(strict_types=1);

namespace Tests;

use FusionAuth\ClientResponse;
use FusionAuth\FusionAuthClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers FusionAuthClient
 */
final class FusionAuthClientTest extends TestCase
{
    private string $applicationId;

    private string $userId;

    private FusionAuthClient $client;

    protected function setUp(): void
    {
        $fusionauthURL = getenv('FUSIONAUTH_URL') ?: 'http://localhost:9011';
        $fusionauthApiKey = getenv('FUSIONAUTH_API_KEY') ?: 'bf69486b-4733-4470-a592-f1bfce7af580';
        $this->client = new FusionAuthClient($fusionauthApiKey, $fusionauthURL);
    }

    protected function tearDown(): void
    {
        if (isset($this->applicationId)) {
            $this->client->deleteApplication($this->applicationId);
        }
        if (isset($this->userId)) {
            $this->client->deleteUser($this->userId);
        }
    }

    /**
     * @throws \Exception
     */
    public function testCanHandleApplications(): void
    {
        // Create it
        $response = $this->client->createApplication(null, ["application" => ["name" => "PHP Client Application"]]);
        $this->handleResponse($response);
        $this->applicationId = $response->successResponse->application->id;

        // Retrieve it
        $response = $this->client->retrieveApplication($this->applicationId);
        $this->handleResponse($response);
        $this->assertEquals("PHP Client Application", $response->successResponse->application->name);

        // Update it
        $response = $this->client->updateApplication(
            $this->applicationId,
            ["application" => ["name" => "PHP Client Application Updated"]]
        );
        $this->handleResponse($response);
        $this->assertEquals("PHP Client Application Updated", $response->successResponse->application->name);

        // Retrieve it again
        $response = $this->client->retrieveApplication($this->applicationId);
        $this->handleResponse($response);
        $this->assertEquals("PHP Client Application Updated", $response->successResponse->application->name);

        // Deactivate it
        $response = $this->client->deactivateApplication($this->applicationId);
        $this->handleResponse($response);

        // Retrieve it again
        $response = $this->client->retrieveApplication($this->applicationId);
        $this->handleResponse($response);
        $this->assertFalse($response->successResponse->application->active);

        // Retrieve inactive
        $response = $this->client->retrieveInactiveApplications();
        $this->assertEquals("PHP Client Application Updated", $response->successResponse->applications[0]->name);

        // Reactivate it
        $response = $this->client->reactivateApplication($this->applicationId);
        $this->handleResponse($response);

        // Retrieve it again
        $response = $this->client->retrieveApplication($this->applicationId);
        $this->handleResponse($response);
        $this->assertEquals("PHP Client Application Updated", $response->successResponse->application->name);
        $this->assertTrue($response->successResponse->application->active);

        // Delete it
        $response = $this->client->deleteApplication($this->applicationId);
        $this->handleResponse($response);

        // Retrieve it again
        $response = $this->client->retrieveApplication($this->applicationId);
        $this->assertEquals(404, $response->status);

        // Retrieve inactive
        $response = $this->client->retrieveInactiveApplications();
        $this->assertEmpty($response->successResponse->applications);
    }

    /**
     * @throws \Exception
     */
    public function testCanHandleUsers(): void
    {
        // Create it
        $response = $this->client->createUser(
            null,
            ["user" => ["email" => "test@fusionauth.io", "password" => "password", "firstName" => "Jäne"]]
        );
        $this->handleResponse($response);
        $this->userId = $response->successResponse->user->id;

        // Retrieve it
        $response = $this->client->retrieveUser($this->userId);
        $this->handleResponse($response);
        $this->assertEquals("test@fusionauth.io", $response->successResponse->user->email);

        // retrieve by login Id (default types)
        $response = $this->client->retrieveUserByLoginId("test@fusionauth.io");
        $this->handleResponse($response);
        $this->assertEquals("test@fusionauth.io", $response->successResponse->user->email);

        // retrieve by login Id (explicit types)
        $response = $this->client->retrieveUserByLoginId("test@fusionauth.io", ["email"]);
        $this->handleResponse($response);
        $this->assertEquals("test@fusionauth.io", $response->successResponse->user->email);

        // retrieve by login Id (not found, wrong type)
        $response = $this->client->retrieveUserByLoginId("test@fusionauth.io", ["phoneNumber"]);
        $this->assertEquals(404, $response->status);

        // Login
        $response = $this->client->login(["loginId" => "test@fusionauth.io", "password" => "password"]);
        $this->handleResponse($response);
        $this->assertEquals("test@fusionauth.io", $response->successResponse->user->email);

        // Update it
        $response = $this->client->updateUser($this->userId, ["user" => ["email" => "test+2@fusionauth.io"]]);
        $this->handleResponse($response);
        $this->assertEquals("test+2@fusionauth.io", $response->successResponse->user->email);

        // Retrieve it again
        $response = $this->client->retrieveUser($this->userId);
        $this->handleResponse($response);
        $this->assertEquals("test+2@fusionauth.io", $response->successResponse->user->email);

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
        $this->assertEquals(404, $response->status);
    }

    /**
     * @throws \Exception
     */
    public function testCanLogoutWithoutRefreshToken(): void
    {
        // Without parameter
        $response = $this->client->logout(true, null);
        $this->handleResponse($response);
    }

    /**
     * @throws \Exception
     */
    public function testCanLogoutWithRefreshToken(): void
    {
        // With NULL
        $response = $this->client->logout(true, 'refresh_token');
        $this->handleResponse($response);
    }

    /**
     * @throws \Exception
     */
    public function testCanLogoutWithBogusToken(): void
    {
        // With bogus token
        $response = $this->client->logout(false, "token");
        $this->handleResponse($response);
    }

    /**
     * @param $response ClientResponse
     */
    private function handleResponse(ClientResponse $response): void
    {
        if (!$response->wasSuccessful()) {
            fwrite(STDERR, "Status: " . $response->status . PHP_EOL);
            fwrite(STDERR, json_encode($response->errorResponse, JSON_PRETTY_PRINT) . PHP_EOL);
        }

        $this->assertTrue(
            $response->wasSuccessful(),
            "Expected success. Status: {$response->status}"
        );
    }
}
