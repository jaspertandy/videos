<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\web\assets\videos;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;
use dukt\videos\Plugin;

/**
 * Videos asset class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class VideosAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function init()
    {
        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        if (Plugin::getInstance()->getAsset()->isDevServerUsed() === false) {
            $this->sourcePath = __DIR__.'/dist';
            $this->js[] = 'js/chunk-vendors.js';
            $this->js[] = 'js/app.js';
            $this->css[] = 'css/app.css';
        } else {
            $this->js[] = Plugin::getInstance()->getAsset()->devServerUrl.'/js/chunk-vendors.js';
            $this->js[] = Plugin::getInstance()->getAsset()->devServerUrl.'/js/app.js';
        }

        parent::init();
    }
}
