<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Video statistic model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoStatistic extends Model
{
    /**
     * @var int the number of times the video has been played
     *
     * @since 3.0.0
     */
    public int $playCount = 0;
}
