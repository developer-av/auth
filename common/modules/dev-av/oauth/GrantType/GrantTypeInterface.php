<?php

namespace DevAV\oauth\GrantType;

use DevAV\oauth\services\OAuthAccessTokenService;
use yii\web\Request;
use yii\web\Response;

/**
 * Interface for all OAuth2 Grant Types
 */
interface GrantTypeInterface
{
    public function __construct(OAuthAccessTokenService $accessTokenService);
    
    /**
     * Get query string identifier
     *
     * @return string
     */
    public function getQueryStringIdentifier(): string;

    /**
     * @param Request $request
     * @param Response $response
     * @param bool $isPublicClient
     * @return mixed
     */
    public function validateRequest(Request $request, Response $response, bool $isPublicClient);

    /**
     * Create access token
     *
     * @param OAuthAccessTokenService $oAuthAccessTokenService
     * @param mixed $client_id - client identifier related to the access token.
     * @param mixed $user_id - user id associated with the access token
     * @param bool $isPublicClient
     * @return array
     */
    public function createAccessToken(OAuthAccessTokenService $oAuthAccessTokenService, $client_id, $user_id, bool $isPublicClient, Response $response): array;

    /**
     * Get client id
     *
     * @return mixed
     */
    public function getClientId();

    /**
     * Get user id
     *
     * @return mixed
     */
    public function getUserId();
}