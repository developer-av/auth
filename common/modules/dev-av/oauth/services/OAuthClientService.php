<?php
/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 17.08.2021
 * Time: 12:07
 */

namespace DevAV\oauth\services;

use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\Request;
use yii\web\Response;

class OAuthClientService
{
    public function getClientId(Request $request, Response $response): string
    {
        $clientData = $this->getClientCredentials($request, $response);
        $item = $this->getClientByClientId($clientData['client_id']);
        $this->validateRequest($clientData, $item);
        return $clientData['client_id'];
    }

    public function checkRestrictedGrantType(string $clientId, string $grantTypeIdentifier)
    {
        $details = $this->getClientDetails($clientId);

        $grant_types = explode(' ', $details['grant_types']);

        if (!in_array($grantTypeIdentifier, (array)$grant_types)) {
            throw new BadRequestHttpException('The grant type is unauthorized for this client_id');
        }
    }

    private function validateRequest(array $clientData, array $item)
    {
        if (!isset($clientData['client_secret']) || $clientData['client_secret'] == '') {
            if (!$item || !empty($item['client_secret'])) {
                throw new BadRequestHttpException('This client is invalid or must authenticate using a client secret');
            }
        } else {
            if (!$item || $item['client_secret'] != $clientData['client_secret']) {
                throw new BadRequestHttpException('The client credentials are invalid');
            }
        }
    }

    /**
     * @param string $clientId
     * @return array|null
     */
    private function getClientByClientId(string $clientId)
    {
        return $this->getQueryByClientId($clientId)->select(['client_secret'])->one();
    }

    private function getClientDetails(string $clientId): array
    {
        return $this->getQueryByClientId($clientId)->one();
    }

    private function getQueryByClientId(string $clientId): Query
    {
        return (new Query())->from('oauth_clients')->where(['client_id' => $clientId]);
    }

    private function getClientCredentials(Request $request, Response $response): array
    {
        if ($clientId = $request->post('client_id')) {
            return ['client_id' => $clientId, 'client_secret' => $request->post('client_secret', '')];
        }

        throw new BadRequestHttpException('Client credentials were not found in the body');
    }

    public function isPublicClient(string $clientId): bool
    {
        $client = $this->getClientByClientId($clientId);
        if (!$client) {
            return false;
        }
        return empty($client['client_secret']);
    }
}