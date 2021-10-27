<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use Craft;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;
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
     * Returns one video by its URL.
     *
     * @param string $videoUrl
     * @return Video
     * @throws InvalidConfigException
     * @throws VideoNotFoundException
     *
     * @since 3.0.0
     */
    public function getVideoByUrl(string $videoUrl): Video
    {
        foreach (VideosPlugin::$plugin->getGateways()->getGateways(true) as $gateway) {
            try {
                return $gateway->getVideoByUrl($videoUrl);
            } catch (VideoNotFoundException $e) {
                continue;
            }
        }

        throw new VideoNotFoundException(Craft::t('videos', 'Video not found for URL {videoUrl}.', ['videoUrl' => $videoUrl]));
    }
}
