<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use Craft;
use dukt\videos\Plugin as VideosPlugin;
use yii\base\Component;

/**
 * Cache service.
 *
 * An instance of the Cache service is globally accessible via [[Plugin::cache `VideosPlugin::$plugin->getCache()`]].
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class Cache extends Component
{
    /**
     * Is cache enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return VideosPlugin::$plugin->getSettings()->enableCache;
    }

    /**
     * Get cache data by cache key.
     *
     * @param string $cacheKey
     *
     * @return mixed the value stored in cache, false if the value is not in the cache, expired, or the dependency associated with the cached data has changed
     */
    public function get(string $cacheKey)
    {
        return Craft::$app->getCache()->get($cacheKey);
    }

    /**
     * Set data to the cache.
     *
     * @param string $cacheKey
     * @param mixed  $value
     *
     * @return bool whether the value is successfully stored into cache
     */
    public function set(string $cacheKey, $value): bool
    {
        return Craft::$app->cache->set($cacheKey, $value, VideosPlugin::$plugin->getSettings()->cacheDuration);
    }
}
