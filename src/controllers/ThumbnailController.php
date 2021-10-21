<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\controllers;

use craft\web\Controller;
use dukt\videos\helpers\ThumbnailHelper;
use dukt\videos\Plugin as VideosPlugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Thumbnail controller.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class ThumbnailController extends Controller
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    /**
     * Returns gateways.
     *
     * @return Response
     * @throws BadRequestHttpException
     *
     * @since 3.0.0
     */
    public function actionGetSize(string $gatewayHandle, string $videoId, int $size = 300): Response
    {
        $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);
        $video = $gateway->getVideoById($videoId);

        $thumbnail = ThumbnailHelper::getByVideoAndSize($video, $size);

        return $this->response
            ->setCacheHeaders()
            ->sendFile($thumbnail->getRealPath(), $thumbnail->getFilename(), [
                'inline' => true,
            ])
        ;
    }
}
