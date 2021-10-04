<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/master/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;
use dukt\videos\base\Cachable;
use dukt\videos\Plugin as VideosPlugin;

/**
 * Oauth account class.
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  3.0
 */
class OauthAccount extends Model implements Cachable
{
    /**
     * @var string prefix for cache key
     */
    public const CACHE_KEY_PREFIX = 'oauth_account';

    /**
     * @var null|int ID
     */
    public ?string $id;

    /**
     * @var null|string name
     */
    public ?string $name;

    /**
     * {@inheritdoc}
     */
    public static function generateCacheKey(array $identifiers): string
    {
        return VideosPlugin::CACHE_KEY_PREFIX.'.'.self::CACHE_KEY_PREFIX.'.'.$identifiers['gateway_handle'];
    }
}
