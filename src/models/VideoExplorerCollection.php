<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Video explorer collection model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoExplorerCollection extends Model
{
    /**
     * @var string the collection's name
     *
     * @since 3.0.0
     */
    public string $name;

    /**
     * @var string the collection's method
     *
     * @since 3.0.0
     */
    public string $method;

    /**
     * @var array the collection's options
     *
     * @since 3.0.0
     */
    public array $options = [];
}
