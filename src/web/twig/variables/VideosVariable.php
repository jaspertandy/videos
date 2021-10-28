<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\web\twig\variables;

use Craft;
use dukt\videos\models\AbstractVideo;
use dukt\videos\models\FailedVideo;
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
     * Get a video from its URL.
     *
     * @param string $videoUrl
     * @return AbstractVideo
     *
     * @since 3.0.0
     */
    public function url(string $videoUrl): AbstractVideo
    {
        try {
            return VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);
        } catch (Exception $e) {
            // log exception
            Craft::error($e->getMessage(), __METHOD__);

            return new FailedVideo([
                'url' => $videoUrl,
                'errors' => [
                    $e->getMessage(),
                ],
            ]);
        }
    }
}
