<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\helpers;

use Craft;
use craft\helpers\FileHelper;
use dukt\videos\Plugin as VideosPlugin;

/**
 * Videos helper.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 * @deprecated in 3.0.0, will be removed in 3.1.0, use [[DatimeHelper|ThumbnailHelper]] instead.
 */
class VideosHelper
{
    /**
     * Formats seconds to hh:mm:ss.
     *
     * @param int $seconds
     * @return string
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[DatimeHelper::formatDateIntervalToReadable]] instead.
     */
    public static function getDuration(int $seconds): string
    {
        $hours = (int)((int)$seconds / 3600);
        $minutes = (($seconds / 60) % 60);
        $seconds %= 60;

        $hms = '';

        if ($hours > 0) {
            $hms .= str_pad($hours, 2, '0', STR_PAD_LEFT).':';
        }

        $hms .= str_pad($minutes, 2, '0', STR_PAD_LEFT).':';

        $hms .= str_pad($seconds, 2, '0', STR_PAD_LEFT);

        return $hms;
    }

    /**
     * Formats seconds to ISO 8601 duration.
     *
     * @param int $seconds
     * @return string
     *
     * @since 2.0.11
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[DatimeHelper::formatDateIntervalToISO8601]] instead.
     */
    public static function getDuration8601(int $seconds): string
    {
        $hours = (int)((int)$seconds / 3600);
        $minutes = (($seconds / 60) % 60);
        $seconds %= 60;

        $iso8601 = 'PT';

        if ($hours > 0) {
            $iso8601 .= "{$hours}H";
        }

        if ($minutes > 0) {
            $iso8601 .= "{$minutes}M";
        }

        $iso8601 .= "{$seconds}S";

        return $iso8601;
    }

    /**
     * Returns a video thumbnail’s published URL.
     *
     * @param $gatewayHandle
     * @param $videoId
     * @param $size
     * @return null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\ImageException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[ThumbnailHelper::getBySize]] instead.
     */
    public static function getVideoThumbnail($gatewayHandle, $videoId, $size)
    {
        $baseDir = Craft::$app->getPath()->getRuntimePath().DIRECTORY_SEPARATOR.'videos'.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.$gatewayHandle.DIRECTORY_SEPARATOR.$videoId;
        $originalDir = $baseDir.DIRECTORY_SEPARATOR.'original';
        $dir = $baseDir.DIRECTORY_SEPARATOR.$size;

        $file = self::getThumbnailFile($dir);

        if (!$file) {
            // Retrieve original image
            $originalPath = null;

            if (is_dir($originalDir)) {
                $originalFiles = FileHelper::findFiles($originalDir);

                if (\count($originalFiles) > 0) {
                    $originalPath = $originalFiles[0];
                }
            }

            if (!$originalPath) {
                // Copy the original thumbnail
                $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);
                $video = $gateway->getVideoById($videoId);
                $url = $video->thumbnailSource;

                $name = pathinfo($url, PATHINFO_BASENAME);
                $originalPath = $originalDir.DIRECTORY_SEPARATOR.$name;

                FileHelper::createDirectory($originalDir);
                $client = new \GuzzleHttp\Client();
                $response = $client->request('GET', $url, [
                    'sink' => $originalPath,
                ]);

                // Make sure the original file has an extension
                $mimeByExt = FileHelper::getMimeTypeByExtension($originalPath);

                if (!$mimeByExt) {
                    // Add the extension to the filename if it doesn’t have one
                    $mime = FileHelper::getMimeType($originalPath);
                    $ext = FileHelper::getExtensionByMimeType($mime);

                    if ($ext) {
                        $name .= '.'.$ext;
                        $targetPath = $originalDir.DIRECTORY_SEPARATOR.$name;

                        rename($originalPath, $targetPath);

                        $originalPath = $targetPath;
                    }
                }

                if ($response->getStatusCode() !== 200) {
                    return null;
                }
            } else {
                $name = pathinfo($originalPath, PATHINFO_BASENAME);
            }

            // Generate the thumb
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            FileHelper::createDirectory($dir);
            Craft::$app->getImages()->loadImage($originalPath, false, $size)
                ->scaleToFit($size, $size)
                ->saveAs(parse_url($path, PHP_URL_PATH))
            ;
        } else {
            $name = pathinfo($file, PATHINFO_BASENAME);
        }

        return Craft::$app->getAssetManager()->getPublishedUrl($dir, true)."/{$name}";
    }

    /**
     * Get thumbnail file.
     *
     * @param $dir
     * @return null|string
     *
     * @since 2.0.0
     */
    private static function getThumbnailFile($dir)
    {
        if (!is_dir($dir)) {
            return null;
        }

        $files = FileHelper::findFiles($dir);

        if (\count($files) === 0) {
            return null;
        }

        return $files[0];
    }
}
