<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use Twig\Markup;

/**
 * Video embed model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoEmbed extends AbstractVideoEmbed
{
    /**
     * @var string the video embed’s url
     *
     * @since 3.0.0
     */
    public string $url;

    /**
     * @var Markup the video embed’s html
     *
     * @since 3.0.0
     */
    public Markup $html;

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public function init(): void
    {
        parent::init();

        $this->loaded = true;
    }
}
