<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 17.08.2021
 * Time: 13:47
 */

namespace DevAV\oauth\services;

use yii\db\Query;
use yii\web\ForbiddenHttpException;
use yii\web\Request;

class OAuthAccessTokenService
{
    /**
     * @var array
     */
    public $config;

    public function __construct(array $config)
    {
        $this->config = array_merge(array(
            'access_lifetime' => 3600,
            'refresh_token_lifetime' => 1209600,
        ), $config);
    }

    /**
     * Handle the creation of access token, also issue refresh token if supported / desirable.
     *
     * @param string $clientId - client identifier related to the access token.
     * @param int $userId - user ID associated with the access token
     * @param bool $includeRefreshToken - if true, a new refresh_token will be added to the response
     * @return array
     *
     */
    public function createAccessToken(string $clientId, int $userId, bool $includeRefreshToken = true): array
    {
        $token = [
            "access_token" => $this->generateAccessToken(),
            "expires_in" => $this->config['access_lifetime'],
            "token_type" => 'bearer',
        ];
        $accessTokenId = $this->setAccessToke($token['access_token'], $clientId, $userId,
            $this->config['access_lifetime'] ? time() + $this->config['access_lifetime'] : null);

        if ($includeRefreshToken) {
            $token["refresh_token"] = $this->generateRefreshToken();
            $expires = 0;
            if ($this->config['refresh_token_lifetime'] > 0) {
                $expires = time() + $this->config['refresh_token_lifetime'];
            }
            $this->setRefreshToken($token['refresh_token'], $accessTokenId, $clientId, $userId, $expires);
        }

        return $token;
    }

    private function setRefreshToken(
        string $refreshToken,
        int $accessTokenId,
        string $clientId,
        int $userId,
        ?int $expires = null
    ) {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        $data = [
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'user_id' => $userId,
            'expires' => $expires,
            'access_token_id' => $accessTokenId
        ];

        $query = (new Query())->createCommand();
        $query->insert('oauth_refresh_tokens', $data);
        $query->execute();
    }

    protected function setAccessToke(string $accessToken, string $clientId, int $userId, ?int $expires = null): int
    {
        $expires = date('Y-m-d H:i:s', $expires);

        $data = [
            'expires' => $expires,
            'user_id' => $userId,
            'client_id' => $clientId
        ];
        $query = (new Query())->createCommand();

        if ($this->tokenExist($accessToken)) {
            $query->update('oauth_access_tokens', $data, ['access_token' => $accessToken]);
        } else {
            $data['access_token'] = $accessToken;
            $query->insert('oauth_access_tokens', $data);
        }
        $query->execute();
        return (new Query())->createCommand()->db->getLastInsertID();
    }

    /**
     * Generates an unique refresh token
     *
     * Implementing classes may want to override this function to implement
     * other refresh token generation schemes.
     *
     * @return string - A unique refresh token.
     *
     * @ingroup oauth2_section_4
     * @see OAuth2::generateAccessToken()
     */
    protected function generateRefreshToken()
    {
        return $this->generateAccessToken(); // let's reuse the same scheme for token generation
    }

    protected function generateAccessToken()
    {
        if (function_exists('random_bytes')) {
            $randomData = random_bytes(20);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $randomData = openssl_random_pseudo_bytes(20);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        if (function_exists('mcrypt_create_iv')) {
            $randomData = mcrypt_create_iv(20, MCRYPT_DEV_URANDOM);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        if (@file_exists('/dev/urandom')) { // Get 100 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 20);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        // Last resort which you probably should just get rid of:
        $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);

        return substr(hash('sha512', $randomData), 0, 40);
    }

    private function tokenExist(string $accessToken): bool
    {
        return $this->getQueryByToken($accessToken)->exists();
    }

    private function getQueryByToken(string $accessToken): Query
    {
        return (new Query())->from('oauth_access_tokens')->where(['access_token' => $accessToken]);
    }

    private function getQueryByRefreshToken(string $refreshToken): Query
    {
        return (new Query())->from('oauth_refresh_tokens')->where(['refresh_token' => $refreshToken]);
    }

    public function getRefreshToken(string $refreshToken): ?array
    {
        $token = $this->getQueryByRefreshToken($refreshToken)->one();
        if ($token) {
            $token['expires'] = strtotime($token['expires']);
        }
        return $token ? $token : null;
    }

    public function getAccessToken(string $accessToken): ?array
    {
        $token = $this->getQueryByToken($accessToken)->one();
        if ($token) {
            $token['expires'] = strtotime($token['expires']);
        }
        return $token ? $token : null;
    }

    /**
     * @param string $accessToken
     * @return int
     * @throws \yii\web\ForbiddenHttpException
     */
    public function getUserIdFromToken(string $accessToken): ?int
    {
        $token = $this->getAccessToken($accessToken);
        if ($token){
            $this->checkAccessToken($token);
            return $token['user_id'];
        }
        return null;
    }

    public function checkAccessToken(?array $accessToken)
    {
        if ($accessToken) {
            if (($accessToken['expires'] > 0 && $accessToken["expires"] < time()) || $accessToken['active'] == 0) {
                throw new ForbiddenHttpException('Access token has expired');
            }
        }
    }

    public function markAsUse(string $refreshToken)
    {
        $refreshToken = $this->getQueryByRefreshToken($refreshToken)->select(['id', 'access_token_id'])->one();
        if ($refreshToken) {
            (new Query())->createCommand()->update('oauth_refresh_tokens', ['active' => 0],
                ['id' => $refreshToken['id']])->execute();
            (new Query())->createCommand()->update('oauth_access_tokens', ['active' => 0],
                ['id' => $refreshToken['access_token_id']])->execute();
        }
    }

    public function getBearerToken(Request $request): ?string
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        $pattern = '/^Bearer\s+(.*?)$/';
        if ($authHeader !== null) {
            if (preg_match($pattern, $authHeader, $matches)) {
                return $matches[1];
            } else {
                return null;
            }
        }
        return null;
    }
}