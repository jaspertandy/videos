<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;
use craft\helpers\UrlHelper;
use JsonSerializable;
use ReflectionObject;
use ReflectionProperty;

/**
 * Video author model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoThumbnail extends Model implements JsonSerializable
{
    /**
     * @var null|string the video thumbnail’s smallest source url
     *
     * @since 3.0.0
     */
    public ?string $smallestSourceUrl = null;

    /**
     * @var null|string the video thumbnail’s smallest source url
     *
     * @since 3.0.0
     */
    public ?string $largestSourceUrl = null;

    /**
     * @var Video the video
     */
    private Video $_video;

    /**
     * Set the video.
     *
     * @param Video $video
     * @return void
     *
     * @since 3.0.0
     */
    public function setVideo(Video $video): void
    {
        $this->_video = $video;
        $video->thumbnail = $this;
    }

    /**
     * Returns the URL.
     *
     * @param int $size
     * @return string
     *
     * @since 3.0.0
     */
    public function getUrl(int $size = 300): string
    {
        return UrlHelper::actionUrl('videos/thumbnail/get-size', [
            'gatewayHandle' => $this->_video->gatewayHandle,
            'videoId' => $this->_video->id,
            'size' => $size,
        ]);
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
            'url' => $this->getUrl(),
        ];

        return array_merge($publicProps, $addedProps);
    }
}
