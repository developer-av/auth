<?php
namespace DevAV\oauth\controllers;

use DevAV\oauth\services\OAuthService;
use yii\rest\Controller;

/**
 * Created by PhpStorm.
 * User: Developer-AV
 * Date: 16.08.2021
 * Time: 14:28
 */
class RestController extends Controller
{
    /**
     * @var \DevAV\oauth\services\OAuthService
     */
    private $oAuthService;

    public function __construct($id, $module, OAuthService $oAuthService, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->oAuthService = $oAuthService;
    }

    public function actionToken()
    {
        return $this->oAuthService->handleTokenRequest($this->request, $this->response);
    }
}