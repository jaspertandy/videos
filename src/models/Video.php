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
use dukt\videos\helpers\DateTimeHelper;
use dukt\videos\helpers\EmbedHelper;
use dukt\videos\Plugin as VideosPlugin;
use JsonSerializable;
use ReflectionObject;
use ReflectionProperty;
use Twig\Markup;

/**
 * Video model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Video extends AbstractVideo implements Cacheable, JsonSerializable
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
     * @var VideoThumbnail the video's thumbnail
     *
     * @since 3.0.0
     */
    public VideoThumbnail $thumbnail;

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
     * Returns the video’s embed html.
     *
     * @param array $options
     * @return Markup
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public function getEmbedHtml(array $options = []): Markup
    {
        return EmbedHelper::getEmbedHtml($this, $options);
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
        return EmbedHelper::getEmbedUrl($this, $options);
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

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    final public function jsonSerialize()
    {
        $publicReflectionProperties = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);

        $publicProps = [];
        foreach ($publicReflectionProperties as $publicReflectionProperty) {
            $publicProps[$publicReflectionProperty->getName()] = $publicReflectionProperty->getValue($this);
        }

        $addedProps = [
            'embedUrl' => $this->getEmbedUrl(),
        ];

        if (Craft::$app->request->getIsCpRequest() === true) {
            $addedProps['durationNumeric'] = DateTimeHelper::formatDateIntervalToReadable($this->duration);
            $addedProps['embedHtml'] = (string)$this->getEmbedHtml(['autoplay' => 1]);
        }

        return array_merge($publicProps, $addedProps);
    }
}
