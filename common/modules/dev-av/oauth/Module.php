<?php
namespace DevAV\oauth;
use DevAV\oauth\GrantType\RefreshToken;
use DevAV\oauth\GrantType\UserCredentials;
use DevAV\oauth\services\OAuthAccessTokenService;
use DevAV\oauth\services\OAuthGrantTypeService;

/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 16.08.2021
 * Time: 14:25
 */
class Module extends \yii\base\Module
{
    /**
     * @var array
     */
    public $grantTypes;
    public $accessTokenConfig = [];
    
    public function init()
    {
        parent::init();
        if (!$this->grantTypes){
            $this->grantTypes = $this->getDefaultGrantTypes();
        }

        \Yii::$container->set(OAuthGrantTypeService::class, [
            '__construct()' => [
                'grantTypes' => $this->grantTypes
            ]
        ]);
        \Yii::$container->set(OAuthAccessTokenService::class, [
            '__construct()' => [
                'config' => $this->accessTokenConfig
            ]
        ]);
    }

    public function getDefaultGrantTypes()
    {
        return [
            'password' => UserCredentials::class,
            'refresh_token' => RefreshToken::class,
        ];
    }
    
    
}