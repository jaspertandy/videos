<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use Craft;
use craft\web\Controller;
use dukt\videos\Plugin;
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
     * Returns gateways.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 3.0.0
     */
    public function actionGetGateways(): Response
    {
        $this->requireAcceptsJson();

        try {
            return $this->asJson([
                'gateways' => VideosPlugin::$plugin->getGateways()->getGateways(true),
            ]);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), $e->getCode(), $e);
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
            $gatewayHandle = Craft::$app->getRequest()->getBodyParam('gateway');
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

            $method = Craft::$app->getRequest()->getBodyParam('method');
            $options = Craft::$app->getRequest()->getBodyParam('options', []);

            $videosResponse = $gateway->getVideos($method, $options);

            return $this->asJson([
                'videos' => $videosResponse['videos'],
                'more' => $videosResponse['pagination']['more'],
                'moreToken' => $videosResponse['pagination']['moreToken'],
            ]);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns video.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 3.0.0
     */
    public function actionGetVideo()
    {
        $this->requireAcceptsJson();

        try {
            $videoUrl = Craft::$app->getRequest()->getBodyParam('url');
            $video = Plugin::getInstance()->getVideos()->getVideoByUrl($videoUrl);

            return $this->asJson([
                'video' => $video,
            ]);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
