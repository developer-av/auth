<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 16.08.2021
 * Time: 23:01
 */

namespace DevAV\oauth\GrantType;

use DevAV\oauth\services\OAuthAccessTokenService;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\Request;
use yii\web\Response;

class RefreshToken implements GrantTypeInterface
{
    /**
     * @var \DevAV\oauth\services\OAuthAccessTokenService
     */
    private $accessTokenService;
    /**
     * @var array|null
     */
    private $refreshToken;

    public function __construct(OAuthAccessTokenService $accessTokenService)
    {
        $this->accessTokenService = $accessTokenService;
    }

    public function getQueryStringIdentifier(): string
    {
        return 'refresh_token';
    }

    public function validateRequest(Request $request, Response $response, bool $isPublicClient)
    {
        if ($isPublicClient) {
            if (!($accessToken = $this->accessTokenService->getBearerToken($request))) {
                throw new BadRequestHttpException('Auth is required');
            }
            if (!($accessToken = $this->accessTokenService->getAccessToken($accessToken))) {
                throw new ForbiddenHttpException('Invalid access token');
            }
        }
        if ($isPublicClient) {
            $refreshToken = $request->cookies->getValue('rt');
        } else {
            $refreshToken = $request->post('refresh_token');
        }
        if (!$refreshToken) {
            throw new BadRequestHttpException('Missing parameter: "refresh_token" is required');
        }
        if ((!$refreshToken = $this->accessTokenService->getRefreshToken($refreshToken)) || ($isPublicClient && $refreshToken['access_token_id'] != $accessToken['id'])) {
            throw new BadRequestHttpException('Invalid refresh token');
        }
        if (($refreshToken['expires'] > 0 && $refreshToken["expires"] < time()) || $refreshToken['active'] == 0) {
            throw new BadRequestHttpException('Refresh token has expired');
        }
        $this->refreshToken = $refreshToken;
    }

    public function createAccessToken(OAuthAccessTokenService $oAuthAccessTokenService, $client_id, $user_id, bool $isPublicClient, Response $response): array
    {
        $token = $oAuthAccessTokenService->createAccessToken($client_id, $user_id);
        $oAuthAccessTokenService->markAsUse($this->refreshToken['refresh_token']);
        if ($isPublicClient) {
            $refreshToken = $token['refresh_token'];
            $response->cookies->add(new Cookie([
                'name' => 'rt',
                'value' => $refreshToken
            ]));
            unset($token['refresh_token']);
        }
        return $token;
    }

    public function getClientId()
    {
        return $this->refreshToken['client_id'];
    }

    public function getUserId()
    {
        return $this->refreshToken['user_id'];
    }
}