<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use dukt\videos\services\Cache;
use dukt\videos\services\Gateways;
use dukt\videos\services\Tokens;
use dukt\videos\services\Videos;
use yii\base\InvalidConfigException;

/**
 * PluginTrait implements the common methods and properties for plugin classes.
 *
 * @author  Dukt <support@dukt.net>
 *
 * @since   2.0
 */
trait PluginTrait
{
    /**
     * Returns the cache service.
     *
     * @return Cache
     *
     * @throws InvalidConfigException
     */
    public function getCache(): Cache
    {
        return $this->get('cache');
    }

    /**
     * Returns the gateways service.
     *
     * @return Gateways
     *
     * @throws InvalidConfigException
     */
    public function getGateways(): Gateways
    {
        return $this->get('gateways');
    }

    /**
     * Returns the tokens service.
     *
     * @return Tokens
     *
     * @throws InvalidConfigException
     */
    public function getTokens(): Tokens
    {
        return $this->get('tokens');
    }

    /**
     * Returns the videos service.
     *
     * @return Videos
     *
     * @throws InvalidConfigException
     */
    public function getVideos(): Videos
    {
        return $this->get('videos');
    }
}
