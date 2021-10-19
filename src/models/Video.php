<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use Craft;
use DateInterval;
use DateTime;
use dukt\videos\base\Cacheable;
use dukt\videos\base\Gateway;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\helpers\ThumbnailHelper;
use dukt\videos\Plugin as VideosPlugin;
use Twig\Markup;

/**
 * Video model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Video extends AbstractVideo implements Cacheable
{
    /**
     * @var string prefix for cache key
     *
     * @since 3.0.0
     */
    public const CACHE_KEY_PREFIX = 'video';

    /**
     * @var string the video's ID
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public string $id;

    /**
     * @var string the video’s title
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public string $title;

    /**
     * @var string the video’s description
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public string $description;

    /**
     * @var DateInterval duration of the video
     *
     * @since 3.0.0
     */
    public DateInterval $duration;

    /**
     * @var DateTime the date the video was published at
     *
     * @since 3.0.0
     */
    public DateTime $publishedAt;

    /**
     * @var VideoAuthor the video's author
     *
     * @since 3.0.0
     */
    public VideoAuthor $author;

    /**
     * @var null|string the thumbnail’s source URL
     *
     * @since 3.0.0
     */
    public ?string $thumbnailSourceUrl = null;

    /**
     * @var null|VideoSize the video's size
     *
     * @since 3.0.0
     */
    public ?VideoSize $size = null;

    /**
     * @var bool is this video private?
     *
     * @since 2.0.0
     */
    public $private = false;

    /**
     * @var VideoStatistic the video's statistic
     *
     * @since 3.0.0
     */
    public VideoStatistic $statistic;

    /**
     * @var string the gateway’s handle
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public string $gatewayHandle;

    /**
     * @var array the raw response object
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public array $raw;

    /**
     * @var bool the video is loaded if its data is filled
     *
     * @since 3.0.0
     */
    public bool $loaded = true;

    /**
     * @var null|Gateway the gateway (used for non reinit on each call)
     */
    private ?Gateway $_gateway = null;

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

    /**
     * Returns the video’s gateway.
     *
     * @return Gateway
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public function getGateway(): Gateway
    {
        if ($this->_gateway === null) {
            $this->_gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($this->gatewayHandle);
        }

        return $this->_gateway;
    }

    /**
     * Returns the video’s thumbnail.
     *
     * @param int $size
     * @return null|string
     *
     * @since 2.0.0
     */
    public function getThumbnail(int $size = 300): string
    {
        return ThumbnailHelper::getByVideoAndSize($this, $size);
    }

    /**
     * Returns the video’s embed.
     *
     * @param array $options
     * @return Markup
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public function getEmbed(array $options = []): Markup
    {
        $embed = $this->getGateway()->getEmbedHtml($this, $options);
        $charset = Craft::$app->getView()->getTwig()->getCharset();

        return new Markup($embed, $charset);
    }

    /**
     * Returns the video’s embed URL.
     *
     * @param array $options
     * @return string
     * @throws GatewayNotFoundException
     *
     * @since 2.0.0
     */
    public function getEmbedUrl(array $options = []): string
    {
        return $this->getGateway()->getEmbedUrl($this, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    final public static function generateCacheKey(array $identifiers): string
    {
        return VideosPlugin::CACHE_KEY_PREFIX.'.'.self::CACHE_KEY_PREFIX.'.'.$identifiers['gateway_handle'].'.'.md5($identifiers['id']);
    }
}
