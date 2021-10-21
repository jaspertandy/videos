<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Video author model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoAuthor extends Model
{
    /**
     * @var string the video author’s name
     *
     * @since 3.0.0
     */
    public string $name;

    /**
     * @var string the video author’s url
     *
     * @since 3.0.0
     */
    public string $url;

    /**
     * @var Video the video
     */
    private Video $_video;

    /**
     * Set the video.
     *
     * @param Video $video
     * @return void
     *
     * @since 3.0.0
     */
    public function setVideo(Video $video): void
    {
        $this->_video = $video;
        $video->author = $this;
    }
}
