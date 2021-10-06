<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Settings model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Settings extends Model
{
    /**
     * @var string the amount of time cache should last
     *
     * @see http://www.php.net/manual/en/dateinterval.construct.php
     *
     * @since 2.0.0
     */
    public $cacheDuration = 'PT15M';

    /**
     * @var bool whether request to APIs should be cached or not
     *
     * @since 2.0.0
     */
    public $enableCache = true;

    /**
     * @var array OAuth provider options
     *
     * @since 2.0.0
     */
    public $oauthProviderOptions = [];

    /**
     * @var int the number of videos per page in the explorer
     *
     * @since 2.0.0
     */
    public $videosPerPage = 30;
}
