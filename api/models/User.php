<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 13.08.2021
 * Time: 14:12
 */

namespace api\models;

use DevAV\oauth\services\OAuthAccessTokenService;
use Yii;

class User extends \common\models\User
{
    public static function findIdentityByAccessToken($token, $type = null)
    {
        /** @var \DevAV\oauth\services\OAuthAccessTokenService $accessTokenService */
        $accessTokenService = Yii::$container->get(OAuthAccessTokenService::class);
        $userId = $accessTokenService->getUserIdFromToken($token);
        return $userId ? self::findOne($userId) : null;
    }
}