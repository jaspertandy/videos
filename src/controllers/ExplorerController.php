<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use dukt\videos\models\FailedVideo;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Explorer controller.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class ExplorerController extends Controller
{
    /**
     * Returns the explorer modal.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 2.0.0
     */
    public function actionGetModal(): Response
    {
        $this->requireAcceptsJson();

        try {
            $namespaceInputId = Craft::$app->getRequest()->getBodyParam('namespaceInputId');

            return $this->asJson([
                'success' => true,
                'html' => Craft::$app->getView()->renderTemplate('videos/_elements/explorer', [
                    'namespaceInputId' => $namespaceInputId,
                    'gateways' => VideosPlugin::$plugin->getGateways()->getGateways(true),
                    'jsonGateways' => Json::encode(VideosPlugin::$plugin->getGateways()->getGateways(true)),
                ]),
            ]);
        } catch (Exception $e) {
            // TODO: exception message

            return $this->asJson(['success' => false]);
        }
    }

    /**
     * Returns videos.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 2.0.0
     */
    public function actionGetVideos(): Response
    {
        $this->requireAcceptsJson();

        try {
            $gatewayHandle = strtolower(Craft::$app->getRequest()->getParam('gateway'));
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            $method = Craft::$app->getRequest()->getParam('method');
            $options = Craft::$app->getRequest()->getParam('options', []);

            $videosResponse = $gateway->getVideos($method, $options);

            return $this->asJson([
                'success' => true,
                'html' => Craft::$app->getView()->renderTemplate('videos/_elements/videos', [
                    'videos' => $videosResponse['videos'],
                ]),
                'more' => $videosResponse['pagination']['more'],
                'moreToken' => $videosResponse['pagination']['moreToken'],
            ]);
        } catch (Exception $e) {
            // TODO: exception message

            return $this->asJson(['success' => false]);
        }
    }

    /**
     * Returns the field preview.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 2.0.0
     */
    public function actionFieldPreview(): Response
    {
        $this->requireAcceptsJson();

        $video = null;

        try {
            $url = Craft::$app->getRequest()->getParam('url');

            $video = VideosPlugin::$plugin->getVideos()->getVideoByUrl($url);
        } catch (Exception $e) {
            // TODO: exception message

            $video = new FailedVideo([
                'url' => $url,
                'errors' => [
                    $e->getMessage(),
                ],
            ]);
        }

        return $this->asJson([
            'video' => $video,
            'preview' => Craft::$app->getView()->renderTemplate('videos/_elements/fieldPreview', ['video' => $video]),
        ]);
    }

    /**
     * Plays the video.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 2.0.0
     */
    public function actionPlayer(): Response
    {
        $this->requireAcceptsJson();

        try {
            $gatewayHandle = strtolower(Craft::$app->getRequest()->getParam('gateway'));
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            $videoId = Craft::$app->getRequest()->getParam('videoId');

            $video = $gateway->getVideoById($videoId);

            return $this->asJson([
                'success' => true,
                'html' => Craft::$app->getView()->renderTemplate('videos/_elements/player', ['video' => $video]),
            ]);
        } catch (Exception $e) {
            // TODO: exception message

            return $this->asJson(['success' => false]);
        }
    }
}
