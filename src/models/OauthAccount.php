<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;
use dukt\videos\base\Cacheable;
use dukt\videos\Plugin as VideosPlugin;

/**
 * Oauth account model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class OauthAccount extends Model implements Cacheable
{
    /**
     * @var string prefix for cache key
     *
     * @since 3.0.0
     */
    public const CACHE_KEY_PREFIX = 'oauth_account';

    /**
     * @var int the account's ID
     *
     * @since 3.0.0
     */
    public string $id;

    /**
     * @var string the account's name
     *
     * @since 3.0.0
     */
    public string $name;

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    final public static function generateCacheKey(array $identifiers): string
    {
        return VideosPlugin::CACHE_KEY_PREFIX.'.'.self::CACHE_KEY_PREFIX.'.'.$identifiers['gateway_handle'];
    }
}
