<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
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
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class OauthController extends Controller
{
    /**
     * Login to OAuth.
     *
     * @return Response
     *
     * @since 3.0.0
     */
    public function actionLogin(): Response
    {
        try {
            $gatewayHandle = Craft::$app->getRequest()->getParam('gatewayHandle');
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

            Craft::$app->getSession()->set('videos.oauthGateway', $gatewayHandle);
            Craft::$app->getSession()->set('videos.oauthState', $gateway->getOauthProvider()->getState());

            return Craft::$app->getResponse()->redirect($gateway->getOauthAuthorizationUrl());
        } catch (Exception $e) {
            // send flash message
            // TODO: improve message (translation ?)
            Craft::$app->getSession()->setError('An error occured: '.$e->getMessage());
        }

        return $this->redirect(Craft::$app->getRequest()->referrer);
    }

    /**
     * Callback from OAuth provider.
     *
     * @return Response
     *
     * @since 2.0.0
     */
    public function actionCallback(): Response
    {
        $gatewayHandle = Craft::$app->getSession()->get('videos.oauthGateway');

        try {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

            $code = Craft::$app->getRequest()->getParam('code');

            $gateway->oauthLogin($code);

            // send flash message
            // TODO: improve message (translation ?)
            Craft::$app->getSession()->setNotice(Craft::t('videos', 'Connected to {gateway}.', ['gateway' => $gateway->getName()]));
        } catch (Exception $e) {
            // send flash message
            // TODO: improve message (translation ?)
            Craft::$app->getSession()->setError('An error occured: '.$e->getMessage());
        }

        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('videos/settings/'.$gatewayHandle));
    }

    /**
     * Logout of OAuth.
     *
     * @return Response
     *
     * @since 3.0.0
     */
    public function actionLogout(): Response
    {
        try {
            $gatewayHandle = Craft::$app->getRequest()->getParam('gatewayHandle');
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

            $gateway->oauthLogout();

            // send flash message
            // TODO: improve message (translation ?)
            Craft::$app->getSession()->setNotice(Craft::t('videos', 'Disconnected.'));
        } catch (Exception $e) {
            // send flash message
            // TODO: improve message (translation ?)
            Craft::$app->getSession()->setError('An error occured: '.$e->getMessage());
        }

        return $this->redirect(Craft::$app->getRequest()->referrer);
    }
}
