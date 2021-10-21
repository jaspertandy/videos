<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\helpers;

use Craft;
use craft\helpers\FileHelper;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\models\Video;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use SplFileInfo;

/**
 * Thumbnail helper.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class ThumbnailHelper
{
    /**
     * Returns a video thumbnail’s published URL for a given size.
     *
     * @param Video $video
     * @param int $size
     * @return null|SplFileInfo
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public static function getByVideoAndSize(Video $video, int $size = 300): ?SplFileInfo
    {
        if ($video->thumbnail->largestSourceUrl === null) {
            return null;
        }

        $directory = Craft::$app->getPath()->getRuntimePath().DIRECTORY_SEPARATOR.'videos'.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.$video->getGateway()->getHandle().DIRECTORY_SEPARATOR.$video->id;

        $thumbnailSourceFile = new SplFileInfo($video->thumbnail->largestSourceUrl);
        $thumbnailName = $size.'.'.$thumbnailSourceFile->getExtension();
        $thumbnailPath = $directory.DIRECTORY_SEPARATOR.$thumbnailName;
        $originalName = 'original.'.$thumbnailSourceFile->getExtension();
        $originalPath = $directory.DIRECTORY_SEPARATOR.$originalName;
        $originalFile = null;

        if (is_dir($directory) === false) {
            FileHelper::createDirectory($directory);
        }

        $filePaths = FileHelper::findFiles($directory);

        foreach ($filePaths as $filePath) {
            $file = new SplFileInfo($filePath);

            if ($file->getFilename() === $thumbnailName) {
                return $file;
            }

            if ($file->getFilename() === $originalName) {
                $originalFile = $file;
            }
        }

        if ($originalFile === null) {
            try {
                (new Client())->request('GET', $video->thumbnail->largestSourceUrl, [
                    'sink' => $originalPath,
                ]);

                $originalFile = new SplFileInfo($originalPath);
            } catch (ClientException $e) {
                return null;
            }
        }

        // generate thumbnail
        Craft::$app->getImages()->loadImage($originalFile->getRealPath())
            ->scaleToFit($size, $size)
            ->saveAs($thumbnailPath)
        ;

        return new SplFileInfo($thumbnailPath);
    }
}
