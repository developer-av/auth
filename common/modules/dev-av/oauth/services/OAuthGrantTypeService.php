<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 17.08.2021
 * Time: 12:29
 */

namespace DevAV\oauth\services;

use DevAV\oauth\GrantType\GrantTypeInterface;
use yii\web\BadRequestHttpException;
use yii\web\Request;
use yii\web\Response;

class OAuthGrantTypeService
{
    /**
     * @var array
     */
    private $grantTypes;
    /**
     * @var \DevAV\oauth\services\OAuthAccessTokenService
     */
    private $accessTokenService;

    public function __construct(array $grantTypes, OAuthAccessTokenService $accessTokenService)
    {
        $this->grantTypes = $grantTypes;
        $this->accessTokenService = $accessTokenService;
    }

    public function getGrantTypeClass(Request $request, Response $response): GrantTypeInterface
    {
        if (!$grantTypeIdentifier = $request->post('grant_type') ?? $request->get('grant_type')) {
            throw new BadRequestHttpException('The grant type was not specified in the request');
        }
        if (!isset($this->grantTypes[$grantTypeIdentifier])) {
            throw new BadRequestHttpException('Grant type "' . $grantTypeIdentifier . '" not supported');
        }
        return new $this->grantTypes[$grantTypeIdentifier]($this->accessTokenService);
    }
}