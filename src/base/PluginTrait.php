<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use dukt\videos\services\Asset;
use dukt\videos\services\Cache;
use dukt\videos\services\Gateways;
use dukt\videos\services\Oauth;
use dukt\videos\services\Videos;
use yii\base\InvalidConfigException;

/**
 * PluginTrait implements the common methods and properties for plugin classes.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
trait PluginTrait
{
    /**
     * Returns the asset service.
     *
     * @return Cache
     * @throws InvalidConfigException
     *
     * @since 3.0.0
     */
    public function getAsset(): Asset
    {
        return $this->get('asset');
    }

    /**
     * Returns the cache service.
     *
     * @return Cache
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getCache(): Cache
    {
        return $this->get('cache');
    }

    /**
     * Returns the gateways service.
     *
     * @return Gateways
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getGateways(): Gateways
    {
        return $this->get('gateways');
    }

    /**
     * Returns the oauth service.
     *
     * @return Oauth
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getOauth(): Oauth
    {
        return $this->get('oauth');
    }

    /**
     * Returns the videos service.
     *
     * @return Videos
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getVideos(): Videos
    {
        return $this->get('videos');
    }
}
