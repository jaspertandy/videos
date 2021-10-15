<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Videos service.
 *
 * An instance of the videos service is globally accessible via [[Plugin::videos `Videos::$plugin->getVideos()`]].
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Videos extends Component
{
    /**
     * @var bool Whether the devServer should be used
     */
    public bool $useDevServer = false;

    /**
     * Returns one video by its URL.
     *
     * @param string $videoUrl
     * @param bool $enableCache @deprecated
     * @param int $cacheExpiry @deprecated
     * @return Video
     * @throws InvalidConfigException
     * @throws VideoNotFoundException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public function getVideoByUrl(string $videoUrl, $enableCache = true, $cacheExpiry = 3600): Video
    {
        foreach (VideosPlugin::$plugin->getGateways()->getGateways(true) as $gateway) {
            try {
                return $gateway->getVideoByUrl($videoUrl);
            } catch (VideoNotFoundException $e) {
                continue;
            }
        }

        throw new VideoNotFoundException(/* TODO: more precise message */);
    }

    /**
     * Returns the HTML of the embed from a video URL.
     *
     * @param string $videoUrl
     * @param array $options
     * @return null|string
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Video::getEmbed]] instead.
     */
    public function getEmbed($videoUrl, array $options = [])
    {
        try {
            $video = VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);

            return $video->getEmbed($options);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Returns video by ID.
     *
     * @param string $gatewayHandle
     * @param string $videoId
     * @return null|Video
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Gateway::getVideoById]] instead.
     */
    public function getVideoById($gatewayHandle, $videoId)
    {
        try {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            return $gateway->getVideoById($videoId);
        } catch (Exception $e) {
        }

        return null;
    }
}
