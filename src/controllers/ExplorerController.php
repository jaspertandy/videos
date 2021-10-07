<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\FailedVideo;
use dukt\videos\Plugin as VideosPlugin;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use yii\web\Response;

/**
 * Explorer controller.
 */
class ExplorerController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $explorerNav;

    // Public Methods
    // =========================================================================

    /**
     * Get the explorer modal.
     *
     * @return Response
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetModal(): Response
    {
        $this->requireAcceptsJson();

        $namespaceInputId = Craft::$app->getRequest()->getBodyParam('namespaceInputId');

        $gateways = [];
        $gatewaySections = [];
        foreach (VideosPlugin::$plugin->getGateways()->getGateways(true) as $_gateway) {
            try {
                $gatewaySection = $_gateway->getExplorerSections();

                if ($gatewaySection) {
                    $gatewaySections[] = $gatewaySection;

                    $gateway = [
                        'name' => $_gateway->getName(),
                        'handle' => $_gateway->getHandle(),
                        'supportsSearch' => $_gateway->supportsSearch(),
                    ];

                    $gateways[] = $gateway;
                }
            } catch (IdentityProviderException $e) {
                $errorMsg = $e->getMessage();

                $data = $e->getResponseBody();

                if (isset($data['error_description'])) {
                    $errorMsg = $data['error_description'];
                }

                Craft::error('Couldn’t load gateway `'.$_gateway->getHandle().'`: '.$errorMsg, __METHOD__);
            }
        }

        return $this->asJson([
            'success' => true,
            'html' => Craft::$app->getView()->renderTemplate('videos/_elements/explorer', [
                'namespaceInputId' => $namespaceInputId,
                'gateways' => $gateways,
                'gatewaySections' => $gatewaySections,
                'jsonGateways' => Json::encode($gateways),
            ]),
        ]);
    }

    /**
     * Get videos.
     *
     * @return Response
     *
     * @throws GatewayNotFoundException
     * @throws \Twig_Error_Loader
     * @throws \dukt\videos\errors\GatewayMethodNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetVideos(): Response
    {
        $this->requireAcceptsJson();

        $gatewayHandle = strtolower(Craft::$app->getRequest()->getParam('gateway'));

        $method = Craft::$app->getRequest()->getParam('method');
        $options = Craft::$app->getRequest()->getParam('options', []);

        $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

        $videosResponse = $gateway->getVideos($method, $options);

        $html = Craft::$app->getView()->renderTemplate('videos/_elements/videos', [
            'videos' => $videosResponse['videos'],
        ]);

        return $this->asJson([
            'html' => $html,
            'more' => $videosResponse['more'],
            'moreToken' => $videosResponse['moreToken'],
        ]);
    }

    /**
     * Field preview.
     *
     * @return Response
     *
     * @throws VideoNotFoundException
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionFieldPreview(): Response
    {
        $this->requireAcceptsJson();

        $url = Craft::$app->getRequest()->getParam('url');

        $video = null;

        try {
            $video = VideosPlugin::$plugin->getVideos()->getVideoByUrl($url);
        } catch (\Exception $e) {
            $video = new FailedVideo([
                'url' => $url,
                'errors' => [
                    $e->getMessage(),
                ],
            ]);
        }

        return $this->asJson(
            [
                'video' => $video,
                'preview' => Craft::$app->getView()->renderTemplate('videos/_elements/fieldPreview', ['video' => $video]),
            ]
        );
    }

    /**
     * Player.
     *
     * @return Response
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionPlayer(): Response
    {
        $this->requireAcceptsJson();

        $gatewayHandle = strtolower(Craft::$app->getRequest()->getParam('gateway'));
        $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

        $videoId = Craft::$app->getRequest()->getParam('videoId');

        $video = $gateway->getVideoById($videoId);

        $html = Craft::$app->getView()->renderTemplate('videos/_elements/player', [
            'video' => $video,
        ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }
}
