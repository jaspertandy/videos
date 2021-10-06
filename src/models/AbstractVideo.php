<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Parent video model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
abstract class AbstractVideo extends Model
{
    /**
     * @var null|string the URL of the video
     *
     * @since 3.0.0
     */
    public ?string $url;

    /**
     * @var bool the video is loaded if its data is filled
     *
     * @since 3.0.0
     */
    public bool $loaded = false;
}
