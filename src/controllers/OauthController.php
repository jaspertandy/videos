<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use yii\web\Response;

/**
 * OAuth controller.
 */
class OauthController extends Controller
{
    /**
     * Action connect.
     *
     * @return Response
     *
     * TODO: rename => login
     * TODO: catch exception
     */
    public function actionConnect(): Response
    {
        $gatewayHandle = Craft::$app->getRequest()->getParam('gateway');
        $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

        Craft::$app->getSession()->set('videos.oauthGateway', $gatewayHandle);
        Craft::$app->getSession()->set('videos.oauthState', $gateway->getOauthProvider()->getState());

        return Craft::$app->getResponse()->redirect($gateway->getOauthAuthorizationUrl());
    }

    /**
     * Action callback.
     *
     * @return Response
     *
     * TODO: catch exception
     */
    public function actionCallback(): Response
    {
        try {
            $gatewayHandle = Craft::$app->getSession()->get('videos.oauthGateway');
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

            $code = Craft::$app->getRequest()->getParam('code');

            $gateway->oauthLogin($code);

            // send notice
            Craft::$app->getSession()->setNotice(Craft::t('videos', 'Connected to {gateway}.', ['gateway' => $gateway->getName()]));
        } catch (Exception $e) {
            Craft::error('Couldn’t connect to video gateway:'."\r\n"
                .'Message: '."\r\n".$e->getMessage()."\r\n"
                .'Trace: '."\r\n".$e->getTraceAsString(), __METHOD__);

            // Failed to get the token credentials or user details.
            Craft::$app->getSession()->setError($e->getMessage());
        }

        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('videos/settings'));
    }

    /**
     * Action disconnect.
     *
     * @return Response
     *
     * TODO: rename => logout
     * TODO: catch exception
     */
    public function actionDisconnect(): Response
    {
        $gatewayHandle = Craft::$app->getRequest()->getParam('gateway');
        $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

        $gateway->oauthLogout();

        // send notice
        Craft::$app->getSession()->setNotice(Craft::t('videos', 'Disconnected.'));

        return $this->redirect(Craft::$app->getRequest()->referrer);
    }
}
