<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use Craft;
use dukt\videos\base\Cachable;
use dukt\videos\base\Gateway;
use dukt\videos\helpers\VideosHelper;
use dukt\videos\Plugin as VideosPlugin;
use Twig_Markup;
use yii\base\InvalidConfigException;

/**
 * Video model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Video extends AbstractVideo implements Cachable
{
    /**
     * @var string prefix for cache key
     *
     * @since 3.0.0
     */
    public const CACHE_KEY_PREFIX = 'video';

    /**
     * @var null|int the video's ID
     *
     * @since 2.0.0
     */
    public $id;

    /**
     * @var null|mixed the raw response object
     *
     * @since 2.0.0
     */
    public $raw;

    /**
     * @var null|string the gateway’s handle
     *
     * @since 2.0.0
     */
    public $gatewayHandle;

    /**
     * @var null|string the gateway’s name
     *
     * @since 2.0.0
     */
    public $gatewayName;

    /**
     * @var null|\DateTime the date the video was uploaded
     *
     * @since 2.0.0
     */
    public $date;

    /**
     * @var null|int the number of times the video has been played
     *
     * @since 2.0.0
     */
    public $plays;

    /**
     * @var null|int duration of the video in seconds
     *
     * @since 2.0.0
     */
    public $durationSeconds;

    /**
     * @var null|int duration of the video in ISO 8601 format
     *
     * @since 2.0.11
     */
    public $duration8601;

    /**
     * @var null|string the author’s name
     *
     * @since 2.0.0
     */
    public $authorName;

    /**
     * @var null|string the author’s URL
     *
     * @since 2.0.0
     */
    public $authorUrl;

    /**
     * @var null|string the author’s username
     *
     * @since 2.0.0
     */
    public $authorUsername;

    /**
     * @var null|string the thumbnail’s source
     *
     * @since 2.0.0
     */
    public $thumbnailSource;

    /**
     * @var null|string the thumbnail’s large source
     *
     * @since 2.0.0
     * @deprecated in 2.1. Use [[\dukt\videos\models\Video::$thumbnailSource]] instead.
     */
    public $thumbnailLargeSource;

    /**
     * @var null|string the video’s title
     *
     * @since 2.0.0
     */
    public $title;

    /**
     * @var null|string the video’s description
     *
     * @since 2.0.0
     */
    public $description;

    /**
     * @var bool is this video private?
     *
     * @since 2.0.0
     */
    public $private = false;

    /**
     * @var null|int the video’s width
     *
     * @since 2.0.0
     */
    public $width;

    /**
     * @var null|int the video’s height
     *
     * @since 2.0.0
     */
    public $height;

    /**
     * @var bool the video is loaded if its data is filled
     *
     * @since 3.0.0
     */
    public bool $loaded = true;

    /**
     * @var null|Gateway the gateway
     *
     * @since 2.0.0
     */
    private $_gateway;

    /**
     * Returns the video’s duration.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getDuration(): string
    {
        return VideosHelper::getDuration($this->durationSeconds);
    }

    /**
     * Returns the video’s embed.
     *
     * @param array $options
     * @return Twig_Markup
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getEmbed(array $options = []): Twig_Markup
    {
        $embed = $this->getGateway()->getEmbedHtml($this->id, $options);
        $charset = Craft::$app->getView()->getTwig()->getCharset();

        return new Twig_Markup($embed, $charset);
    }

    /**
     * Returns the video’s embed URL.
     *
     * @param array $options
     * @return string
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getEmbedUrl(array $options = []): string
    {
        return $this->getGateway()->getEmbedUrl($this->id, $options);
    }

    /**
     * Returns the video’s gateway.
     *
     * @return null|Gateway
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getGateway()
    {
        if (!$this->_gateway) {
            $this->_gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($this->gatewayHandle, true);
        }

        return $this->_gateway;
    }

    /**
     * Returns the video’s thumbnail.
     *
     * @param int $size
     * @return null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\ImageException
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getThumbnail($size = 300)
    {
        return VideosHelper::getVideoThumbnail($this->gatewayHandle, $this->id, $size);
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public static function generateCacheKey(array $identifiers): string
    {
        return VideosPlugin::CACHE_KEY_PREFIX.'.'.self::CACHE_KEY_PREFIX.'.'.$identifiers['gateway_handle'].'.'.md5($identifiers['id']);
    }
}
