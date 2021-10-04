<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use Craft;
use craft\web\Controller;
use dukt\videos\Plugin as Videos;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * OAuth controller.
 */
class OauthController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Connect.
     *
     * @return Response
     *
     * @throws InvalidConfigException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionConnect(): Response
    {
        $gatewayHandle = Craft::$app->getRequest()->getParam('gateway');

        $gateway = Videos::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

        Craft::$app->getSession()->set('videos.oauthGateway', $gatewayHandle);

        return $gateway->oauthConnect();
    }

    /**
     * Callback.
     *
     * @return Response
     *
     * @throws InvalidConfigException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionCallback(): Response
    {
        $gatewayHandle = Craft::$app->getSession()->get('videos.oauthGateway');

        $gateway = Videos::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

        return $gateway->oauthCallback();
    }

    /**
     * Disconnect.
     *
     * @return Response
     *
     * @throws InvalidConfigException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionDisconnect(): Response
    {
        $gatewayHandle = Craft::$app->getRequest()->getParam('gateway');
        $gateway = Videos::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

        Videos::$plugin->getTokens()->deleteTokenByGatewayHandle($gateway->getHandle());

        // set notice
        Craft::$app->getSession()->setNotice(Craft::t('videos', 'Disconnected.'));

        // redirect

        $redirect = Craft::$app->getRequest()->referrer;

        return $this->redirect($redirect);
    }
}
