<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Video;
use League\OAuth2\Client\Provider\AbstractProvider;

/**
 * GatewayInterface defines the common interface to be implemented by gateway classes.
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
interface GatewayInterface
{
    /**
     * Returns the name of the gateway.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the icon’s alias.
     *
     * @return string
     */
    public function getIconAlias(): string;

    /**
     * Returns the OAuth provider’s instance.
     *
     * @param array $options
     */
    public function createOauthProvider(array $options): AbstractProvider;

    /**
     * Returns the OAuth provider’s API console URL.
     *
     * @return string
     */
    public function getOauthProviderApiConsoleUrl(): string;

    /**
     * Extracts the video ID from the video URL.
     *
     * @param string $videoUrl
     *
     * @return string
     *
     * @throws VideoIdExtractException
     */
    public function extractVideoIdFromVideoUrl(string $videoUrl): string;

    /**
     * Requests the video from the API and then returns it as video object.
     *
     * @param string $videoId
     *
     * @return Video
     *
     * @throws VideoNotFoundException
     */
    public function fetchVideoById(string $videoId): Video;

    /**
     * Returns the URL format of the embed.
     *
     * @return string
     */
    public function getEmbedFormat(): string;

    /**
     * Whether the gateway supports search or not.
     *
     * @return bool
     */
    public function supportsSearch(): bool;

    /**
     * Returns the sections for the explorer.
     *
     * @return array
     */
    public function getExplorerSections(): array;
}
