<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 16.08.2021
 * Time: 16:23
 */

namespace DevAV\oauth\services;

use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Request;
use yii\web\Response;

class OAuthService
{
    /**
     * @var \DevAV\oauth\services\OAuthClientService
     */
    private $oAuthClientService;
    /**
     * @var \DevAV\oauth\services\OAuthGrantTypeService
     */
    private $oAuthGrantTypeService;
    /**
     * @var \DevAV\oauth\services\OAuthAccessTokenService
     */
    private $oAuthAccessTokenService;

    public function __construct(
        OAuthClientService $oAuthClientService,
        OAuthGrantTypeService $oAuthGrantTypeService,
        OAuthAccessTokenService $oAuthAccessTokenService
    ) {
        $this->oAuthClientService = $oAuthClientService;
        $this->oAuthGrantTypeService = $oAuthGrantTypeService;
        $this->oAuthAccessTokenService = $oAuthAccessTokenService;
    }

    /**
     * @param \yii\web\Request $request
     * @param \yii\web\Response $response
     * @return array|void
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\MethodNotAllowedHttpException
     */
    public function handleTokenRequest(Request $request, Response $response)
    {
        if ($token = $this->grantAccessToken($request, $response)) {
            return $token;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return array
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\MethodNotAllowedHttpException
     */
    private function grantAccessToken(Request $request, Response $response)
    {
        if (!$request->isPost) {
            throw new MethodNotAllowedHttpException('The request method must be POST when requesting an access token');
        }

        $grantType = $this->oAuthGrantTypeService->getGrantTypeClass($request, $response);
        $clientId = $this->oAuthClientService->getClientId($request, $response);
        $isPublicClient = $this->oAuthClientService->isPublicClient($clientId);
        $grantType->validateRequest($request, $response, $isPublicClient);
        $grantTypeIdentifier = $grantType->getQueryStringIdentifier();

        if (!is_null($storedClientId = $grantType->getClientId()) && $storedClientId != $clientId) {
            throw new BadRequestHttpException($grantTypeIdentifier . ' doesn\'t exist or is invalid for the client');
        }
        $this->oAuthClientService->checkRestrictedGrantType($clientId, $grantTypeIdentifier);
        return $grantType->createAccessToken($this->oAuthAccessTokenService, $clientId, $grantType->getUserId(), $isPublicClient, $response);
    }

}