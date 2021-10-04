<?php
/**
 * @link https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;
use yii\base\Component;

/**
 * Videos service.
 *
 * An instance of the videos service is globally accessible via [[Plugin::videos `Videos::$plugin->getVideos()`]].
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class Videos extends Component
{
    /**
     * Get one video by its ID from a gateway.
     *
     * @param string $videoId
     * @param string $gatewayHandle
     *
     * @return Video
     *
     * @throws InvalidConfigException
     * @throws GatewayNotFoundException
     * @throws VideoNotFoundException
     */
    public function getVideoByIdAndGateway(string $videoId, string $gatewayHandle): Video
    {
        if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
            $video = VideosPlugin::$plugin->getCache()->get($this->_generateVideoCacheKey($videoId, $gatewayHandle));

            if ($video instanceof Video) {
                return $video;
            }
        }

        $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle); // TODO: if gateway is not found, must throw GatewayNotFoundException

        $video = $gateway->getVideoById($videoId); // TODO: if video is not found, must throw VideoNotFoundException

        if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
            VideosPlugin::$plugin->getCache()->set($this->_generateVideoCacheKey($videoId, $gatewayHandle), $video);
        }

        return $video;
    }

    /**
     * Get one video by its URL.
     *
     * @param string $videoUrl
     *
     * @return Video
     *
     * @throws InvalidConfigException
     * @throws VideoNotFoundException
     */
    public function getVideoByUrl(string $videoUrl): Video
    {
        foreach (VideosPlugin::$plugin->getGateways()->getGateways() as $gateway) {
            $videoId = $gateway->extractVideoIdFromUrl($videoUrl);

            if ($videoId !== false) {
                return $this->getVideoByIdAndGateway($videoId, $gateway->getHandle());
            }
        }

        throw new VideoNotFoundException(/* TODO: more precise message */);
    }

    /**
     * Generate cache key for a video.
     *
     * @param string $videoId
     * @param string $gatewayHandle
     *
     * @return string
     */
    private function _generateVideoCacheKey(string $videoId, string $gatewayHandle): string
    {
        return VideosPlugin::CACHE_KEY_PREFIX.'.'.Video::CACHE_KEY_PREFIX.'.'.$gatewayHandle.'.'.md5($videoId);
    }
}
