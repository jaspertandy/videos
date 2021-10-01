<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Collection model class.
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class Collection extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var null|string Name
     */
    public $name;

    /**
     * @var null|string Method
     */
    public $method;

    /**
     * @var null|mixed Options
     */
    public $options;
}
