<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Parent video embed model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
abstract class AbstractVideoEmbed extends Model
{
    /**
     * @var bool the video embed is loaded if its data is filled
     *
     * @since 3.0.0
     */
    public bool $loaded = false;
}
