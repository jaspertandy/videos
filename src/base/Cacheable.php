<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

/**
 * Cacheable defines the common interface to be implemented by cacheables.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
interface Cacheable
{
    /**
     * Generate cache key.
     *
     * @param array $identifiers
     * @return string
     *
     * @since 3.0.0
     */
    public static function generateCacheKey(array $identifiers): string;
}
