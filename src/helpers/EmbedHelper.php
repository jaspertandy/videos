<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\helpers;

use Craft;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\models\Video;
use Twig\Markup;

/**
 * Embed helper.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class EmbedHelper
{
    /**
     * Returns the URL of the embed from a video ID.
     *
     * @param Video $video
     * @param array $options
     * @return string
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public static function getEmbedUrl(Video $video, array $options = []): string
    {
        $options = $video->getGateway()->resolveEmbedUrlOptions($options, $video);

        $format = $video->getGateway()->getEmbedUrlFormat();

        $formatParts = parse_url($format);
        $formatPartQueryParams = [];

        if (isset($formatParts['query']) === true) {
            parse_str($formatParts['query'], $formatPartQueryParams);
        }

        $formatParts['query'] = http_build_query(array_merge($formatPartQueryParams, $options));

        return sprintf(UrlHelper::buildUrl($formatParts), $video->id);
    }

    /**
     * Returns the HTML of the embed from a video ID.
     *
     * @param Video $video
     * @param array $htmlOptions
     * @param array $urlOptions
     * @return Markup
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public static function getEmbedHtml(Video $video, array $htmlOptions = [], array $urlOptions = []): Markup
    {
        $htmlOptions = $video->getGateway()->resolveEmbedHtmlOptions($htmlOptions, $video);

        $htmlAttributesString = implode(' ', array_map(function ($value, $attr) {
            return is_bool($value) === true ? sprintf('%s', $attr) : sprintf('%s="%s"', $attr, $value);
        }, $htmlOptions, array_keys($htmlOptions)));

        $embedUrl = self::getEmbedUrl($video, $urlOptions);

        $html = '<iframe src="'.$embedUrl.'"'.$htmlAttributesString.'></iframe>';
        $charset = Craft::$app->getView()->getTwig()->getCharset();

        return new Markup($html, $charset);
    }
}
