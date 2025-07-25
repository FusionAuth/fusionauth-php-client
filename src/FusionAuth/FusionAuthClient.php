<?php
namespace FusionAuth;

/*
 * Copyright (c) 2018-2025, FusionAuth, All Rights Reserved
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

/**
 * Client that connects to a FusionAuth server and provides access to the full set of FusionAuth APIs.
 * <p/>
 * When any method is called the return value is always a ClientResponse object. When an API call was successful, the
 * response will contain the response from the server. This might be empty or contain an success object or an error
 * object. If there was a validation error or any other type of error, this will return the Errors object in the
 * response. Additionally, if FusionAuth could not be contacted because it is down or experiencing a failure, the response
 * will contain an Exception, which could be an IOException.
 *
 * @author Brian Pontarelli
 */
class FusionAuthClient
{
  /**
   * @var string
   */
  private $apiKey;

  /**
   * @var string
   */
  private $baseURL;

  /**
   * @var string
   */
  private $tenantId;

  /**
   * @var int
   */
  public $connectTimeout = 2000;

  /**
   * @var int
   */
  public $readTimeout = 2000;

  public function __construct($apiKey, $baseURL)
  {
    include_once 'RESTClient.php';
    $this->apiKey = $apiKey;
    $this->baseURL = $baseURL;
  }

  public function withTenantId($tenantId) {
    $this->tenantId = $tenantId;
    return $this;
  }

  /**
   * Takes an action on a user. The user being actioned is called the "actionee" and the user taking the action is called the
   * "actioner". Both user ids are required in the request object.
   *
   * @param array $request The action request that includes all the information about the action being taken including
  *     the Id of the action, any options and the duration (if applicable).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function actionUser($request)
  {
    return $this->start()->uri("/api/user/action")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Activates the FusionAuth Reactor using a license Id and optionally a license text (for air-gapped deployments)
   *
   * @param array $request An optional request that contains the license text to activate Reactor (useful for air-gap deployments of FusionAuth).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function activateReactor($request)
  {
    return $this->start()->uri("/api/reactor")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Adds a user to an existing family. The family Id must be specified.
   *
   * @param string $familyId The Id of the family.
   * @param array $request The request object that contains all the information used to determine which user to add to the family.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function addUserToFamily($familyId, $request)
  {
    return $this->start()->uri("/api/user/family")
        ->urlSegment($familyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Approve a device grant.
   *
   * @param string $client_id (Optional) The unique client identifier. The client Id is the Id of the FusionAuth Application in which you are attempting to authenticate.
   * @param string $client_secret (Optional) The client secret. This value will be required if client authentication is enabled.
   * @param string $token The access token used to identify the user.
   * @param string $user_code The end-user verification code.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function approveDevice($client_id, $client_secret, $token, $user_code)
  {
    $post_data = array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'token' => $token,
      'user_code' => $user_code
    );
    return $this->start()->uri("/oauth2/device/approve")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Cancels the user action.
   *
   * @param string $actionId The action Id of the action to cancel.
   * @param array $request The action request that contains the information about the cancellation.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function cancelAction($actionId, $request)
  {
    return $this->start()->uri("/api/user/action")
        ->urlSegment($actionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Changes a user's password using the change password Id. This usually occurs after an email has been sent to the user
   * and they clicked on a link to reset their password.
   * 
   * As of version 1.32.2, prefer sending the changePasswordId in the request body. To do this, omit the first parameter, and set
   * the value in the request body.
   *
   * @param string $changePasswordId The change password Id used to find the user. This value is generated by FusionAuth once the change password workflow has been initiated.
   * @param array $request The change password request that contains all the information used to change the password.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function changePassword($changePasswordId, $request)
  {
    return $this->startAnonymous()->uri("/api/user/change-password")
        ->urlSegment($changePasswordId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Changes a user's password using their access token (JWT) instead of the changePasswordId
   * A common use case for this method will be if you want to allow the user to change their own password.
   * 
   * Remember to send refreshToken in the request body if you want to get a new refresh token when login using the returned oneTimePassword.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   * @param array $request The change password request that contains all the information used to change the password.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to changePasswordUsingJWT, use that method instead.
   */
  public function changePasswordByJWT($encodedJWT, $request)
  {
    return $this->startAnonymous()->uri("/api/user/change-password")
        ->authorization("Bearer " . $encodedJWT)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Changes a user's password using their identity (loginId and password). Using a loginId instead of the changePasswordId
   * bypasses the email verification and allows a password to be changed directly without first calling the #forgotPassword
   * method.
   *
   * @param array $request The change password request that contains all the information used to change the password.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function changePasswordByIdentity($request)
  {
    return $this->start()->uri("/api/user/change-password")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Changes a user's password using their access token (JWT) instead of the changePasswordId
   * A common use case for this method will be if you want to allow the user to change their own password.
   * 
   * Remember to send refreshToken in the request body if you want to get a new refresh token when login using the returned oneTimePassword.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   * @param array $request The change password request that contains all the information used to change the password.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function changePasswordUsingJWT($encodedJWT, $request)
  {
    return $this->startAnonymous()->uri("/api/user/change-password")
        ->authorization("Bearer " . $encodedJWT)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Check to see if the user must obtain a Trust Token Id in order to complete a change password request.
   * When a user has enabled Two-Factor authentication, before you are allowed to use the Change Password API to change
   * your password, you must obtain a Trust Token by completing a Two-Factor Step-Up authentication.
   * 
   * An HTTP status code of 400 with a general error code of [TrustTokenRequired] indicates that a Trust Token is required to make a POST request to this API.
   *
   * @param string $changePasswordId The change password Id used to find the user. This value is generated by FusionAuth once the change password workflow has been initiated.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function checkChangePasswordUsingId($changePasswordId)
  {
    return $this->startAnonymous()->uri("/api/user/change-password")
        ->urlSegment($changePasswordId)
        ->get()
        ->go();
  }

  /**
   * Check to see if the user must obtain a Trust Token Id in order to complete a change password request.
   * When a user has enabled Two-Factor authentication, before you are allowed to use the Change Password API to change
   * your password, you must obtain a Trust Token by completing a Two-Factor Step-Up authentication.
   * 
   * An HTTP status code of 400 with a general error code of [TrustTokenRequired] indicates that a Trust Token is required to make a POST request to this API.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function checkChangePasswordUsingJWT($encodedJWT)
  {
    return $this->startAnonymous()->uri("/api/user/change-password")
        ->authorization("Bearer " . $encodedJWT)
        ->get()
        ->go();
  }

  /**
   * Check to see if the user must obtain a Trust Request Id in order to complete a change password request.
   * When a user has enabled Two-Factor authentication, before you are allowed to use the Change Password API to change
   * your password, you must obtain a Trust Request Id by completing a Two-Factor Step-Up authentication.
   * 
   * An HTTP status code of 400 with a general error code of [TrustTokenRequired] indicates that a Trust Token is required to make a POST request to this API.
   *
   * @param string $loginId The loginId of the User that you intend to change the password for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function checkChangePasswordUsingLoginId($loginId)
  {
    return $this->start()->uri("/api/user/change-password")
        ->urlParameter("username", $loginId)
        ->get()
        ->go();
  }

  /**
   * Make a Client Credentials grant request to obtain an access token.
   *
   * @param string $client_id (Optional) The client identifier. The client Id is the Id of the FusionAuth Entity in which you are attempting to authenticate.
  *     This parameter is optional when Basic Authorization is used to authenticate this request.
   * @param string $client_secret (Optional) The client secret used to authenticate this request.
  *     This parameter is optional when Basic Authorization is used to authenticate this request.
   * @param string $scope (Optional) This parameter is used to indicate which target entity you are requesting access. To request access to an entity, use the format target-entity:&lt;target-entity-id&gt;:&lt;roles&gt;. Roles are an optional comma separated list.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function clientCredentialsGrant($client_id, $client_secret, $scope = NULL)
  {
    $post_data = array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'client_credentials',
      'scope' => $scope
    );
    return $this->startAnonymous()->uri("/oauth2/token")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Adds a comment to the user's account.
   *
   * @param array $request The request object that contains all the information used to create the user comment.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function commentOnUser($request)
  {
    return $this->start()->uri("/api/user/comment")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Complete a WebAuthn authentication ceremony by validating the signature against the previously generated challenge without logging the user in
   *
   * @param array $request An object containing data necessary for completing the authentication ceremony
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function completeWebAuthnAssertion($request)
  {
    return $this->startAnonymous()->uri("/api/webauthn/assert")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Complete a WebAuthn authentication ceremony by validating the signature against the previously generated challenge and then login the user in
   *
   * @param array $request An object containing data necessary for completing the authentication ceremony
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function completeWebAuthnLogin($request)
  {
    return $this->startAnonymous()->uri("/api/webauthn/login")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Complete a WebAuthn registration ceremony by validating the client request and saving the new credential
   *
   * @param array $request An object containing data necessary for completing the registration ceremony
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function completeWebAuthnRegistration($request)
  {
    return $this->start()->uri("/api/webauthn/register/complete")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an API key. You can optionally specify a unique Id for the key, if not provided one will be generated.
   * an API key can only be created with equal or lesser authority. An API key cannot create another API key unless it is granted 
   * to that API key.
   * 
   * If an API key is locked to a tenant, it can only create API Keys for that same tenant.
   *
   * @param string $keyId (Optional) The unique Id of the API key. If not provided a secure random Id will be generated.
   * @param array $request The request object that contains all the information needed to create the APIKey.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createAPIKey($keyId, $request)
  {
    return $this->start()->uri("/api/api-key")
        ->urlSegment($keyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an application. You can optionally specify an Id for the application, if not provided one will be generated.
   *
   * @param string $applicationId (Optional) The Id to use for the application. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the application.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createApplication($applicationId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a new role for an application. You must specify the Id of the application you are creating the role for.
   * You can optionally specify an Id for the role inside the ApplicationRole object itself, if not provided one will be generated.
   *
   * @param string $applicationId The Id of the application to create the role on.
   * @param string $roleId (Optional) The Id of the role. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the application role.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createApplicationRole($applicationId, $roleId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("role")
        ->urlSegment($roleId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an audit log with the message and user name (usually an email). Audit logs should be written anytime you
   * make changes to the FusionAuth database. When using the FusionAuth App web interface, any changes are automatically
   * written to the audit log. However, if you are accessing the API, you must write the audit logs yourself.
   *
   * @param array $request The request object that contains all the information used to create the audit log entry.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createAuditLog($request)
  {
    return $this->start()->uri("/api/system/audit-log")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a connector.  You can optionally specify an Id for the connector, if not provided one will be generated.
   *
   * @param string $connectorId (Optional) The Id for the connector. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the connector.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createConnector($connectorId, $request)
  {
    return $this->start()->uri("/api/connector")
        ->urlSegment($connectorId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a user consent type. You can optionally specify an Id for the consent type, if not provided one will be generated.
   *
   * @param string $consentId (Optional) The Id for the consent. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the consent.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createConsent($consentId, $request)
  {
    return $this->start()->uri("/api/consent")
        ->urlSegment($consentId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an email template. You can optionally specify an Id for the template, if not provided one will be generated.
   *
   * @param string $emailTemplateId (Optional) The Id for the template. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the email template.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createEmailTemplate($emailTemplateId, $request)
  {
    return $this->start()->uri("/api/email/template")
        ->urlSegment($emailTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an Entity. You can optionally specify an Id for the Entity. If not provided one will be generated.
   *
   * @param string $entityId (Optional) The Id for the Entity. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the Entity.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createEntity($entityId, $request)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a Entity Type. You can optionally specify an Id for the Entity Type, if not provided one will be generated.
   *
   * @param string $entityTypeId (Optional) The Id for the Entity Type. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the Entity Type.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createEntityType($entityTypeId, $request)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a new permission for an entity type. You must specify the Id of the entity type you are creating the permission for.
   * You can optionally specify an Id for the permission inside the EntityTypePermission object itself, if not provided one will be generated.
   *
   * @param string $entityTypeId The Id of the entity type to create the permission on.
   * @param string $permissionId (Optional) The Id of the permission. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the permission.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createEntityTypePermission($entityTypeId, $permissionId, $request)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->urlSegment("permission")
        ->urlSegment($permissionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a family with the user Id in the request as the owner and sole member of the family. You can optionally specify an Id for the
   * family, if not provided one will be generated.
   *
   * @param string $familyId (Optional) The Id for the family. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the family.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createFamily($familyId, $request)
  {
    return $this->start()->uri("/api/user/family")
        ->urlSegment($familyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a form.  You can optionally specify an Id for the form, if not provided one will be generated.
   *
   * @param string $formId (Optional) The Id for the form. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the form.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createForm($formId, $request)
  {
    return $this->start()->uri("/api/form")
        ->urlSegment($formId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a form field.  You can optionally specify an Id for the form, if not provided one will be generated.
   *
   * @param string $fieldId (Optional) The Id for the form field. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the form field.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createFormField($fieldId, $request)
  {
    return $this->start()->uri("/api/form/field")
        ->urlSegment($fieldId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a group. You can optionally specify an Id for the group, if not provided one will be generated.
   *
   * @param string $groupId (Optional) The Id for the group. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the group.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createGroup($groupId, $request)
  {
    return $this->start()->uri("/api/group")
        ->urlSegment($groupId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a member in a group.
   *
   * @param array $request The request object that contains all the information used to create the group member(s).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createGroupMembers($request)
  {
    return $this->start()->uri("/api/group/member")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an IP Access Control List. You can optionally specify an Id on this create request, if one is not provided one will be generated.
   *
   * @param string $accessControlListId (Optional) The Id for the IP Access Control List. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the IP Access Control List.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createIPAccessControlList($accessControlListId, $request)
  {
    return $this->start()->uri("/api/ip-acl")
        ->urlSegment($accessControlListId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an identity provider. You can optionally specify an Id for the identity provider, if not provided one will be generated.
   *
   * @param string $identityProviderId (Optional) The Id of the identity provider. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the identity provider.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createIdentityProvider($identityProviderId, $request)
  {
    return $this->start()->uri("/api/identity-provider")
        ->urlSegment($identityProviderId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a Lambda. You can optionally specify an Id for the lambda, if not provided one will be generated.
   *
   * @param string $lambdaId (Optional) The Id for the lambda. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the lambda.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createLambda($lambdaId, $request)
  {
    return $this->start()->uri("/api/lambda")
        ->urlSegment($lambdaId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates an message template. You can optionally specify an Id for the template, if not provided one will be generated.
   *
   * @param string $messageTemplateId (Optional) The Id for the template. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the message template.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createMessageTemplate($messageTemplateId, $request)
  {
    return $this->start()->uri("/api/message/template")
        ->urlSegment($messageTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a messenger.  You can optionally specify an Id for the messenger, if not provided one will be generated.
   *
   * @param string $messengerId (Optional) The Id for the messenger. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the messenger.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createMessenger($messengerId, $request)
  {
    return $this->start()->uri("/api/messenger")
        ->urlSegment($messengerId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a new custom OAuth scope for an application. You must specify the Id of the application you are creating the scope for.
   * You can optionally specify an Id for the OAuth scope on the URL, if not provided one will be generated.
   *
   * @param string $applicationId The Id of the application to create the OAuth scope on.
   * @param string $scopeId (Optional) The Id of the OAuth scope. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the OAuth OAuth scope.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createOAuthScope($applicationId, $scopeId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("scope")
        ->urlSegment($scopeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a tenant. You can optionally specify an Id for the tenant, if not provided one will be generated.
   *
   * @param string $tenantId (Optional) The Id for the tenant. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the tenant.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createTenant($tenantId, $request)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a Theme. You can optionally specify an Id for the theme, if not provided one will be generated.
   *
   * @param string $themeId (Optional) The Id for the theme. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the theme.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createTheme($themeId, $request)
  {
    return $this->start()->uri("/api/theme")
        ->urlSegment($themeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a user. You can optionally specify an Id for the user, if not provided one will be generated.
   *
   * @param string $userId (Optional) The Id for the user. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createUser($userId, $request)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a user action. This action cannot be taken on a user until this call successfully returns. Anytime after
   * that the user action can be applied to any user.
   *
   * @param string $userActionId (Optional) The Id for the user action. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the user action.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createUserAction($userActionId, $request)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a user reason. This user action reason cannot be used when actioning a user until this call completes
   * successfully. Anytime after that the user action reason can be used.
   *
   * @param string $userActionReasonId (Optional) The Id for the user action reason. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the user action reason.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createUserActionReason($userActionReasonId, $request)
  {
    return $this->start()->uri("/api/user-action-reason")
        ->urlSegment($userActionReasonId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a single User consent.
   *
   * @param string $userConsentId (Optional) The Id for the User consent. If not provided a secure random UUID will be generated.
   * @param array $request The request that contains the user consent information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createUserConsent($userConsentId, $request)
  {
    return $this->start()->uri("/api/user/consent")
        ->urlSegment($userConsentId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Link an external user from a 3rd party identity provider to a FusionAuth user.
   *
   * @param array $request The request object that contains all the information used to link the FusionAuth user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createUserLink($request)
  {
    return $this->start()->uri("/api/identity-provider/link")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Creates a webhook. You can optionally specify an Id for the webhook, if not provided one will be generated.
   *
   * @param string $webhookId (Optional) The Id for the webhook. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the webhook.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function createWebhook($webhookId, $request)
  {
    return $this->start()->uri("/api/webhook")
        ->urlSegment($webhookId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Deactivates the application with the given Id.
   *
   * @param string $applicationId The Id of the application to deactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deactivateApplication($applicationId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->delete()
        ->go();
  }

  /**
   * Deactivates the FusionAuth Reactor.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deactivateReactor()
  {
    return $this->start()->uri("/api/reactor")
        ->delete()
        ->go();
  }

  /**
   * Deactivates the user with the given Id.
   *
   * @param string $userId The Id of the user to deactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deactivateUser($userId)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->delete()
        ->go();
  }

  /**
   * Deactivates the user action with the given Id.
   *
   * @param string $userActionId The Id of the user action to deactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deactivateUserAction($userActionId)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->delete()
        ->go();
  }

  /**
   * Deactivates the users with the given Ids.
   *
   * @param array $userIds The ids of the users to deactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to deactivateUsersByIds, use that method instead.
   */
  public function deactivateUsers($userIds)
  {
    return $this->start()->uri("/api/user/bulk")
        ->urlParameter("userId", $userIds)
        ->urlParameter("dryRun", false)
        ->urlParameter("hardDelete", false)
        ->delete()
        ->go();
  }

  /**
   * Deactivates the users with the given Ids.
   *
   * @param array $userIds The ids of the users to deactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deactivateUsersByIds($userIds)
  {
    return $this->start()->uri("/api/user/bulk")
        ->urlParameter("userId", $userIds)
        ->urlParameter("dryRun", false)
        ->urlParameter("hardDelete", false)
        ->delete()
        ->go();
  }

  /**
   * Deletes the API key for the given Id.
   *
   * @param string $keyId The Id of the authentication API key to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteAPIKey($keyId)
  {
    return $this->start()->uri("/api/api-key")
        ->urlSegment($keyId)
        ->delete()
        ->go();
  }

  /**
   * Hard deletes an application. This is a dangerous operation and should not be used in most circumstances. This will
   * delete the application, any registrations for that application, metrics and reports for the application, all the
   * roles for the application, and any other data associated with the application. This operation could take a very
   * long time, depending on the amount of data in your database.
   *
   * @param string $applicationId The Id of the application to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteApplication($applicationId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlParameter("hardDelete", true)
        ->delete()
        ->go();
  }

  /**
   * Hard deletes an application role. This is a dangerous operation and should not be used in most circumstances. This
   * permanently removes the given role from all users that had it.
   *
   * @param string $applicationId The Id of the application that the role belongs to.
   * @param string $roleId The Id of the role to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteApplicationRole($applicationId, $roleId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("role")
        ->urlSegment($roleId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the connector for the given Id.
   *
   * @param string $connectorId The Id of the connector to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteConnector($connectorId)
  {
    return $this->start()->uri("/api/connector")
        ->urlSegment($connectorId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the consent for the given Id.
   *
   * @param string $consentId The Id of the consent to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteConsent($consentId)
  {
    return $this->start()->uri("/api/consent")
        ->urlSegment($consentId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the email template for the given Id.
   *
   * @param string $emailTemplateId The Id of the email template to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteEmailTemplate($emailTemplateId)
  {
    return $this->start()->uri("/api/email/template")
        ->urlSegment($emailTemplateId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the Entity for the given Id.
   *
   * @param string $entityId The Id of the Entity to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteEntity($entityId)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->delete()
        ->go();
  }

  /**
   * Deletes an Entity Grant for the given User or Entity.
   *
   * @param string $entityId The Id of the Entity that the Entity Grant is being deleted for.
   * @param string $recipientEntityId (Optional) The Id of the Entity that the Entity Grant is for.
   * @param string $userId (Optional) The Id of the User that the Entity Grant is for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteEntityGrant($entityId, $recipientEntityId, $userId = NULL)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->urlSegment("grant")
        ->urlParameter("recipientEntityId", $recipientEntityId)
        ->urlParameter("userId", $userId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the Entity Type for the given Id.
   *
   * @param string $entityTypeId The Id of the Entity Type to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteEntityType($entityTypeId)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->delete()
        ->go();
  }

  /**
   * Hard deletes a permission. This is a dangerous operation and should not be used in most circumstances. This
   * permanently removes the given permission from all grants that had it.
   *
   * @param string $entityTypeId The Id of the entityType the the permission belongs to.
   * @param string $permissionId The Id of the permission to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteEntityTypePermission($entityTypeId, $permissionId)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->urlSegment("permission")
        ->urlSegment($permissionId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the form for the given Id.
   *
   * @param string $formId The Id of the form to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteForm($formId)
  {
    return $this->start()->uri("/api/form")
        ->urlSegment($formId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the form field for the given Id.
   *
   * @param string $fieldId The Id of the form field to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteFormField($fieldId)
  {
    return $this->start()->uri("/api/form/field")
        ->urlSegment($fieldId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the group for the given Id.
   *
   * @param string $groupId The Id of the group to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteGroup($groupId)
  {
    return $this->start()->uri("/api/group")
        ->urlSegment($groupId)
        ->delete()
        ->go();
  }

  /**
   * Removes users as members of a group.
   *
   * @param array $request The member request that contains all the information used to remove members to the group.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteGroupMembers($request)
  {
    return $this->start()->uri("/api/group/member")
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Deletes the IP Access Control List for the given Id.
   *
   * @param string $ipAccessControlListId The Id of the IP Access Control List to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteIPAccessControlList($ipAccessControlListId)
  {
    return $this->start()->uri("/api/ip-acl")
        ->urlSegment($ipAccessControlListId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the identity provider for the given Id.
   *
   * @param string $identityProviderId The Id of the identity provider to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteIdentityProvider($identityProviderId)
  {
    return $this->start()->uri("/api/identity-provider")
        ->urlSegment($identityProviderId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the key for the given Id.
   *
   * @param string $keyId The Id of the key to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteKey($keyId)
  {
    return $this->start()->uri("/api/key")
        ->urlSegment($keyId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the lambda for the given Id.
   *
   * @param string $lambdaId The Id of the lambda to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteLambda($lambdaId)
  {
    return $this->start()->uri("/api/lambda")
        ->urlSegment($lambdaId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the message template for the given Id.
   *
   * @param string $messageTemplateId The Id of the message template to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteMessageTemplate($messageTemplateId)
  {
    return $this->start()->uri("/api/message/template")
        ->urlSegment($messageTemplateId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the messenger for the given Id.
   *
   * @param string $messengerId The Id of the messenger to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteMessenger($messengerId)
  {
    return $this->start()->uri("/api/messenger")
        ->urlSegment($messengerId)
        ->delete()
        ->go();
  }

  /**
   * Hard deletes a custom OAuth scope.
   * OAuth workflows that are still requesting the deleted OAuth scope may fail depending on the application's unknown scope policy.
   *
   * @param string $applicationId The Id of the application that the OAuth scope belongs to.
   * @param string $scopeId The Id of the OAuth scope to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteOAuthScope($applicationId, $scopeId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("scope")
        ->urlSegment($scopeId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the user registration for the given user and application.
   *
   * @param string $userId The Id of the user whose registration is being deleted.
   * @param string $applicationId The Id of the application to remove the registration for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteRegistration($userId, $applicationId)
  {
    return $this->start()->uri("/api/user/registration")
        ->urlSegment($userId)
        ->urlSegment($applicationId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the user registration for the given user and application along with the given JSON body that contains the event information.
   *
   * @param string $userId The Id of the user whose registration is being deleted.
   * @param string $applicationId The Id of the application to remove the registration for.
   * @param array $request The request body that contains the event information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteRegistrationWithRequest($userId, $applicationId, $request)
  {
    return $this->start()->uri("/api/user/registration")
        ->urlSegment($userId)
        ->urlSegment($applicationId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Deletes the tenant based on the given Id on the URL. This permanently deletes all information, metrics, reports and data associated
   * with the tenant and everything under the tenant (applications, users, etc).
   *
   * @param string $tenantId The Id of the tenant to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteTenant($tenantId)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the tenant for the given Id asynchronously.
   * This method is helpful if you do not want to wait for the delete operation to complete.
   *
   * @param string $tenantId The Id of the tenant to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteTenantAsync($tenantId)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->urlParameter("async", true)
        ->delete()
        ->go();
  }

  /**
   * Deletes the tenant based on the given request (sent to the API as JSON). This permanently deletes all information, metrics, reports and data associated
   * with the tenant and everything under the tenant (applications, users, etc).
   *
   * @param string $tenantId The Id of the tenant to delete.
   * @param array $request The request object that contains all the information used to delete the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteTenantWithRequest($tenantId, $request)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Deletes the theme for the given Id.
   *
   * @param string $themeId The Id of the theme to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteTheme($themeId)
  {
    return $this->start()->uri("/api/theme")
        ->urlSegment($themeId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the user for the given Id. This permanently deletes all information, metrics, reports and data associated
   * with the user.
   *
   * @param string $userId The Id of the user to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteUser($userId)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->urlParameter("hardDelete", true)
        ->delete()
        ->go();
  }

  /**
   * Deletes the user action for the given Id. This permanently deletes the user action and also any history and logs of
   * the action being applied to any users.
   *
   * @param string $userActionId The Id of the user action to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteUserAction($userActionId)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->urlParameter("hardDelete", true)
        ->delete()
        ->go();
  }

  /**
   * Deletes the user action reason for the given Id.
   *
   * @param string $userActionReasonId The Id of the user action reason to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteUserActionReason($userActionReasonId)
  {
    return $this->start()->uri("/api/user-action-reason")
        ->urlSegment($userActionReasonId)
        ->delete()
        ->go();
  }

  /**
   * Remove an existing link that has been made from a 3rd party identity provider to a FusionAuth user.
   *
   * @param string $identityProviderId The unique Id of the identity provider.
   * @param string $identityProviderUserId The unique Id of the user in the 3rd party identity provider to unlink.
   * @param string $userId The unique Id of the FusionAuth user to unlink.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteUserLink($identityProviderId, $identityProviderUserId, $userId)
  {
    return $this->start()->uri("/api/identity-provider/link")
        ->urlParameter("identityProviderId", $identityProviderId)
        ->urlParameter("identityProviderUserId", $identityProviderUserId)
        ->urlParameter("userId", $userId)
        ->delete()
        ->go();
  }

  /**
   * Deletes the user based on the given request (sent to the API as JSON). This permanently deletes all information, metrics, reports and data associated
   * with the user.
   *
   * @param string $userId The Id of the user to delete (required).
   * @param array $request The request object that contains all the information used to delete the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteUserWithRequest($userId, $request)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Deletes the users with the given Ids, or users matching the provided JSON query or queryString.
   * The order of preference is Ids, query and then queryString, it is recommended to only provide one of the three for the request.
   * 
   * This method can be used to deactivate or permanently delete (hard-delete) users based upon the hardDelete boolean in the request body.
   * Using the dryRun parameter you may also request the result of the action without actually deleting or deactivating any users.
   *
   * @param array $request The UserDeleteRequest.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to deleteUsersByQuery, use that method instead.
   */
  public function deleteUsers($request)
  {
    return $this->start()->uri("/api/user/bulk")
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Deletes the users with the given Ids, or users matching the provided JSON query or queryString.
   * The order of preference is Ids, query and then queryString, it is recommended to only provide one of the three for the request.
   * 
   * This method can be used to deactivate or permanently delete (hard-delete) users based upon the hardDelete boolean in the request body.
   * Using the dryRun parameter you may also request the result of the action without actually deleting or deactivating any users.
   *
   * @param array $request The UserDeleteRequest.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteUsersByQuery($request)
  {
    return $this->start()->uri("/api/user/bulk")
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Deletes the WebAuthn credential for the given Id.
   *
   * @param string $id The Id of the WebAuthn credential to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteWebAuthnCredential($id)
  {
    return $this->start()->uri("/api/webauthn")
        ->urlSegment($id)
        ->delete()
        ->go();
  }

  /**
   * Deletes the webhook for the given Id.
   *
   * @param string $webhookId The Id of the webhook to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function deleteWebhook($webhookId)
  {
    return $this->start()->uri("/api/webhook")
        ->urlSegment($webhookId)
        ->delete()
        ->go();
  }

  /**
   * Disable two-factor authentication for a user.
   *
   * @param string $userId The Id of the User for which you're disabling two-factor authentication.
   * @param string $methodId The two-factor method identifier you wish to disable
   * @param string $code The two-factor code used verify the the caller knows the two-factor secret.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function disableTwoFactor($userId, $methodId, $code)
  {
    return $this->start()->uri("/api/user/two-factor")
        ->urlSegment($userId)
        ->urlParameter("methodId", $methodId)
        ->urlParameter("code", $code)
        ->delete()
        ->go();
  }

  /**
   * Disable two-factor authentication for a user using a JSON body rather than URL parameters.
   *
   * @param string $userId The Id of the User for which you're disabling two-factor authentication.
   * @param array $request The request information that contains the code and methodId along with any event information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function disableTwoFactorWithRequest($userId, $request)
  {
    return $this->start()->uri("/api/user/two-factor")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Enable two-factor authentication for a user.
   *
   * @param string $userId The Id of the user to enable two-factor authentication.
   * @param array $request The two-factor enable request information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function enableTwoFactor($userId, $request)
  {
    return $this->start()->uri("/api/user/two-factor")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Exchanges an OAuth authorization code for an access token.
   * Makes a request to the Token endpoint to exchange the authorization code returned from the Authorize endpoint for an access token.
   *
   * @param string $code The authorization code returned on the /oauth2/authorize response.
   * @param string $client_id (Optional) The unique client identifier. The client Id is the Id of the FusionAuth Application in which you are attempting to authenticate.
  *     This parameter is optional when Basic Authorization is used to authenticate this request.
   * @param string $client_secret (Optional) The client secret. This value will be required if client authentication is enabled.
   * @param string $redirect_uri The URI to redirect to upon a successful request.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function exchangeOAuthCodeForAccessToken($code, $client_id, $client_secret, $redirect_uri)
  {
    $post_data = array(
      'code' => $code,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'authorization_code',
      'redirect_uri' => $redirect_uri
    );
    return $this->startAnonymous()->uri("/oauth2/token")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Exchanges an OAuth authorization code and code_verifier for an access token.
   * Makes a request to the Token endpoint to exchange the authorization code returned from the Authorize endpoint and a code_verifier for an access token.
   *
   * @param string $code The authorization code returned on the /oauth2/authorize response.
   * @param string $client_id (Optional) The unique client identifier. The client Id is the Id of the FusionAuth Application in which you are attempting to authenticate. This parameter is optional when the Authorization header is provided.
  *     This parameter is optional when Basic Authorization is used to authenticate this request.
   * @param string $client_secret (Optional) The client secret. This value may optionally be provided in the request body instead of the Authorization header.
   * @param string $redirect_uri The URI to redirect to upon a successful request.
   * @param string $code_verifier The random string generated previously. Will be compared with the code_challenge sent previously, which allows the OAuth provider to authenticate your app.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function exchangeOAuthCodeForAccessTokenUsingPKCE($code, $client_id, $client_secret, $redirect_uri, $code_verifier)
  {
    $post_data = array(
      'code' => $code,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'authorization_code',
      'redirect_uri' => $redirect_uri,
      'code_verifier' => $code_verifier
    );
    return $this->startAnonymous()->uri("/oauth2/token")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Exchange a Refresh Token for an Access Token.
   * If you will be using the Refresh Token Grant, you will make a request to the Token endpoint to exchange the user’s refresh token for an access token.
   *
   * @param string $refresh_token The refresh token that you would like to use to exchange for an access token.
   * @param string $client_id (Optional) The unique client identifier. The client Id is the Id of the FusionAuth Application in which you are attempting to authenticate. This parameter is optional when the Authorization header is provided.
  *     This parameter is optional when Basic Authorization is used to authenticate this request.
   * @param string $client_secret (Optional) The client secret. This value may optionally be provided in the request body instead of the Authorization header.
   * @param string $scope (Optional) This parameter is optional and if omitted, the same scope requested during the authorization request will be used. If provided the scopes must match those requested during the initial authorization request.
   * @param string $user_code (Optional) The end-user verification code. This code is required if using this endpoint to approve the Device Authorization.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function exchangeRefreshTokenForAccessToken($refresh_token, $client_id, $client_secret, $scope, $user_code = NULL)
  {
    $post_data = array(
      'refresh_token' => $refresh_token,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'refresh_token',
      'scope' => $scope,
      'user_code' => $user_code
    );
    return $this->startAnonymous()->uri("/oauth2/token")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Exchange a refresh token for a new JWT.
   *
   * @param array $request The refresh request.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function exchangeRefreshTokenForJWT($request)
  {
    return $this->startAnonymous()->uri("/api/jwt/refresh")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Exchange User Credentials for a Token.
   * If you will be using the Resource Owner Password Credential Grant, you will make a request to the Token endpoint to exchange the user’s email and password for an access token.
   *
   * @param string $username The login identifier of the user. The login identifier can be either the email or the username.
   * @param string $password The user’s password.
   * @param string $client_id (Optional) The unique client identifier. The client Id is the Id of the FusionAuth Application in which you are attempting to authenticate. This parameter is optional when the Authorization header is provided.
  *     This parameter is optional when Basic Authorization is used to authenticate this request.
   * @param string $client_secret (Optional) The client secret. This value may optionally be provided in the request body instead of the Authorization header.
   * @param string $scope (Optional) This parameter is optional and if omitted, the same scope requested during the authorization request will be used. If provided the scopes must match those requested during the initial authorization request.
   * @param string $user_code (Optional) The end-user verification code. This code is required if using this endpoint to approve the Device Authorization.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function exchangeUserCredentialsForAccessToken($username, $password, $client_id, $client_secret, $scope, $user_code = NULL)
  {
    $post_data = array(
      'username' => $username,
      'password' => $password,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'password',
      'scope' => $scope,
      'user_code' => $user_code
    );
    return $this->startAnonymous()->uri("/oauth2/token")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Begins the forgot password sequence, which kicks off an email to the user so that they can reset their password.
   *
   * @param array $request The request that contains the information about the user so that they can be emailed.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function forgotPassword($request)
  {
    return $this->start()->uri("/api/user/forgot-password")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Generate a new Email Verification Id to be used with the Verify Email API. This API will not attempt to send an
   * email to the User. This API may be used to collect the verificationId for use with a third party system.
   *
   * @param string $email The email address of the user that needs a new verification email.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function generateEmailVerificationId($email)
  {
    return $this->start()->uri("/api/user/verify-email")
        ->urlParameter("email", $email)
        ->urlParameter("sendVerifyEmail", false)
        ->put()
        ->go();
  }

  /**
   * Generate a new RSA or EC key pair or an HMAC secret.
   *
   * @param string $keyId (Optional) The Id for the key. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the key.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function generateKey($keyId, $request)
  {
    return $this->start()->uri("/api/key/generate")
        ->urlSegment($keyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Generate a new Application Registration Verification Id to be used with the Verify Registration API. This API will not attempt to send an
   * email to the User. This API may be used to collect the verificationId for use with a third party system.
   *
   * @param string $email The email address of the user that needs a new verification email.
   * @param string $applicationId The Id of the application to be verified.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function generateRegistrationVerificationId($email, $applicationId)
  {
    return $this->start()->uri("/api/user/verify-registration")
        ->urlParameter("email", $email)
        ->urlParameter("sendVerifyPasswordEmail", false)
        ->urlParameter("applicationId", $applicationId)
        ->put()
        ->go();
  }

  /**
   * Generate two-factor recovery codes for a user. Generating two-factor recovery codes will invalidate any existing recovery codes. 
   *
   * @param string $userId The Id of the user to generate new Two Factor recovery codes.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function generateTwoFactorRecoveryCodes($userId)
  {
    return $this->start()->uri("/api/user/two-factor/recovery-code")
        ->urlSegment($userId)
        ->post()
        ->go();
  }

  /**
   * Generate a Two Factor secret that can be used to enable Two Factor authentication for a User. The response will contain
   * both the secret and a Base32 encoded form of the secret which can be shown to a User when using a 2 Step Authentication
   * application such as Google Authenticator.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function generateTwoFactorSecret()
  {
    return $this->start()->uri("/api/two-factor/secret")
        ->get()
        ->go();
  }

  /**
   * Generate a Two Factor secret that can be used to enable Two Factor authentication for a User. The response will contain
   * both the secret and a Base32 encoded form of the secret which can be shown to a User when using a 2 Step Authentication
   * application such as Google Authenticator.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function generateTwoFactorSecretUsingJWT($encodedJWT)
  {
    return $this->startAnonymous()->uri("/api/two-factor/secret")
        ->authorization("Bearer " . $encodedJWT)
        ->get()
        ->go();
  }

  /**
   * Handles login via third-parties including Social login, external OAuth and OpenID Connect, and other
   * login systems.
   *
   * @param array $request The third-party login request that contains information from the third-party login
  *     providers that FusionAuth uses to reconcile the user's account.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function identityProviderLogin($request)
  {
    return $this->startAnonymous()->uri("/api/identity-provider/login")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Import an existing RSA or EC key pair or an HMAC secret.
   *
   * @param string $keyId (Optional) The Id for the key. If not provided a secure random UUID will be generated.
   * @param array $request The request object that contains all the information used to create the key.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function importKey($keyId, $request)
  {
    return $this->start()->uri("/api/key/import")
        ->urlSegment($keyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Bulk imports refresh tokens. This request performs minimal validation and runs batch inserts of refresh tokens with the
   * expectation that each token represents a user that already exists and is registered for the corresponding FusionAuth
   * Application. This is done to increases the insert performance.
   * 
   * Therefore, if you encounter an error due to a database key violation, the response will likely offer a generic
   * explanation. If you encounter an error, you may optionally enable additional validation to receive a JSON response
   * body with specific validation errors. This will slow the request down but will allow you to identify the cause of
   * the failure. See the validateDbConstraints request parameter.
   *
   * @param array $request The request that contains all the information about all the refresh tokens to import.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function importRefreshTokens($request)
  {
    return $this->start()->uri("/api/user/refresh-token/import")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Bulk imports users. This request performs minimal validation and runs batch inserts of users with the expectation
   * that each user does not yet exist and each registration corresponds to an existing FusionAuth Application. This is done to
   * increases the insert performance.
   * 
   * Therefore, if you encounter an error due to a database key violation, the response will likely offer
   * a generic explanation. If you encounter an error, you may optionally enable additional validation to receive a JSON response
   * body with specific validation errors. This will slow the request down but will allow you to identify the cause of the failure. See
   * the validateDbConstraints request parameter.
   *
   * @param array $request The request that contains all the information about all the users to import.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function importUsers($request)
  {
    return $this->start()->uri("/api/user/import")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Import a WebAuthn credential
   *
   * @param array $request An object containing data necessary for importing the credential
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function importWebAuthnCredential($request)
  {
    return $this->start()->uri("/api/webauthn/import")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Inspect an access token issued as the result of the User based grant such as the Authorization Code Grant, Implicit Grant, the User Credentials Grant or the Refresh Grant.
   *
   * @param string $client_id The unique client identifier. The client Id is the Id of the FusionAuth Application for which this token was generated.
   * @param string $token The access token returned by this OAuth provider as the result of a successful client credentials grant.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function introspectAccessToken($client_id, $token)
  {
    $post_data = array(
      'client_id' => $client_id,
      'token' => $token
    );
    return $this->startAnonymous()->uri("/oauth2/introspect")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Inspect an access token issued as the result of the Client Credentials Grant.
   *
   * @param string $token The access token returned by this OAuth provider as the result of a successful client credentials grant.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function introspectClientCredentialsAccessToken($token)
  {
    $post_data = array(
      'token' => $token
    );
    return $this->startAnonymous()->uri("/oauth2/introspect")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->post()
        ->go();
  }

  /**
   * Issue a new access token (JWT) for the requested Application after ensuring the provided JWT is valid. A valid
   * access token is properly signed and not expired.
   * <p>
   * This API may be used in an SSO configuration to issue new tokens for another application after the user has
   * obtained a valid token from authentication.
   *
   * @param string $applicationId The Application Id for which you are requesting a new access token be issued.
   * @param string $encodedJWT The encoded JWT (access token).
   * @param string $refreshToken (Optional) An existing refresh token used to request a refresh token in addition to a JWT in the response.
  *     <p>The target application represented by the applicationId request parameter must have refresh
  *     tokens enabled in order to receive a refresh token in the response.</p>
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function issueJWT($applicationId, $encodedJWT, $refreshToken = NULL)
  {
    return $this->startAnonymous()->uri("/api/jwt/issue")
        ->authorization("Bearer " . $encodedJWT)
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("refreshToken", $refreshToken)
        ->get()
        ->go();
  }

  /**
   * Authenticates a user to FusionAuth. 
   * 
   * This API optionally requires an API key. See <code>Application.loginConfiguration.requireAuthentication</code>.
   *
   * @param array $request The login request that contains the user credentials used to log them in.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function login($request)
  {
    return $this->start()->uri("/api/login")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Sends a ping to FusionAuth indicating that the user was automatically logged into an application. When using
   * FusionAuth's SSO or your own, you should call this if the user is already logged in centrally, but accesses an
   * application where they no longer have a session. This helps correctly track login counts, times and helps with
   * reporting.
   *
   * @param string $userId The Id of the user that was logged in.
   * @param string $applicationId The Id of the application that they logged into.
   * @param string $callerIPAddress (Optional) The IP address of the end-user that is logging in. If a null value is provided
  *     the IP address will be that of the client or last proxy that sent the request.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function loginPing($userId, $applicationId, $callerIPAddress = NULL)
  {
    return $this->start()->uri("/api/login")
        ->urlSegment($userId)
        ->urlSegment($applicationId)
        ->urlParameter("ipAddress", $callerIPAddress)
        ->put()
        ->go();
  }

  /**
   * Sends a ping to FusionAuth indicating that the user was automatically logged into an application. When using
   * FusionAuth's SSO or your own, you should call this if the user is already logged in centrally, but accesses an
   * application where they no longer have a session. This helps correctly track login counts, times and helps with
   * reporting.
   *
   * @param array $request The login request that contains the user credentials used to log them in.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function loginPingWithRequest($request)
  {
    return $this->start()->uri("/api/login")
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * The Logout API is intended to be used to remove the refresh token and access token cookies if they exist on the
   * client and revoke the refresh token stored. This API does nothing if the request does not contain an access
   * token or refresh token cookies.
   *
   * @param boolean $global When this value is set to true all the refresh tokens issued to the owner of the
  *     provided token will be revoked.
   * @param string $refreshToken (Optional) The refresh_token as a request parameter instead of coming in via a cookie.
  *     If provided this takes precedence over the cookie.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function logout($global, $refreshToken = NULL)
  {
    return $this->startAnonymous()->uri("/api/logout")
        ->urlParameter("global", $global)
        ->urlParameter("refreshToken", $refreshToken)
        ->post()
        ->go();
  }

  /**
   * The Logout API is intended to be used to remove the refresh token and access token cookies if they exist on the
   * client and revoke the refresh token stored. This API takes the refresh token in the JSON body.
   *
   * @param array $request The request object that contains all the information used to logout the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function logoutWithRequest($request)
  {
    return $this->startAnonymous()->uri("/api/logout")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Retrieves the identity provider for the given domain. A 200 response code indicates the domain is managed
   * by a registered identity provider. A 404 indicates the domain is not managed.
   *
   * @param string $domain The domain or email address to lookup.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function lookupIdentityProvider($domain)
  {
    return $this->start()->uri("/api/identity-provider/lookup")
        ->urlParameter("domain", $domain)
        ->get()
        ->go();
  }

  /**
   * Modifies a temporal user action by changing the expiration of the action and optionally adding a comment to the
   * action.
   *
   * @param string $actionId The Id of the action to modify. This is technically the user action log Id.
   * @param array $request The request that contains all the information about the modification.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function modifyAction($actionId, $request)
  {
    return $this->start()->uri("/api/user/action")
        ->urlSegment($actionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Complete a login request using a passwordless code
   *
   * @param array $request The passwordless login request that contains all the information used to complete login.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function passwordlessLogin($request)
  {
    return $this->startAnonymous()->uri("/api/passwordless/login")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Updates an API key with the given Id.
   *
   * @param string $keyId The Id of the API key. If not provided a secure random api key will be generated.
   * @param array $request The request object that contains all the information needed to create the API key.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchAPIKey($keyId, $request)
  {
    return $this->start()->uri("/api/api-key")
        ->urlSegment($keyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the application with the given Id.
   *
   * @param string $applicationId The Id of the application to update.
   * @param array $request The request that contains just the new application information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchApplication($applicationId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the application role with the given Id for the application.
   *
   * @param string $applicationId The Id of the application that the role belongs to.
   * @param string $roleId The Id of the role to update.
   * @param array $request The request that contains just the new role information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchApplicationRole($applicationId, $roleId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("role")
        ->urlSegment($roleId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the connector with the given Id.
   *
   * @param string $connectorId The Id of the connector to update.
   * @param array $request The request that contains just the new connector information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchConnector($connectorId, $request)
  {
    return $this->start()->uri("/api/connector")
        ->urlSegment($connectorId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the consent with the given Id.
   *
   * @param string $consentId The Id of the consent to update.
   * @param array $request The request that contains just the new consent information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchConsent($consentId, $request)
  {
    return $this->start()->uri("/api/consent")
        ->urlSegment($consentId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the email template with the given Id.
   *
   * @param string $emailTemplateId The Id of the email template to update.
   * @param array $request The request that contains just the new email template information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchEmailTemplate($emailTemplateId, $request)
  {
    return $this->start()->uri("/api/email/template")
        ->urlSegment($emailTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the Entity with the given Id.
   *
   * @param string $entityId The Id of the Entity Type to update.
   * @param array $request The request that contains just the new Entity information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchEntity($entityId, $request)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the Entity Type with the given Id.
   *
   * @param string $entityTypeId The Id of the Entity Type to update.
   * @param array $request The request that contains just the new Entity Type information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchEntityType($entityTypeId, $request)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Patches the permission with the given Id for the entity type.
   *
   * @param string $entityTypeId The Id of the entityType that the permission belongs to.
   * @param string $permissionId The Id of the permission to patch.
   * @param array $request The request that contains the new permission information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchEntityTypePermission($entityTypeId, $permissionId, $request)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->urlSegment("permission")
        ->urlSegment($permissionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Patches the form with the given Id.
   *
   * @param string $formId The Id of the form to patch.
   * @param array $request The request object that contains the new form information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchForm($formId, $request)
  {
    return $this->start()->uri("/api/form")
        ->urlSegment($formId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Patches the form field with the given Id.
   *
   * @param string $fieldId The Id of the form field to patch.
   * @param array $request The request object that contains the new form field information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchFormField($fieldId, $request)
  {
    return $this->start()->uri("/api/form/field")
        ->urlSegment($fieldId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the group with the given Id.
   *
   * @param string $groupId The Id of the group to update.
   * @param array $request The request that contains just the new group information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchGroup($groupId, $request)
  {
    return $this->start()->uri("/api/group")
        ->urlSegment($groupId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Update the IP Access Control List with the given Id.
   *
   * @param string $accessControlListId The Id of the IP Access Control List to patch.
   * @param array $request The request that contains the new IP Access Control List information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchIPAccessControlList($accessControlListId, $request)
  {
    return $this->start()->uri("/api/ip-acl")
        ->urlSegment($accessControlListId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the identity provider with the given Id.
   *
   * @param string $identityProviderId The Id of the identity provider to update.
   * @param array $request The request object that contains just the updated identity provider information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchIdentityProvider($identityProviderId, $request)
  {
    return $this->start()->uri("/api/identity-provider")
        ->urlSegment($identityProviderId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the available integrations.
   *
   * @param array $request The request that contains just the new integration information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchIntegrations($request)
  {
    return $this->start()->uri("/api/integration")
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the lambda with the given Id.
   *
   * @param string $lambdaId The Id of the lambda to update.
   * @param array $request The request that contains just the new lambda information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchLambda($lambdaId, $request)
  {
    return $this->start()->uri("/api/lambda")
        ->urlSegment($lambdaId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the message template with the given Id.
   *
   * @param string $messageTemplateId The Id of the message template to update.
   * @param array $request The request that contains just the new message template information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchMessageTemplate($messageTemplateId, $request)
  {
    return $this->start()->uri("/api/message/template")
        ->urlSegment($messageTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the messenger with the given Id.
   *
   * @param string $messengerId The Id of the messenger to update.
   * @param array $request The request that contains just the new messenger information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchMessenger($messengerId, $request)
  {
    return $this->start()->uri("/api/messenger")
        ->urlSegment($messengerId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the custom OAuth scope with the given Id for the application.
   *
   * @param string $applicationId The Id of the application that the OAuth scope belongs to.
   * @param string $scopeId The Id of the OAuth scope to update.
   * @param array $request The request that contains just the new OAuth scope information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchOAuthScope($applicationId, $scopeId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("scope")
        ->urlSegment($scopeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the registration for the user with the given Id and the application defined in the request.
   *
   * @param string $userId The Id of the user whose registration is going to be updated.
   * @param array $request The request that contains just the new registration information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchRegistration($userId, $request)
  {
    return $this->start()->uri("/api/user/registration")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the system configuration.
   *
   * @param array $request The request that contains just the new system configuration information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchSystemConfiguration($request)
  {
    return $this->start()->uri("/api/system-configuration")
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the tenant with the given Id.
   *
   * @param string $tenantId The Id of the tenant to update.
   * @param array $request The request that contains just the new tenant information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchTenant($tenantId, $request)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the theme with the given Id.
   *
   * @param string $themeId The Id of the theme to update.
   * @param array $request The request that contains just the new theme information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchTheme($themeId, $request)
  {
    return $this->start()->uri("/api/theme")
        ->urlSegment($themeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the user with the given Id.
   *
   * @param string $userId The Id of the user to update.
   * @param array $request The request that contains just the new user information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchUser($userId, $request)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the user action with the given Id.
   *
   * @param string $userActionId The Id of the user action to update.
   * @param array $request The request that contains just the new user action information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchUserAction($userActionId, $request)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, the user action reason with the given Id.
   *
   * @param string $userActionReasonId The Id of the user action reason to update.
   * @param array $request The request that contains just the new user action reason information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchUserActionReason($userActionReasonId, $request)
  {
    return $this->start()->uri("/api/user-action-reason")
        ->urlSegment($userActionReasonId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Updates, via PATCH, a single User consent by Id.
   *
   * @param string $userConsentId The User Consent Id
   * @param array $request The request that contains just the new user consent information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchUserConsent($userConsentId, $request)
  {
    return $this->start()->uri("/api/user/consent")
        ->urlSegment($userConsentId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Patches the webhook with the given Id.
   *
   * @param string $webhookId The Id of the webhook to update.
   * @param array $request The request that contains the new webhook information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function patchWebhook($webhookId, $request)
  {
    return $this->start()->uri("/api/webhook")
        ->urlSegment($webhookId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->patch()
        ->go();
  }

  /**
   * Reactivates the application with the given Id.
   *
   * @param string $applicationId The Id of the application to reactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function reactivateApplication($applicationId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlParameter("reactivate", true)
        ->put()
        ->go();
  }

  /**
   * Reactivates the user with the given Id.
   *
   * @param string $userId The Id of the user to reactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function reactivateUser($userId)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->urlParameter("reactivate", true)
        ->put()
        ->go();
  }

  /**
   * Reactivates the user action with the given Id.
   *
   * @param string $userActionId The Id of the user action to reactivate.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function reactivateUserAction($userActionId)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->urlParameter("reactivate", true)
        ->put()
        ->go();
  }

  /**
   * Reconcile a User to FusionAuth using JWT issued from another Identity Provider.
   *
   * @param array $request The reconcile request that contains the data to reconcile the User.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function reconcileJWT($request)
  {
    return $this->startAnonymous()->uri("/api/jwt/reconcile")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Request a refresh of the Entity search index. This API is not generally necessary and the search index will become consistent in a
   * reasonable amount of time. There may be scenarios where you may wish to manually request an index refresh. One example may be 
   * if you are using the Search API or Delete Tenant API immediately following a Entity Create etc, you may wish to request a refresh to
   *  ensure the index immediately current before making a query request to the search index.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function refreshEntitySearchIndex()
  {
    return $this->start()->uri("/api/entity/search")
        ->put()
        ->go();
  }

  /**
   * Request a refresh of the User search index. This API is not generally necessary and the search index will become consistent in a
   * reasonable amount of time. There may be scenarios where you may wish to manually request an index refresh. One example may be 
   * if you are using the Search API or Delete Tenant API immediately following a User Create etc, you may wish to request a refresh to
   *  ensure the index immediately current before making a query request to the search index.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function refreshUserSearchIndex()
  {
    return $this->start()->uri("/api/user/search")
        ->put()
        ->go();
  }

  /**
   * Regenerates any keys that are used by the FusionAuth Reactor.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function regenerateReactorKeys()
  {
    return $this->start()->uri("/api/reactor")
        ->put()
        ->go();
  }

  /**
   * Registers a user for an application. If you provide the User and the UserRegistration object on this request, it
   * will create the user as well as register them for the application. This is called a Full Registration. However, if
   * you only provide the UserRegistration object, then the user must already exist and they will be registered for the
   * application. The user Id can also be provided and it will either be used to look up an existing user or it will be
   * used for the newly created User.
   *
   * @param string $userId (Optional) The Id of the user being registered for the application and optionally created.
   * @param array $request The request that optionally contains the User and must contain the UserRegistration.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function register($userId, $request)
  {
    return $this->start()->uri("/api/user/registration")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Requests Elasticsearch to delete and rebuild the index for FusionAuth users or entities. Be very careful when running this request as it will 
   * increase the CPU and I/O load on your database until the operation completes. Generally speaking you do not ever need to run this operation unless 
   * instructed by FusionAuth support, or if you are migrating a database another system and you are not brining along the Elasticsearch index. 
   * 
   * You have been warned.
   *
   * @param array $request The request that contains the index name.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function reindex($request)
  {
    return $this->start()->uri("/api/system/reindex")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Removes a user from the family with the given Id.
   *
   * @param string $familyId The Id of the family to remove the user from.
   * @param string $userId The Id of the user to remove from the family.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function removeUserFromFamily($familyId, $userId)
  {
    return $this->start()->uri("/api/user/family")
        ->urlSegment($familyId)
        ->urlSegment($userId)
        ->delete()
        ->go();
  }

  /**
   * Re-sends the verification email to the user.
   *
   * @param string $email The email address of the user that needs a new verification email.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function resendEmailVerification($email)
  {
    return $this->start()->uri("/api/user/verify-email")
        ->urlParameter("email", $email)
        ->put()
        ->go();
  }

  /**
   * Re-sends the verification email to the user. If the Application has configured a specific email template this will be used
   * instead of the tenant configuration.
   *
   * @param string $applicationId The unique Application Id to used to resolve an application specific email template.
   * @param string $email The email address of the user that needs a new verification email.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function resendEmailVerificationWithApplicationTemplate($applicationId, $email)
  {
    return $this->start()->uri("/api/user/verify-email")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("email", $email)
        ->put()
        ->go();
  }

  /**
   * Re-sends the application registration verification email to the user.
   *
   * @param string $email The email address of the user that needs a new verification email.
   * @param string $applicationId The Id of the application to be verified.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function resendRegistrationVerification($email, $applicationId)
  {
    return $this->start()->uri("/api/user/verify-registration")
        ->urlParameter("email", $email)
        ->urlParameter("applicationId", $applicationId)
        ->put()
        ->go();
  }

  /**
   * Retrieves an authentication API key for the given Id.
   *
   * @param string $keyId The Id of the API key to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveAPIKey($keyId)
  {
    return $this->start()->uri("/api/api-key")
        ->urlSegment($keyId)
        ->get()
        ->go();
  }

  /**
   * Retrieves a single action log (the log of a user action that was taken on a user previously) for the given Id.
   *
   * @param string $actionId The Id of the action to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveAction($actionId)
  {
    return $this->start()->uri("/api/user/action")
        ->urlSegment($actionId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the actions for the user with the given Id. This will return all time based actions that are active,
   * and inactive as well as non-time based actions.
   *
   * @param string $userId The Id of the user to fetch the actions for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveActions($userId)
  {
    return $this->start()->uri("/api/user/action")
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the actions for the user with the given Id that are currently preventing the User from logging in.
   *
   * @param string $userId The Id of the user to fetch the actions for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveActionsPreventingLogin($userId)
  {
    return $this->start()->uri("/api/user/action")
        ->urlParameter("userId", $userId)
        ->urlParameter("preventingLogin", true)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the actions for the user with the given Id that are currently active.
   * An active action means one that is time based and has not been canceled, and has not ended.
   *
   * @param string $userId The Id of the user to fetch the actions for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveActiveActions($userId)
  {
    return $this->start()->uri("/api/user/action")
        ->urlParameter("userId", $userId)
        ->urlParameter("active", true)
        ->get()
        ->go();
  }

  /**
   * Retrieves the application for the given Id or all the applications if the Id is null.
   *
   * @param string $applicationId (Optional) The application Id.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveApplication($applicationId = NULL)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the applications.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveApplications()
  {
    return $this->start()->uri("/api/application")
        ->get()
        ->go();
  }

  /**
   * Retrieves a single audit log for the given Id.
   *
   * @param int $auditLogId The Id of the audit log to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveAuditLog($auditLogId)
  {
    return $this->start()->uri("/api/system/audit-log")
        ->urlSegment($auditLogId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the connector with the given Id.
   *
   * @param string $connectorId The Id of the connector.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveConnector($connectorId)
  {
    return $this->start()->uri("/api/connector")
        ->urlSegment($connectorId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the connectors.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveConnectors()
  {
    return $this->start()->uri("/api/connector")
        ->get()
        ->go();
  }

  /**
   * Retrieves the Consent for the given Id.
   *
   * @param string $consentId The Id of the consent.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveConsent($consentId)
  {
    return $this->start()->uri("/api/consent")
        ->urlSegment($consentId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the consent.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveConsents()
  {
    return $this->start()->uri("/api/consent")
        ->get()
        ->go();
  }

  /**
   * Retrieves the daily active user report between the two instants. If you specify an application Id, it will only
   * return the daily active counts for that application.
   *
   * @param string $applicationId (Optional) The application Id.
   * @param array $start The start instant as UTC milliseconds since Epoch.
   * @param array $end The end instant as UTC milliseconds since Epoch.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveDailyActiveReport($applicationId, $start, $end)
  {
    return $this->start()->uri("/api/report/daily-active-user")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("start", $start)
        ->urlParameter("end", $end)
        ->get()
        ->go();
  }

  /**
   * Retrieves the email template for the given Id. If you don't specify the Id, this will return all the email templates.
   *
   * @param string $emailTemplateId (Optional) The Id of the email template.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEmailTemplate($emailTemplateId = NULL)
  {
    return $this->start()->uri("/api/email/template")
        ->urlSegment($emailTemplateId)
        ->get()
        ->go();
  }

  /**
   * Creates a preview of the email template provided in the request. This allows you to preview an email template that
   * hasn't been saved to the database yet. The entire email template does not need to be provided on the request. This
   * will create the preview based on whatever is given.
   *
   * @param array $request The request that contains the email template and optionally a locale to render it in.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEmailTemplatePreview($request)
  {
    return $this->start()->uri("/api/email/template/preview")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Retrieves all the email templates.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEmailTemplates()
  {
    return $this->start()->uri("/api/email/template")
        ->get()
        ->go();
  }

  /**
   * Retrieves the Entity for the given Id.
   *
   * @param string $entityId The Id of the Entity.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEntity($entityId)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->get()
        ->go();
  }

  /**
   * Retrieves an Entity Grant for the given Entity and User/Entity.
   *
   * @param string $entityId The Id of the Entity.
   * @param string $recipientEntityId (Optional) The Id of the Entity that the Entity Grant is for.
   * @param string $userId (Optional) The Id of the User that the Entity Grant is for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEntityGrant($entityId, $recipientEntityId, $userId = NULL)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->urlSegment("grant")
        ->urlParameter("recipientEntityId", $recipientEntityId)
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the Entity Type for the given Id.
   *
   * @param string $entityTypeId The Id of the Entity Type.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEntityType($entityTypeId)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the Entity Types.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEntityTypes()
  {
    return $this->start()->uri("/api/entity/type")
        ->get()
        ->go();
  }

  /**
   * Retrieves a single event log for the given Id.
   *
   * @param int $eventLogId The Id of the event log to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveEventLog($eventLogId)
  {
    return $this->start()->uri("/api/system/event-log")
        ->urlSegment($eventLogId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the families that a user belongs to.
   *
   * @param string $userId The User's id
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveFamilies($userId)
  {
    return $this->start()->uri("/api/user/family")
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the members of a family by the unique Family Id.
   *
   * @param string $familyId The unique Id of the Family.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveFamilyMembersByFamilyId($familyId)
  {
    return $this->start()->uri("/api/user/family")
        ->urlSegment($familyId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the form with the given Id.
   *
   * @param string $formId The Id of the form.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveForm($formId)
  {
    return $this->start()->uri("/api/form")
        ->urlSegment($formId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the form field with the given Id.
   *
   * @param string $fieldId The Id of the form field.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveFormField($fieldId)
  {
    return $this->start()->uri("/api/form/field")
        ->urlSegment($fieldId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the forms fields
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveFormFields()
  {
    return $this->start()->uri("/api/form/field")
        ->get()
        ->go();
  }

  /**
   * Retrieves all the forms.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveForms()
  {
    return $this->start()->uri("/api/form")
        ->get()
        ->go();
  }

  /**
   * Retrieves the group for the given Id.
   *
   * @param string $groupId The Id of the group.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveGroup($groupId)
  {
    return $this->start()->uri("/api/group")
        ->urlSegment($groupId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the groups.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveGroups()
  {
    return $this->start()->uri("/api/group")
        ->get()
        ->go();
  }

  /**
   * Retrieves the IP Access Control List with the given Id.
   *
   * @param string $ipAccessControlListId The Id of the IP Access Control List.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveIPAccessControlList($ipAccessControlListId)
  {
    return $this->start()->uri("/api/ip-acl")
        ->urlSegment($ipAccessControlListId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the identity provider for the given Id or all the identity providers if the Id is null.
   *
   * @param string $identityProviderId The identity provider Id.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveIdentityProvider($identityProviderId)
  {
    return $this->start()->uri("/api/identity-provider")
        ->urlSegment($identityProviderId)
        ->get()
        ->go();
  }

  /**
   * Retrieves one or more identity provider for the given type. For types such as Google, Facebook, Twitter and LinkedIn, only a single 
   * identity provider can exist. For types such as OpenID Connect and SAMLv2 more than one identity provider can be configured so this request 
   * may return multiple identity providers.
   *
   * @param array $type The type of the identity provider.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveIdentityProviderByType($type)
  {
    return $this->start()->uri("/api/identity-provider")
        ->urlParameter("type", $type)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the identity providers.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveIdentityProviders()
  {
    return $this->start()->uri("/api/identity-provider")
        ->get()
        ->go();
  }

  /**
   * Retrieves all the actions for the user with the given Id that are currently inactive.
   * An inactive action means one that is time based and has been canceled or has expired, or is not time based.
   *
   * @param string $userId The Id of the user to fetch the actions for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveInactiveActions($userId)
  {
    return $this->start()->uri("/api/user/action")
        ->urlParameter("userId", $userId)
        ->urlParameter("active", false)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the applications that are currently inactive.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveInactiveApplications()
  {
    return $this->start()->uri("/api/application")
        ->urlParameter("inactive", true)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the user actions that are currently inactive.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveInactiveUserActions()
  {
    return $this->start()->uri("/api/user-action")
        ->urlParameter("inactive", true)
        ->get()
        ->go();
  }

  /**
   * Retrieves the available integrations.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveIntegration()
  {
    return $this->start()->uri("/api/integration")
        ->get()
        ->go();
  }

  /**
   * Retrieves the Public Key configured for verifying JSON Web Tokens (JWT) by the key Id (kid).
   *
   * @param string $keyId The Id of the public key (kid).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveJWTPublicKey($keyId)
  {
    return $this->startAnonymous()->uri("/api/jwt/public-key")
        ->urlParameter("kid", $keyId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the Public Key configured for verifying the JSON Web Tokens (JWT) issued by the Login API by the Application Id.
   *
   * @param string $applicationId The Id of the Application for which this key is used.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveJWTPublicKeyByApplicationId($applicationId)
  {
    return $this->startAnonymous()->uri("/api/jwt/public-key")
        ->urlParameter("applicationId", $applicationId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all Public Keys configured for verifying JSON Web Tokens (JWT).
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveJWTPublicKeys()
  {
    return $this->startAnonymous()->uri("/api/jwt/public-key")
        ->get()
        ->go();
  }

  /**
   * Returns public keys used by FusionAuth to cryptographically verify JWTs using the JSON Web Key format.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveJsonWebKeySet()
  {
    return $this->startAnonymous()->uri("/.well-known/jwks.json")
        ->get()
        ->go();
  }

  /**
   * Retrieves the key for the given Id.
   *
   * @param string $keyId The Id of the key.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveKey($keyId)
  {
    return $this->start()->uri("/api/key")
        ->urlSegment($keyId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the keys.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveKeys()
  {
    return $this->start()->uri("/api/key")
        ->get()
        ->go();
  }

  /**
   * Retrieves the lambda for the given Id.
   *
   * @param string $lambdaId The Id of the lambda.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveLambda($lambdaId)
  {
    return $this->start()->uri("/api/lambda")
        ->urlSegment($lambdaId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the lambdas.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveLambdas()
  {
    return $this->start()->uri("/api/lambda")
        ->get()
        ->go();
  }

  /**
   * Retrieves all the lambdas for the provided type.
   *
   * @param array $type The type of the lambda to return.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveLambdasByType($type)
  {
    return $this->start()->uri("/api/lambda")
        ->urlParameter("type", $type)
        ->get()
        ->go();
  }

  /**
   * Retrieves the login report between the two instants. If you specify an application Id, it will only return the
   * login counts for that application.
   *
   * @param string $applicationId (Optional) The application Id.
   * @param array $start The start instant as UTC milliseconds since Epoch.
   * @param array $end The end instant as UTC milliseconds since Epoch.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveLoginReport($applicationId, $start, $end)
  {
    return $this->start()->uri("/api/report/login")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("start", $start)
        ->urlParameter("end", $end)
        ->get()
        ->go();
  }

  /**
   * Retrieves the message template for the given Id. If you don't specify the Id, this will return all the message templates.
   *
   * @param string $messageTemplateId (Optional) The Id of the message template.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveMessageTemplate($messageTemplateId = NULL)
  {
    return $this->start()->uri("/api/message/template")
        ->urlSegment($messageTemplateId)
        ->get()
        ->go();
  }

  /**
   * Creates a preview of the message template provided in the request, normalized to a given locale.
   *
   * @param array $request The request that contains the email template and optionally a locale to render it in.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveMessageTemplatePreview($request)
  {
    return $this->start()->uri("/api/message/template/preview")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Retrieves all the message templates.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveMessageTemplates()
  {
    return $this->start()->uri("/api/message/template")
        ->get()
        ->go();
  }

  /**
   * Retrieves the messenger with the given Id.
   *
   * @param string $messengerId The Id of the messenger.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveMessenger($messengerId)
  {
    return $this->start()->uri("/api/messenger")
        ->urlSegment($messengerId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the messengers.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveMessengers()
  {
    return $this->start()->uri("/api/messenger")
        ->get()
        ->go();
  }

  /**
   * Retrieves the monthly active user report between the two instants. If you specify an application Id, it will only
   * return the monthly active counts for that application.
   *
   * @param string $applicationId (Optional) The application Id.
   * @param array $start The start instant as UTC milliseconds since Epoch.
   * @param array $end The end instant as UTC milliseconds since Epoch.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveMonthlyActiveReport($applicationId, $start, $end)
  {
    return $this->start()->uri("/api/report/monthly-active-user")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("start", $start)
        ->urlParameter("end", $end)
        ->get()
        ->go();
  }

  /**
   * Retrieves a custom OAuth scope.
   *
   * @param string $applicationId The Id of the application that the OAuth scope belongs to.
   * @param string $scopeId The Id of the OAuth scope to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveOAuthScope($applicationId, $scopeId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("scope")
        ->urlSegment($scopeId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the Oauth2 configuration for the application for the given Application Id.
   *
   * @param string $applicationId The Id of the Application to retrieve OAuth configuration.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveOauthConfiguration($applicationId)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("oauth-configuration")
        ->get()
        ->go();
  }

  /**
   * Returns the well known OpenID Configuration JSON document
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveOpenIdConfiguration()
  {
    return $this->startAnonymous()->uri("/.well-known/openid-configuration")
        ->get()
        ->go();
  }

  /**
   * Retrieves the password validation rules for a specific tenant. This method requires a tenantId to be provided 
   * through the use of a Tenant scoped API key or an HTTP header X-FusionAuth-TenantId to specify the Tenant Id.
   * 
   * This API does not require an API key.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrievePasswordValidationRules()
  {
    return $this->startAnonymous()->uri("/api/tenant/password-validation-rules")
        ->get()
        ->go();
  }

  /**
   * Retrieves the password validation rules for a specific tenant.
   * 
   * This API does not require an API key.
   *
   * @param string $tenantId The Id of the tenant.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrievePasswordValidationRulesWithTenantId($tenantId)
  {
    return $this->startAnonymous()->uri("/api/tenant/password-validation-rules")
        ->urlSegment($tenantId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the children for the given parent email address.
   *
   * @param string $parentEmail The email of the parent.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrievePendingChildren($parentEmail)
  {
    return $this->start()->uri("/api/user/family/pending")
        ->urlParameter("parentEmail", $parentEmail)
        ->get()
        ->go();
  }

  /**
   * Retrieve a pending identity provider link. This is useful to validate a pending link and retrieve meta-data about the identity provider link.
   *
   * @param string $pendingLinkId The pending link Id.
   * @param string $userId The optional userId. When provided additional meta-data will be provided to identify how many links if any the user already has.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrievePendingLink($pendingLinkId, $userId)
  {
    return $this->start()->uri("/api/identity-provider/link/pending")
        ->urlSegment($pendingLinkId)
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the FusionAuth Reactor metrics.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveReactorMetrics()
  {
    return $this->start()->uri("/api/reactor/metrics")
        ->get()
        ->go();
  }

  /**
   * Retrieves the FusionAuth Reactor status.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveReactorStatus()
  {
    return $this->start()->uri("/api/reactor")
        ->get()
        ->go();
  }

  /**
   * Retrieves the last number of login records.
   *
   * @param int $offset The initial record. e.g. 0 is the last login, 100 will be the 100th most recent login.
   * @param int $limit (Optional, defaults to 10) The number of records to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveRecentLogins($offset, $limit)
  {
    return $this->start()->uri("/api/user/recent-login")
        ->urlParameter("offset", $offset)
        ->urlParameter("limit", $limit)
        ->get()
        ->go();
  }

  /**
   * Retrieves a single refresh token by unique Id. This is not the same thing as the string value of the refresh token. If you have that, you already have what you need.
   *
   * @param string $tokenId The Id of the token.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveRefreshTokenById($tokenId)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlSegment($tokenId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the refresh tokens that belong to the user with the given Id.
   *
   * @param string $userId The Id of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveRefreshTokens($userId)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user registration for the user with the given Id and the given application Id.
   *
   * @param string $userId The Id of the user.
   * @param string $applicationId The Id of the application.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveRegistration($userId, $applicationId)
  {
    return $this->start()->uri("/api/user/registration")
        ->urlSegment($userId)
        ->urlSegment($applicationId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the registration report between the two instants. If you specify an application Id, it will only return
   * the registration counts for that application.
   *
   * @param string $applicationId (Optional) The application Id.
   * @param array $start The start instant as UTC milliseconds since Epoch.
   * @param array $end The end instant as UTC milliseconds since Epoch.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveRegistrationReport($applicationId, $start, $end)
  {
    return $this->start()->uri("/api/report/registration")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("start", $start)
        ->urlParameter("end", $end)
        ->get()
        ->go();
  }

  /**
   * Retrieve the status of a re-index process. A status code of 200 indicates the re-index is in progress, a status code of  
   * 404 indicates no re-index is in progress.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveReindexStatus()
  {
    return $this->start()->uri("/api/system/reindex")
        ->get()
        ->go();
  }

  /**
   * Retrieves the system configuration.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveSystemConfiguration()
  {
    return $this->start()->uri("/api/system-configuration")
        ->get()
        ->go();
  }

  /**
   * Retrieves the FusionAuth system health. This API will return 200 if the system is healthy, and 500 if the system is un-healthy.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveSystemHealth()
  {
    return $this->startAnonymous()->uri("/api/health")
        ->get()
        ->go();
  }

  /**
   * Retrieves the FusionAuth system status. This request is anonymous and does not require an API key. When an API key is not provided the response will contain a single value in the JSON response indicating the current health check.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveSystemStatus()
  {
    return $this->startAnonymous()->uri("/api/status")
        ->get()
        ->go();
  }

  /**
   * Retrieves the FusionAuth system status using an API key. Using an API key will cause the response to include the product version, health checks and various runtime metrics.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveSystemStatusUsingAPIKey()
  {
    return $this->start()->uri("/api/status")
        ->get()
        ->go();
  }

  /**
   * Retrieves the tenant for the given Id.
   *
   * @param string $tenantId The Id of the tenant.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveTenant($tenantId)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the tenants.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveTenants()
  {
    return $this->start()->uri("/api/tenant")
        ->get()
        ->go();
  }

  /**
   * Retrieves the theme for the given Id.
   *
   * @param string $themeId The Id of the theme.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveTheme($themeId)
  {
    return $this->start()->uri("/api/theme")
        ->urlSegment($themeId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the themes.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveThemes()
  {
    return $this->start()->uri("/api/theme")
        ->get()
        ->go();
  }

  /**
   * Retrieves the totals report. This contains all the total counts for each application and the global registration
   * count.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveTotalReport()
  {
    return $this->start()->uri("/api/report/totals")
        ->get()
        ->go();
  }

  /**
   * Retrieve two-factor recovery codes for a user.
   *
   * @param string $userId The Id of the user to retrieve Two Factor recovery codes.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveTwoFactorRecoveryCodes($userId)
  {
    return $this->start()->uri("/api/user/two-factor/recovery-code")
        ->urlSegment($userId)
        ->get()
        ->go();
  }

  /**
   * Retrieve a user's two-factor status.
   * 
   * This can be used to see if a user will need to complete a two-factor challenge to complete a login,
   * and optionally identify the state of the two-factor trust across various applications.
   *
   * @param string $userId The user Id to retrieve the Two-Factor status.
   * @param string $applicationId The optional applicationId to verify.
   * @param string $twoFactorTrustId The optional two-factor trust Id to verify.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveTwoFactorStatus($userId, $applicationId, $twoFactorTrustId)
  {
    return $this->start()->uri("/api/two-factor/status")
        ->urlParameter("userId", $userId)
        ->urlParameter("applicationId", $applicationId)
        ->urlSegment($twoFactorTrustId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user for the given Id.
   *
   * @param string $userId The Id of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUser($userId)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user action for the given Id. If you pass in null for the Id, this will return all the user
   * actions.
   *
   * @param string $userActionId (Optional) The Id of the user action.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserAction($userActionId = NULL)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user action reason for the given Id. If you pass in null for the Id, this will return all the user
   * action reasons.
   *
   * @param string $userActionReasonId (Optional) The Id of the user action reason.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserActionReason($userActionReasonId = NULL)
  {
    return $this->start()->uri("/api/user-action-reason")
        ->urlSegment($userActionReasonId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the user action reasons.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserActionReasons()
  {
    return $this->start()->uri("/api/user-action-reason")
        ->get()
        ->go();
  }

  /**
   * Retrieves all the user actions.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserActions()
  {
    return $this->start()->uri("/api/user-action")
        ->get()
        ->go();
  }

  /**
   * Retrieves the user by a change password Id. The intended use of this API is to retrieve a user after the forgot
   * password workflow has been initiated and you may not know the user's email or username.
   *
   * @param string $changePasswordId The unique change password Id that was sent via email or returned by the Forgot Password API.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserByChangePasswordId($changePasswordId)
  {
    return $this->start()->uri("/api/user")
        ->urlParameter("changePasswordId", $changePasswordId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user for the given email.
   *
   * @param string $email The email of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserByEmail($email)
  {
    return $this->start()->uri("/api/user")
        ->urlParameter("email", $email)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user for the loginId. The loginId can be either the username or the email.
   *
   * @param string $loginId The email or username of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserByLoginId($loginId)
  {
    return $this->start()->uri("/api/user")
        ->urlParameter("loginId", $loginId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user for the given username.
   *
   * @param string $username The username of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserByUsername($username)
  {
    return $this->start()->uri("/api/user")
        ->urlParameter("username", $username)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user by a verificationId. The intended use of this API is to retrieve a user after the forgot
   * password workflow has been initiated and you may not know the user's email or username.
   *
   * @param string $verificationId The unique verification Id that has been set on the user object.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserByVerificationId($verificationId)
  {
    return $this->start()->uri("/api/user")
        ->urlParameter("verificationId", $verificationId)
        ->get()
        ->go();
  }

  /**
   * Retrieve a user_code that is part of an in-progress Device Authorization Grant.
   * 
   * This API is useful if you want to build your own login workflow to complete a device grant.
   *
   * @param string $client_id The client Id.
   * @param string $client_secret The client Id.
   * @param string $user_code The end-user verification code.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserCode($client_id, $client_secret, $user_code)
  {
    $post_data = array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'user_code' => $user_code
    );
    return $this->startAnonymous()->uri("/oauth2/device/user-code")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->get()
        ->go();
  }

  /**
   * Retrieve a user_code that is part of an in-progress Device Authorization Grant.
   * 
   * This API is useful if you want to build your own login workflow to complete a device grant.
   * 
   * This request will require an API key.
   *
   * @param string $user_code The end-user verification code.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserCodeUsingAPIKey($user_code)
  {
    $post_data = array(
      'user_code' => $user_code
    );
    return $this->startAnonymous()->uri("/oauth2/device/user-code")
        ->bodyHandler(new FormDataBodyHandler($post_data))
        ->get()
        ->go();
  }

  /**
   * Retrieves all the comments for the user with the given Id.
   *
   * @param string $userId The Id of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserComments($userId)
  {
    return $this->start()->uri("/api/user/comment")
        ->urlSegment($userId)
        ->get()
        ->go();
  }

  /**
   * Retrieve a single User consent by Id.
   *
   * @param string $userConsentId The User consent Id
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserConsent($userConsentId)
  {
    return $this->start()->uri("/api/user/consent")
        ->urlSegment($userConsentId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the consents for a User.
   *
   * @param string $userId The User's Id
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserConsents($userId)
  {
    return $this->start()->uri("/api/user/consent")
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Call the UserInfo endpoint to retrieve User Claims from the access token issued by FusionAuth.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserInfoFromAccessToken($encodedJWT)
  {
    return $this->startAnonymous()->uri("/oauth2/userinfo")
        ->authorization("Bearer " . $encodedJWT)
        ->get()
        ->go();
  }

  /**
   * Retrieve a single Identity Provider user (link).
   *
   * @param string $identityProviderId The unique Id of the identity provider.
   * @param string $identityProviderUserId The unique Id of the user in the 3rd party identity provider.
   * @param string $userId The unique Id of the FusionAuth user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserLink($identityProviderId, $identityProviderUserId, $userId)
  {
    return $this->start()->uri("/api/identity-provider/link")
        ->urlParameter("identityProviderId", $identityProviderId)
        ->urlParameter("identityProviderUserId", $identityProviderUserId)
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieve all Identity Provider users (links) for the user. Specify the optional identityProviderId to retrieve links for a particular IdP.
   *
   * @param string $identityProviderId (Optional) The unique Id of the identity provider. Specify this value to reduce the links returned to those for a particular IdP.
   * @param string $userId The unique Id of the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserLinksByUserId($identityProviderId, $userId)
  {
    return $this->start()->uri("/api/identity-provider/link")
        ->urlParameter("identityProviderId", $identityProviderId)
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the login report between the two instants for a particular user by Id. If you specify an application Id, it will only return the
   * login counts for that application.
   *
   * @param string $applicationId (Optional) The application Id.
   * @param string $userId The userId Id.
   * @param array $start The start instant as UTC milliseconds since Epoch.
   * @param array $end The end instant as UTC milliseconds since Epoch.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserLoginReport($applicationId, $userId, $start, $end)
  {
    return $this->start()->uri("/api/report/login")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("userId", $userId)
        ->urlParameter("start", $start)
        ->urlParameter("end", $end)
        ->get()
        ->go();
  }

  /**
   * Retrieves the login report between the two instants for a particular user by login Id. If you specify an application Id, it will only return the
   * login counts for that application.
   *
   * @param string $applicationId (Optional) The application Id.
   * @param string $loginId The userId Id.
   * @param array $start The start instant as UTC milliseconds since Epoch.
   * @param array $end The end instant as UTC milliseconds since Epoch.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserLoginReportByLoginId($applicationId, $loginId, $start, $end)
  {
    return $this->start()->uri("/api/report/login")
        ->urlParameter("applicationId", $applicationId)
        ->urlParameter("loginId", $loginId)
        ->urlParameter("start", $start)
        ->urlParameter("end", $end)
        ->get()
        ->go();
  }

  /**
   * Retrieves the last number of login records for a user.
   *
   * @param string $userId The Id of the user.
   * @param int $offset The initial record. e.g. 0 is the last login, 100 will be the 100th most recent login.
   * @param int $limit (Optional, defaults to 10) The number of records to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserRecentLogins($userId, $offset, $limit)
  {
    return $this->start()->uri("/api/user/recent-login")
        ->urlParameter("userId", $userId)
        ->urlParameter("offset", $offset)
        ->urlParameter("limit", $limit)
        ->get()
        ->go();
  }

  /**
   * Retrieves the user for the given Id. This method does not use an API key, instead it uses a JSON Web Token (JWT) for authentication.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveUserUsingJWT($encodedJWT)
  {
    return $this->startAnonymous()->uri("/api/user")
        ->authorization("Bearer " . $encodedJWT)
        ->get()
        ->go();
  }

  /**
   * Retrieves the FusionAuth version string.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveVersion()
  {
    return $this->start()->uri("/api/system/version")
        ->get()
        ->go();
  }

  /**
   * Retrieves the WebAuthn credential for the given Id.
   *
   * @param string $id The Id of the WebAuthn credential.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveWebAuthnCredential($id)
  {
    return $this->start()->uri("/api/webauthn")
        ->urlSegment($id)
        ->get()
        ->go();
  }

  /**
   * Retrieves all WebAuthn credentials for the given user.
   *
   * @param string $userId The user's ID.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveWebAuthnCredentialsForUser($userId)
  {
    return $this->start()->uri("/api/webauthn")
        ->urlParameter("userId", $userId)
        ->get()
        ->go();
  }

  /**
   * Retrieves the webhook for the given Id. If you pass in null for the Id, this will return all the webhooks.
   *
   * @param string $webhookId (Optional) The Id of the webhook.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveWebhook($webhookId = NULL)
  {
    return $this->start()->uri("/api/webhook")
        ->urlSegment($webhookId)
        ->get()
        ->go();
  }

  /**
   * Retrieves a single webhook attempt log for the given Id.
   *
   * @param string $webhookAttemptLogId The Id of the webhook attempt log to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveWebhookAttemptLog($webhookAttemptLogId)
  {
    return $this->start()->uri("/api/system/webhook-attempt-log")
        ->urlSegment($webhookAttemptLogId)
        ->get()
        ->go();
  }

  /**
   * Retrieves a single webhook event log for the given Id.
   *
   * @param string $webhookEventLogId The Id of the webhook event log to retrieve.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveWebhookEventLog($webhookEventLogId)
  {
    return $this->start()->uri("/api/system/webhook-event-log")
        ->urlSegment($webhookEventLogId)
        ->get()
        ->go();
  }

  /**
   * Retrieves all the webhooks.
   *
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function retrieveWebhooks()
  {
    return $this->start()->uri("/api/webhook")
        ->get()
        ->go();
  }

  /**
   * Revokes refresh tokens.
   * 
   * Usage examples:
   *   - Delete a single refresh token, pass in only the token.
   *       revokeRefreshToken(token)
   * 
   *   - Delete all refresh tokens for a user, pass in only the userId.
   *       revokeRefreshToken(null, userId)
   * 
   *   - Delete all refresh tokens for a user for a specific application, pass in both the userId and the applicationId.
   *       revokeRefreshToken(null, userId, applicationId)
   * 
   *   - Delete all refresh tokens for an application
   *       revokeRefreshToken(null, null, applicationId)
   * 
   * Note: <code>null</code> may be handled differently depending upon the programming language.
   * 
   * See also: (method names may vary by language... but you'll figure it out)
   * 
   *  - revokeRefreshTokenById
   *  - revokeRefreshTokenByToken
   *  - revokeRefreshTokensByUserId
   *  - revokeRefreshTokensByApplicationId
   *  - revokeRefreshTokensByUserIdForApplication
   *
   * @param string $token (Optional) The refresh token to delete.
   * @param string $userId (Optional) The user Id whose tokens to delete.
   * @param string $applicationId (Optional) The application Id of the tokens to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshToken($token, $userId, $applicationId = NULL)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlParameter("token", $token)
        ->urlParameter("userId", $userId)
        ->urlParameter("applicationId", $applicationId)
        ->delete()
        ->go();
  }

  /**
   * Revokes a single refresh token by the unique Id. The unique Id is not sensitive as it cannot be used to obtain another JWT.
   *
   * @param string $tokenId The unique Id of the token to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshTokenById($tokenId)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlSegment($tokenId)
        ->delete()
        ->go();
  }

  /**
   * Revokes a single refresh token by using the actual refresh token value. This refresh token value is sensitive, so  be careful with this API request.
   *
   * @param string $token The refresh token to delete.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshTokenByToken($token)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlParameter("token", $token)
        ->delete()
        ->go();
  }

  /**
   * Revoke all refresh tokens that belong to an application by applicationId.
   *
   * @param string $applicationId The unique Id of the application that you want to delete all refresh tokens for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshTokensByApplicationId($applicationId)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlParameter("applicationId", $applicationId)
        ->delete()
        ->go();
  }

  /**
   * Revoke all refresh tokens that belong to a user by user Id.
   *
   * @param string $userId The unique Id of the user that you want to delete all refresh tokens for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshTokensByUserId($userId)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlParameter("userId", $userId)
        ->delete()
        ->go();
  }

  /**
   * Revoke all refresh tokens that belong to a user by user Id for a specific application by applicationId.
   *
   * @param string $userId The unique Id of the user that you want to delete all refresh tokens for.
   * @param string $applicationId The unique Id of the application that you want to delete refresh tokens for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshTokensByUserIdForApplication($userId, $applicationId)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->urlParameter("userId", $userId)
        ->urlParameter("applicationId", $applicationId)
        ->delete()
        ->go();
  }

  /**
   * Revokes refresh tokens using the information in the JSON body. The handling for this method is the same as the revokeRefreshToken method
   * and is based on the information you provide in the RefreshDeleteRequest object. See that method for additional information.
   *
   * @param array $request The request information used to revoke the refresh tokens.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeRefreshTokensWithRequest($request)
  {
    return $this->start()->uri("/api/jwt/refresh")
        ->bodyHandler(new JSONBodyHandler($request))
        ->delete()
        ->go();
  }

  /**
   * Revokes a single User consent by Id.
   *
   * @param string $userConsentId The User Consent Id
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function revokeUserConsent($userConsentId)
  {
    return $this->start()->uri("/api/user/consent")
        ->urlSegment($userConsentId)
        ->delete()
        ->go();
  }

  /**
   * Searches applications with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchApplications($request)
  {
    return $this->start()->uri("/api/application/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches the audit logs with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchAuditLogs($request)
  {
    return $this->start()->uri("/api/system/audit-log/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches consents with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchConsents($request)
  {
    return $this->start()->uri("/api/consent/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches email templates with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchEmailTemplates($request)
  {
    return $this->start()->uri("/api/email/template/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches entities with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchEntities($request)
  {
    return $this->start()->uri("/api/entity/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Retrieves the entities for the given Ids. If any Id is invalid, it is ignored.
   *
   * @param array $ids The entity ids to search for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchEntitiesByIds($ids)
  {
    return $this->start()->uri("/api/entity/search")
        ->urlParameter("ids", $ids)
        ->get()
        ->go();
  }

  /**
   * Searches Entity Grants with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchEntityGrants($request)
  {
    return $this->start()->uri("/api/entity/grant/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches the entity types with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchEntityTypes($request)
  {
    return $this->start()->uri("/api/entity/type/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches the event logs with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchEventLogs($request)
  {
    return $this->start()->uri("/api/system/event-log/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches group members with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchGroupMembers($request)
  {
    return $this->start()->uri("/api/group/member/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches groups with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchGroups($request)
  {
    return $this->start()->uri("/api/group/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches the IP Access Control Lists with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchIPAccessControlLists($request)
  {
    return $this->start()->uri("/api/ip-acl/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches identity providers with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchIdentityProviders($request)
  {
    return $this->start()->uri("/api/identity-provider/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches keys with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchKeys($request)
  {
    return $this->start()->uri("/api/key/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches lambdas with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchLambdas($request)
  {
    return $this->start()->uri("/api/lambda/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches the login records with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchLoginRecords($request)
  {
    return $this->start()->uri("/api/system/login-record/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches tenants with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchTenants($request)
  {
    return $this->start()->uri("/api/tenant/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches themes with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchThemes($request)
  {
    return $this->start()->uri("/api/theme/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches user comments with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchUserComments($request)
  {
    return $this->start()->uri("/api/user/comment/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Retrieves the users for the given Ids. If any Id is invalid, it is ignored.
   *
   * @param array $ids The user ids to search for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to searchUsersByIds, use that method instead.
   */
  public function searchUsers($ids)
  {
    return $this->start()->uri("/api/user/search")
        ->urlParameter("ids", $ids)
        ->get()
        ->go();
  }

  /**
   * Retrieves the users for the given Ids. If any Id is invalid, it is ignored.
   *
   * @param array $ids The user Ids to search for.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchUsersByIds($ids)
  {
    return $this->start()->uri("/api/user/search")
        ->urlParameter("ids", $ids)
        ->get()
        ->go();
  }

  /**
   * Retrieves the users for the given search criteria and pagination.
   *
   * @param array $request The search criteria and pagination constraints. Fields used: ids, query, queryString, numberOfResults, orderBy, startRow,
  *     and sortFields.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchUsersByQuery($request)
  {
    return $this->start()->uri("/api/user/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Retrieves the users for the given search criteria and pagination.
   *
   * @param array $request The search criteria and pagination constraints. Fields used: ids, query, queryString, numberOfResults, orderBy, startRow,
  *     and sortFields.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to searchUsersByQuery, use that method instead.
   */
  public function searchUsersByQueryString($request)
  {
    return $this->start()->uri("/api/user/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches the webhook event logs with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchWebhookEventLogs($request)
  {
    return $this->start()->uri("/api/system/webhook-event-log/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Searches webhooks with the specified criteria and pagination.
   *
   * @param array $request The search criteria and pagination information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function searchWebhooks($request)
  {
    return $this->start()->uri("/api/webhook/search")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Send an email using an email template Id. You can optionally provide <code>requestData</code> to access key value
   * pairs in the email template.
   *
   * @param string $emailTemplateId The Id for the template.
   * @param array $request The send email request that contains all the information used to send the email.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function sendEmail($emailTemplateId, $request)
  {
    return $this->start()->uri("/api/email/send")
        ->urlSegment($emailTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Sends out an email to a parent that they need to register and create a family or need to log in and add a child to their existing family.
   *
   * @param array $request The request object that contains the parent email.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function sendFamilyRequestEmail($request)
  {
    return $this->start()->uri("/api/user/family/request")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Send a passwordless authentication code in an email to complete login.
   *
   * @param array $request The passwordless send request that contains all the information used to send an email containing a code.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function sendPasswordlessCode($request)
  {
    return $this->startAnonymous()->uri("/api/passwordless/send")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Send a Two Factor authentication code to assist in setting up Two Factor authentication or disabling.
   *
   * @param array $request The request object that contains all the information used to send the code.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to sendTwoFactorCodeForEnableDisable, use that method instead.
   */
  public function sendTwoFactorCode($request)
  {
    return $this->start()->uri("/api/two-factor/send")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Send a Two Factor authentication code to assist in setting up Two Factor authentication or disabling.
   *
   * @param array $request The request object that contains all the information used to send the code.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function sendTwoFactorCodeForEnableDisable($request)
  {
    return $this->start()->uri("/api/two-factor/send")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Send a Two Factor authentication code to allow the completion of Two Factor authentication.
   *
   * @param string $twoFactorId The Id returned by the Login API necessary to complete Two Factor authentication.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to sendTwoFactorCodeForLoginUsingMethod, use that method instead.
   */
  public function sendTwoFactorCodeForLogin($twoFactorId)
  {
    return $this->startAnonymous()->uri("/api/two-factor/send")
        ->urlSegment($twoFactorId)
        ->post()
        ->go();
  }

  /**
   * Send a Two Factor authentication code to allow the completion of Two Factor authentication.
   *
   * @param string $twoFactorId The Id returned by the Login API necessary to complete Two Factor authentication.
   * @param array $request The Two Factor send request that contains all the information used to send the Two Factor code to the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function sendTwoFactorCodeForLoginUsingMethod($twoFactorId, $request)
  {
    return $this->startAnonymous()->uri("/api/two-factor/send")
        ->urlSegment($twoFactorId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Begins a login request for a 3rd party login that requires user interaction such as HYPR.
   *
   * @param array $request The third-party login request that contains information from the third-party login
  *     providers that FusionAuth uses to reconcile the user's account.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function startIdentityProviderLogin($request)
  {
    return $this->start()->uri("/api/identity-provider/start")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Start a passwordless login request by generating a passwordless code. This code can be sent to the User using the Send
   * Passwordless Code API or using a mechanism outside of FusionAuth. The passwordless login is completed by using the Passwordless Login API with this code.
   *
   * @param array $request The passwordless start request that contains all the information used to begin the passwordless login request.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function startPasswordlessLogin($request)
  {
    return $this->start()->uri("/api/passwordless/start")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Start a Two-Factor login request by generating a two-factor identifier. This code can then be sent to the Two Factor Send 
   * API (/api/two-factor/send)in order to send a one-time use code to a user. You can also use one-time use code returned 
   * to send the code out-of-band. The Two-Factor login is completed by making a request to the Two-Factor Login 
   * API (/api/two-factor/login). with the two-factor identifier and the one-time use code.
   * 
   * This API is intended to allow you to begin a Two-Factor login outside a normal login that originated from the Login API (/api/login).
   *
   * @param array $request The Two-Factor start request that contains all the information used to begin the Two-Factor login request.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function startTwoFactorLogin($request)
  {
    return $this->start()->uri("/api/two-factor/start")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Start a WebAuthn authentication ceremony by generating a new challenge for the user
   *
   * @param array $request An object containing data necessary for starting the authentication ceremony
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function startWebAuthnLogin($request)
  {
    return $this->start()->uri("/api/webauthn/start")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Start a WebAuthn registration ceremony by generating a new challenge for the user
   *
   * @param array $request An object containing data necessary for starting the registration ceremony
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function startWebAuthnRegistration($request)
  {
    return $this->start()->uri("/api/webauthn/register/start")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Complete login using a 2FA challenge
   *
   * @param array $request The login request that contains the user credentials used to log them in.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function twoFactorLogin($request)
  {
    return $this->startAnonymous()->uri("/api/two-factor/login")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Updates an API key with the given Id.
   *
   * @param string $keyId The Id of the API key to update.
   * @param array $request The request that contains all the new API key information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateAPIKey($keyId, $request)
  {
    return $this->start()->uri("/api/api-key")
        ->urlSegment($keyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the application with the given Id.
   *
   * @param string $applicationId The Id of the application to update.
   * @param array $request The request that contains all the new application information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateApplication($applicationId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the application role with the given Id for the application.
   *
   * @param string $applicationId The Id of the application that the role belongs to.
   * @param string $roleId The Id of the role to update.
   * @param array $request The request that contains all the new role information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateApplicationRole($applicationId, $roleId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("role")
        ->urlSegment($roleId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the connector with the given Id.
   *
   * @param string $connectorId The Id of the connector to update.
   * @param array $request The request object that contains all the new connector information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateConnector($connectorId, $request)
  {
    return $this->start()->uri("/api/connector")
        ->urlSegment($connectorId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the consent with the given Id.
   *
   * @param string $consentId The Id of the consent to update.
   * @param array $request The request that contains all the new consent information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateConsent($consentId, $request)
  {
    return $this->start()->uri("/api/consent")
        ->urlSegment($consentId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the email template with the given Id.
   *
   * @param string $emailTemplateId The Id of the email template to update.
   * @param array $request The request that contains all the new email template information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateEmailTemplate($emailTemplateId, $request)
  {
    return $this->start()->uri("/api/email/template")
        ->urlSegment($emailTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the Entity with the given Id.
   *
   * @param string $entityId The Id of the Entity to update.
   * @param array $request The request that contains all the new Entity information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateEntity($entityId, $request)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the Entity Type with the given Id.
   *
   * @param string $entityTypeId The Id of the Entity Type to update.
   * @param array $request The request that contains all the new Entity Type information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateEntityType($entityTypeId, $request)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the permission with the given Id for the entity type.
   *
   * @param string $entityTypeId The Id of the entityType that the permission belongs to.
   * @param string $permissionId The Id of the permission to update.
   * @param array $request The request that contains all the new permission information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateEntityTypePermission($entityTypeId, $permissionId, $request)
  {
    return $this->start()->uri("/api/entity/type")
        ->urlSegment($entityTypeId)
        ->urlSegment("permission")
        ->urlSegment($permissionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates a family with a given Id.
   *
   * @param string $familyId The Id of the family to update.
   * @param array $request The request object that contains all the new family information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateFamily($familyId, $request)
  {
    return $this->start()->uri("/api/user/family")
        ->urlSegment($familyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the form with the given Id.
   *
   * @param string $formId The Id of the form to update.
   * @param array $request The request object that contains all the new form information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateForm($formId, $request)
  {
    return $this->start()->uri("/api/form")
        ->urlSegment($formId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the form field with the given Id.
   *
   * @param string $fieldId The Id of the form field to update.
   * @param array $request The request object that contains all the new form field information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateFormField($fieldId, $request)
  {
    return $this->start()->uri("/api/form/field")
        ->urlSegment($fieldId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the group with the given Id.
   *
   * @param string $groupId The Id of the group to update.
   * @param array $request The request that contains all the new group information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateGroup($groupId, $request)
  {
    return $this->start()->uri("/api/group")
        ->urlSegment($groupId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Creates a member in a group.
   *
   * @param array $request The request object that contains all the information used to create the group member(s).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateGroupMembers($request)
  {
    return $this->start()->uri("/api/group/member")
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the IP Access Control List with the given Id.
   *
   * @param string $accessControlListId The Id of the IP Access Control List to update.
   * @param array $request The request that contains all the new IP Access Control List information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateIPAccessControlList($accessControlListId, $request)
  {
    return $this->start()->uri("/api/ip-acl")
        ->urlSegment($accessControlListId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the identity provider with the given Id.
   *
   * @param string $identityProviderId The Id of the identity provider to update.
   * @param array $request The request object that contains the updated identity provider.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateIdentityProvider($identityProviderId, $request)
  {
    return $this->start()->uri("/api/identity-provider")
        ->urlSegment($identityProviderId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the available integrations.
   *
   * @param array $request The request that contains all the new integration information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateIntegrations($request)
  {
    return $this->start()->uri("/api/integration")
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the key with the given Id.
   *
   * @param string $keyId The Id of the key to update.
   * @param array $request The request that contains all the new key information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateKey($keyId, $request)
  {
    return $this->start()->uri("/api/key")
        ->urlSegment($keyId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the lambda with the given Id.
   *
   * @param string $lambdaId The Id of the lambda to update.
   * @param array $request The request that contains all the new lambda information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateLambda($lambdaId, $request)
  {
    return $this->start()->uri("/api/lambda")
        ->urlSegment($lambdaId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the message template with the given Id.
   *
   * @param string $messageTemplateId The Id of the message template to update.
   * @param array $request The request that contains all the new message template information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateMessageTemplate($messageTemplateId, $request)
  {
    return $this->start()->uri("/api/message/template")
        ->urlSegment($messageTemplateId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the messenger with the given Id.
   *
   * @param string $messengerId The Id of the messenger to update.
   * @param array $request The request object that contains all the new messenger information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateMessenger($messengerId, $request)
  {
    return $this->start()->uri("/api/messenger")
        ->urlSegment($messengerId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the OAuth scope with the given Id for the application.
   *
   * @param string $applicationId The Id of the application that the OAuth scope belongs to.
   * @param string $scopeId The Id of the OAuth scope to update.
   * @param array $request The request that contains all the new OAuth scope information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateOAuthScope($applicationId, $scopeId, $request)
  {
    return $this->start()->uri("/api/application")
        ->urlSegment($applicationId)
        ->urlSegment("scope")
        ->urlSegment($scopeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the registration for the user with the given Id and the application defined in the request.
   *
   * @param string $userId The Id of the user whose registration is going to be updated.
   * @param array $request The request that contains all the new registration information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateRegistration($userId, $request)
  {
    return $this->start()->uri("/api/user/registration")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the system configuration.
   *
   * @param array $request The request that contains all the new system configuration information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateSystemConfiguration($request)
  {
    return $this->start()->uri("/api/system-configuration")
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the tenant with the given Id.
   *
   * @param string $tenantId The Id of the tenant to update.
   * @param array $request The request that contains all the new tenant information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateTenant($tenantId, $request)
  {
    return $this->start()->uri("/api/tenant")
        ->urlSegment($tenantId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the theme with the given Id.
   *
   * @param string $themeId The Id of the theme to update.
   * @param array $request The request that contains all the new theme information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateTheme($themeId, $request)
  {
    return $this->start()->uri("/api/theme")
        ->urlSegment($themeId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the user with the given Id.
   *
   * @param string $userId The Id of the user to update.
   * @param array $request The request that contains all the new user information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateUser($userId, $request)
  {
    return $this->start()->uri("/api/user")
        ->urlSegment($userId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the user action with the given Id.
   *
   * @param string $userActionId The Id of the user action to update.
   * @param array $request The request that contains all the new user action information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateUserAction($userActionId, $request)
  {
    return $this->start()->uri("/api/user-action")
        ->urlSegment($userActionId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the user action reason with the given Id.
   *
   * @param string $userActionReasonId The Id of the user action reason to update.
   * @param array $request The request that contains all the new user action reason information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateUserActionReason($userActionReasonId, $request)
  {
    return $this->start()->uri("/api/user-action-reason")
        ->urlSegment($userActionReasonId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates a single User consent by Id.
   *
   * @param string $userConsentId The User Consent Id
   * @param array $request The request that contains the user consent information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateUserConsent($userConsentId, $request)
  {
    return $this->start()->uri("/api/user/consent")
        ->urlSegment($userConsentId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Updates the webhook with the given Id.
   *
   * @param string $webhookId The Id of the webhook to update.
   * @param array $request The request that contains all the new webhook information.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function updateWebhook($webhookId, $request)
  {
    return $this->start()->uri("/api/webhook")
        ->urlSegment($webhookId)
        ->bodyHandler(new JSONBodyHandler($request))
        ->put()
        ->go();
  }

  /**
   * Creates or updates an Entity Grant. This is when a User/Entity is granted permissions to an Entity.
   *
   * @param string $entityId The Id of the Entity that the User/Entity is being granted access to.
   * @param array $request The request object that contains all the information used to create the Entity Grant.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function upsertEntityGrant($entityId, $request)
  {
    return $this->start()->uri("/api/entity")
        ->urlSegment($entityId)
        ->urlSegment("grant")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Validates the end-user provided user_code from the user-interaction of the Device Authorization Grant.
   * If you build your own activation form you should validate the user provided code prior to beginning the Authorization grant.
   *
   * @param string $user_code The end-user verification code.
   * @param string $client_id The client Id.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function validateDevice($user_code, $client_id)
  {
    return $this->startAnonymous()->uri("/oauth2/device/validate")
        ->urlParameter("user_code", $user_code)
        ->urlParameter("client_id", $client_id)
        ->get()
        ->go();
  }

  /**
   * Validates the provided JWT (encoded JWT string) to ensure the token is valid. A valid access token is properly
   * signed and not expired.
   * <p>
   * This API may be used to verify the JWT as well as decode the encoded JWT into human readable identity claims.
   *
   * @param string $encodedJWT The encoded JWT (access token).
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function validateJWT($encodedJWT)
  {
    return $this->startAnonymous()->uri("/api/jwt/validate")
        ->authorization("Bearer " . $encodedJWT)
        ->get()
        ->go();
  }

  /**
   * It's a JWT vending machine!
   * 
   * Issue a new access token (JWT) with the provided claims in the request. This JWT is not scoped to a tenant or user, it is a free form 
   * token that will contain what claims you provide.
   * <p>
   * The iat, exp and jti claims will be added by FusionAuth, all other claims must be provided by the caller.
   * 
   * If a TTL is not provided in the request, the TTL will be retrieved from the default Tenant or the Tenant specified on the request either 
   * by way of the X-FusionAuth-TenantId request header, or a tenant scoped API key.
   *
   * @param array $request The request that contains all the claims for this JWT.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function vendJWT($request)
  {
    return $this->start()->uri("/api/jwt/vend")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Confirms a email verification. The Id given is usually from an email sent to the user.
   *
   * @param string $verificationId The email verification Id sent to the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to verifyEmailAddress and changed to take a JSON request body, use that method instead.
   */
  public function verifyEmail($verificationId)
  {
    return $this->startAnonymous()->uri("/api/user/verify-email")
        ->urlSegment($verificationId)
        ->post()
        ->go();
  }

  /**
   * Confirms a user's email address. 
   * 
   * The request body will contain the verificationId. You may also be required to send a one-time use code based upon your configuration. When 
   * the tenant is configured to gate a user until their email address is verified, this procedures requires two values instead of one. 
   * The verificationId is a high entropy value and the one-time use code is a low entropy value that is easily entered in a user interactive form. The 
   * two values together are able to confirm a user's email address and mark the user's email address as verified.
   *
   * @param array $request The request that contains the verificationId and optional one-time use code paired with the verificationId.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function verifyEmailAddress($request)
  {
    return $this->startAnonymous()->uri("/api/user/verify-email")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Administratively verify a user's email address. Use this method to bypass email verification for the user.
   * 
   * The request body will contain the userId to be verified. An API key is required when sending the userId in the request body.
   *
   * @param array $request The request that contains the userId to verify.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function verifyEmailAddressByUserId($request)
  {
    return $this->start()->uri("/api/user/verify-email")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }

  /**
   * Confirms an application registration. The Id given is usually from an email sent to the user.
   *
   * @param string $verificationId The registration verification Id sent to the user.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   * @deprecated This method has been renamed to verifyUserRegistration and changed to take a JSON request body, use that method instead.
   */
  public function verifyRegistration($verificationId)
  {
    return $this->startAnonymous()->uri("/api/user/verify-registration")
        ->urlSegment($verificationId)
        ->post()
        ->go();
  }

  /**
   * Confirms a user's registration. 
   * 
   * The request body will contain the verificationId. You may also be required to send a one-time use code based upon your configuration. When 
   * the application is configured to gate a user until their registration is verified, this procedures requires two values instead of one. 
   * The verificationId is a high entropy value and the one-time use code is a low entropy value that is easily entered in a user interactive form. The 
   * two values together are able to confirm a user's registration and mark the user's registration as verified.
   *
   * @param array $request The request that contains the verificationId and optional one-time use code paired with the verificationId.
   *
   * @return ClientResponse The ClientResponse.
   * @throws \Exception
   */
  public function verifyUserRegistration($request)
  {
    return $this->startAnonymous()->uri("/api/user/verify-registration")
        ->bodyHandler(new JSONBodyHandler($request))
        ->post()
        ->go();
  }


  private function start()
  {
    return $this->startAnonymous()->authorization($this->apiKey);
  }

  private function startAnonymous()
  {
    $rest = new RESTClient();
    if (isset($this->tenantId)) {
      $rest->header("X-FusionAuth-TenantId", $this->tenantId);
    }
    return $rest->url($this->baseURL)
        ->connectTimeout($this->connectTimeout)
        ->readTimeout($this->readTimeout)
        ->successResponseHandler(new JSONResponseHandler())
        ->errorResponseHandler(new JSONResponseHandler());
  }
}
