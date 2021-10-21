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
        $format = $video->getGateway()->getEmbedFormat();

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
     * @param array $options
     * @return Markup
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public static function getEmbedHtml(Video $video, array $options = []): Markup
    {
        $urlOptions = [];
        $attributeOptions = [
            'title' => [
                'value' => 'External video from '.$video->getGateway()->getHandle(),
            ],
            'frameborder' => [
                'value' => 0,
            ],
            'allowfullscreen' => [
                'value' => true,
            ],
            'allowscriptaccess' => [
                'value' => true,
            ],
            'allow' => [
                'value' => 'autoplay; encrypted-media',
            ],
            'disable_size' => [
                'value' => null,
            ],
            'width' => [
                'value' => null,
            ],
            'height' => [
                'value' => null,
            ],
            'iframeClass' => [
                'attr' => 'class',
                'value' => null,
            ],
        ];

        // split url options / attribute options from options
        foreach ($options as $optionName => $optionValue) {
            if (isset($attributeOptions[$optionName]) === true) {
                $attributeOptions[$optionName]['value'] = $optionValue;
            } else {
                $urlOptions[$optionName] = $optionValue;
            }
        }

        // special disable size attribute
        if ($attributeOptions['disable_size']['value'] === true) {
            $attributeOptions['width']['value'] = null;
            $attributeOptions['height']['value'] = null;
        }

        // remove null value attribute options
        $attributeOptions = array_filter($attributeOptions, function ($attributeOption) {
            return $attributeOption['value'] !== null;
        });

        // reformate attribute options
        foreach ($attributeOptions as $key => $attributeOption) {
            unset($attributeOptions[$key]);
            $key = $attributeOption['attr'] ?? $key;
            $attributeOptions[$key] = $attributeOption['value'];
        }

        $embedUrl = self::getEmbedUrl($video, $urlOptions);

        $embedAttributesString = implode(' ', array_map(function ($value, $attr) {
            return sprintf('%s="%s"', $attr, $value);
        }, $attributeOptions, array_keys($attributeOptions)));

        $html = '<iframe src="'.$embedUrl.'"'.$embedAttributesString.'></iframe>';
        $charset = Craft::$app->getView()->getTwig()->getCharset();

        return new Markup($html, $charset);
    }
}
