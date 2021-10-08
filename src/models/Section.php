<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Section model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 * @deprecated in 3.0.0, will be removed in 3.1.0, use [[VideoExplorerSection]] instead.
 */
class Section extends Model
{
    /**
     * @var null|string the section's name
     *
     * @since 2.0.0
     */
    public $name;

    /**
     * @var null|mixed the section's collections
     *
     * @since 2.0.0
     */
    public $collections;
}
