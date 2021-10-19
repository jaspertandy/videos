<?php
/**
 * @link      https://dukt.net/videos/
 * @copyright Copyright (c) 2019, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\Plugin as Videos;
use dukt\videos\Plugin;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Explorer controller
 */
class ExplorerController extends Controller
{
    /**
     * @return Response
     * @throws InvalidConfigException
     */
    public function actionGetGateways(): Response
    {
        $gateways = Videos::$plugin->getGateways()->getGateways();

        $gatewaysArray = [];

        foreach ($gateways as $gateway) {
            $gatewaysArray[] = [
                'name' => $gateway->getName(),
                'handle' => $gateway->getHandle(),
                'sections' => $gateway->getExplorer()
            ];
        }

        return $this->asJson($gatewaysArray);
    }

    /**
     * @return Response
     * @throws GatewayNotFoundException
     * @throws InvalidConfigException
     * @throws \dukt\videos\errors\GatewayMethodNotFoundException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetVideos(): Response
    {
        $this->requireAcceptsJson();

        $rawBody = Craft::$app->getRequest()->getRawBody();
        $payload = Json::decodeIfJson($rawBody);

        $gatewayHandle = strtolower($payload['gateway']);
        $method = $payload['method'];
        $options = $payload['options'] ?? [];

        $gateway = Videos::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);

        if (!$gateway) {
            throw new GatewayNotFoundException('Gateway not found.');
        }

        $videosResponse = $gateway->getVideos($method, $options);


        // Todo: Make this happen in the Video model toArray()

        $videos = array();

        foreach($videosResponse['videos'] as $video) {
            $videos[] = $video->toArray();
        }

        return $this->asJson([
            'videos' => $videos,
            'more' => $videosResponse['pagination']['more'],
            'moreToken' => $videosResponse['pagination']['moreToken']
        ]);
    }

    public function actionGetVideo()
    {
        $this->requireAcceptsJson();

        $rawBody = Craft::$app->getRequest()->getRawBody();
        $payload = Json::decodeIfJson($rawBody);
        $url = $payload['url'];

        $video = Plugin::getInstance()->getVideos()->getVideoByUrl($url);

        if (!$video) {
            return $this->asErrorJson("Video not found.");
        }

        return $this->asJson($video->toArray());
    }

    public function actionGetVideoEmbedHtml(): Response
    {
        $this->requireAcceptsJson();

        $rawBody = Craft::$app->getRequest()->getRawBody();
        $payload = Json::decodeIfJson($rawBody);

        $gatewayHandle = strtolower($payload['gateway']);
        $videoId = $payload['videoId'];

        $video = Videos::$plugin->getVideos()->getVideoById($gatewayHandle, $videoId);

        $html = Craft::$app->getView()->renderTemplate('videos/_elements/embedHtml', [
            'video' => $video
        ]);

        return $this->asJson([
            'html' => $html
        ]);
    }
}
