<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\web\twig\variables;

use Craft;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;
use Exception;

/**
 * Video variable class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class VideosVariable
{
    /**
     * Get embed from a video url.
     *
     * @param string $videoUrl
     * @param array $options
     * @return null|string
     *
     * @since 2.0.0
     */
    public function getEmbed(string $videoUrl, array $options = []): ?string
    {
        try {
            $video = VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);

            return $video->getEmbed($options);
        } catch (Exception $e) {
            Craft::info('Couldn’t get video from its url ('.$videoUrl.'): '.$e->getMessage(), __METHOD__);
        }

        return null;
    }

    /**
     * Get a video from its URL.
     *
     * @param string $videoUrl
     * @param bool $enableCache @deprecated
     * @param int $cacheExpiry @deprecated
     * @return null|Video
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public function getVideoByUrl(string $videoUrl, $enableCache = true, $cacheExpiry = 3600): ?Video
    {
        try {
            return VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);
        } catch (Exception $e) {
            Craft::info('Couldn’t get video from its url ('.$videoUrl.'): '.$e->getMessage(), __METHOD__);
        }

        return null;
    }

    /**
     * Alias for the `getVideoByUrl()` method.
     *
     * @param string $videoUrl
     * @param bool $enableCache @deprecated
     * @param int $cacheExpiry @deprecated
     * @return null|Video
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public function url($videoUrl, $enableCache = true, $cacheExpiry = 3600): ?Video
    {
        return $this->getVideoByUrl($videoUrl);
    }
}
