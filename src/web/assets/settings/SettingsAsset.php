<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\web\assets\settings;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Settings asset class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class SettingsAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__.'/dist';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->css = [
            'settings.css',
        ];

        parent::init();
    }
}
