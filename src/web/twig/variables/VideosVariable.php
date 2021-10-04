<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\web\twig\variables;

use Craft;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;

class VideosVariable
{
    /**
     * Get embed from a video url.
     *
     * @param string $videoUrl
     * @param array  $embedOptions
     *
     * @return null|string
     */
    public function getEmbed(string $videoUrl, array $embedOptions = []): ?string
    {
        try {
            $video = VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);

            return $video->getEmbed($embedOptions);
        } catch (\Exception $e) {
            Craft::info('Couldn’t get video from its url ('.$videoUrl.'): '.$e->getMessage(), __METHOD__);
        }

        return null;
    }

    /**
     * Get a video from its URL.
     *
     * @param string $videoUrl
     * @param bool   $enableCache
     * @param int    $cacheExpiry
     *
     * @return null|Video
     */
    public function getVideoByUrl(string $videoUrl/*, $enableCache = true, $cacheExpiry = 3600*/): ?Video
    {
        try {
            return VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);
        } catch (\Exception $e) {
            Craft::info('Couldn’t get video from its url ('.$videoUrl.'): '.$e->getMessage(), __METHOD__);
        }

        return null;
    }

    /**
     * Alias for the `getVideoByUrl()` method.
     *
     * @param string $videoUrl
     * @param bool   $enableCache
     * @param int    $cacheExpiry
     *
     * @return null|Video
     */
    public function url($videoUrl/*, $enableCache = true, $cacheExpiry = 3600*/): ?Video
    {
        return $this->getVideoByUrl($videoUrl);
    }
}
