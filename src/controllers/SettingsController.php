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
use dukt\videos\web\assets\videos\VideosAsset;
use Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Settings controller.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class SettingsController extends Controller
{
    /**
     * Settings list of gateways.
     *
     * @return Response
     *
     * @since 2.0.0
     */
    public function actionIndex(): Response
    {
        $gateways = VideosPlugin::$plugin->getGateways()->getGateways();

        Craft::$app->getView()->registerAssetBundle(VideosAsset::class);

        return $this->renderTemplate('videos/settings/_index', [
            'gateways' => $gateways,
        ]);
    }

    /**
     * Setting of a gateway.
     *
     * @param string $gatewayHandle
     * @return Response
     *
     * @since 2.0.0
     */
    public function actionGateway(string $gatewayHandle): Response
    {
        try {
            return $this->renderTemplate('videos/settings/_gateway', [
                'gateway' => VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle),
            ]);
        } catch (Exception $e) {
            // send flash message
            Craft::$app->getSession()->setError($e->getMessage());

            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('videos/settings'));
        }
    }

    /**
     * Settings gateway OAuth.
     *
     * @param string $gatewayHandle
     * @return Response
     *
     * @since 2.0.0
     */
    public function actionGatewayOauth(string $gatewayHandle): Response
    {
        try {
            return $this->renderTemplate('videos/settings/_oauth', [
                'gateway' => VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle),
            ]);
        } catch (Exception $e) {
            // send flash message
            Craft::$app->getSession()->setError($e->getMessage());

            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('videos/settings'));
        }
    }

    /**
     * Save gateway.
     *
     * @return Response
     *
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionSaveGateway(): Response
    {
        try {
            $gatewayHandle = Craft::$app->getRequest()->getParam('gatewayHandle');
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

            $clientId = Craft::$app->getRequest()->getParam('clientId');
            $clientSecret = Craft::$app->getRequest()->getParam('clientSecret');

            $configData = [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ];

            $key = 'plugins.videos.settings.oauthProviderOptions';
            $configPath = $key.'.'.$gateway->getHandle();

            Craft::$app->getProjectConfig()->set($configPath, $configData, Craft::t('videos', 'setting.oauth.configure.save.info', ['gatewayName' => $gateway->getName()]));

            // send flash message
            Craft::$app->getSession()->setNotice(Craft::t('videos', 'setting.oauth.configure.save.success', ['gatewayName' => $gateway->getName()]));
        } catch (Exception $e) {
            // send flash message
            Craft::$app->getSession()->setError($e->getMessage());
        }

        return $this->redirectToPostedUrl();
    }
}
