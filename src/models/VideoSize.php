<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Video size model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoSize extends Model
{
    /**
     * @var int the video size's width
     *
     * @since 3.0.0
     */
    public int $width;

    /**
     * @var int the video size's height
     *
     * @since 3.0.0
     */
    public string $height;
}
