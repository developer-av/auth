<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 16.08.2021
 * Time: 22:58
 */

namespace DevAV\oauth\GrantType;

use DevAV\oauth\services\OAuthAccessTokenService;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\Request;
use yii\web\Response;

class UserCredentials implements GrantTypeInterface
{
    public function __construct(OAuthAccessTokenService $accessTokenService)
    {
    }

    /**
     * @var array
     */
    private $userInfo;

    /**
     * @inheritDoc
     */
    public function validateRequest(Request $request, Response $response, bool $isPublicClient)
    {
        if (!($username = $request->post('username')) || !($password = $request->post('password'))) {
            throw new BadRequestHttpException('Missing parameters: "username" and "password" required');
        }

        if (!$this->checkUserCredentials($username, $password)) {
            throw new ForbiddenHttpException('Invalid username and password combination');
        }

        $userInfo = $this->getUserDetails($username);
        if (!$userInfo) {
            throw new BadRequestHttpException('Unable to retrieve user information');
        }
        if (!isset($userInfo['user_id'])) {
            throw new \LogicException("you must set the user_id on the array returned by getUserDetails");
        }
        $this->userInfo = $userInfo;
    }

    /**
     * @inheritDoc
     */
    public function createAccessToken(
        OAuthAccessTokenService $oAuthAccessTokenService,
        $client_id,
        $user_id,
        bool $isPublicClient,
        Response $response
    ): array {
        $token = $oAuthAccessTokenService->createAccessToken($client_id, $user_id);
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

    private function checkUserCredentials(string $username, string $password): bool
    {
        $passwordHash = $this->getUserPasswordHashByUsername($username);
        return $passwordHash && \Yii::$app->security->validatePassword($password, $passwordHash);
    }

    private function getUserDetails(string $username): array
    {
        return $this->getQueryByUsername($username)->select(['id as user_id'])->one();
    }

    /**
     * @param string $username
     * @return string|null
     */
    private function getUserPasswordHashByUsername(string $username): ?string
    {
        return $this->getQueryByUsername($username)->select('password_hash')->scalar();
    }

    private function getQueryByUsername(string $username): Query
    {
        return (new Query())->from('user')->where(['username' => $username]);
    }

    public function getClientId()
    {
        return null;
    }

    public function getQueryStringIdentifier(): string
    {
        return 'password';
    }

    /**
     * Get user id
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userInfo['user_id'];
    }
}