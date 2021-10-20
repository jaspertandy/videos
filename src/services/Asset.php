<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use yii\base\Component;

/**
 * Asset service.
 *
 * An instance of the videos service is globally accessible via [[Plugin::videos `Videos::$plugin->getVideos()`]].
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class Asset extends Component
{
    /**
     * @var bool Whether the devServer should be used
     *
     * @since 3.0.0
     */
    public string $devServerUrl = 'https://localhost:8090';

    /**
     * @var bool Whether the devServer should be used
     */
    private bool $_devServerUsed = false;

    /**
     * Is dev server used?
     *
     * @return bool
     *
     * @since 3.0.0
     */
    public function isDevServerUsed(): bool
    {
        return $this->_devServerUsed;
    }

    /**
     * Set dev server used?
     *
     * @param bool $devServerUsed
     * @return void
     *
     * @since 3.0.0
     */
    public function setDevServerUsed(bool $devServerUsed): void
    {
        $this->_devServerUsed = $devServerUsed;
    }
}
