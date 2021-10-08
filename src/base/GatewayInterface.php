<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use craft\errors\MissingComponentException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Video;
use dukt\videos\models\VideoExplorer;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\AbstractProvider;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * GatewayInterface defines the common interface to be implemented by gateway classes.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
interface GatewayInterface
{
    /**
     * Returns the name of the gateway.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getName(): string;

    /**
     * Returns the icon’s alias.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getIconAlias(): string;

    /**
     * Creates the OAuth provider’s instance.
     *
     * @param array $options
     * @return AbstractProvider
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    public function createOauthProvider(array $options): AbstractProvider;

    /**
     * Returns the OAuth provider’s API console URL.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getOauthProviderApiConsoleUrl(): string;

    /**
     * Extracts the video ID from the video URL.
     *
     * @param string $videoUrl
     * @return string
     * @throws VideoIdExtractException
     *
     * @since 3.0.0
     */
    public function extractVideoIdFromVideoUrl(string $videoUrl): string;

    /**
     * Creates an authenticated guzzle client.
     *
     * @return Client
     * @throws ApiClientCreateException
     *
     * @since 3.0.0
     */
    public function createApiClient(): Client;

    /**
     * Requests the video from the API and then returns it as video object.
     *
     * @param string $videoId
     * @return Video
     * @throws VideoNotFoundException
     *
     * @since 3.0.0
     */
    public function fetchVideoById(string $videoId): Video;

    /**
     * Returns the URL format of the embed.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getEmbedFormat(): string;

    /**
     * Whether the gateway supports search or not.
     *
     * @return bool
     *
     * @since 3.0.0
     */
    public function supportsSearch(): bool;

    /**
     * Returns the videos'explorer.
     *
     * @return VideoExplorer
     * @throws ApiResponseException
     *
     * @since 3.0.0
     */
    public function getExplorer(): VideoExplorer;

    /*
     * Returns the sections for the explorer.
     *
     * @return array
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[GatewayInterface::getExplorer]] instead.
     */
    //public function getExplorerSections(): array;

    /*
     * Extracts the video ID from the video URL.
     *
     * @param string $videoUrl
     * @return bool|string
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    //public function extractVideoIdFromUrl(string $videoUrl);

    /*
     * OAuth Connect.
     *
     * @return Response
     * @throws MissingComponentException
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    //public function oauthConnect(): Response;

    /*
     * OAuth Callback.
     *
     * @return Response
     * @throws MissingComponentException
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    //public function oauthCallback(): Response;

    /*
     * Returns an authenticated Guzzle client.
     *
     * @return Client
     *
     * TODO: throws exception
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    //protected function createClient(): Client;

    /*
     * Performs a GET request on the API.
     *
     * @param $uri
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    //protected function get($uri, array $options = []): array
}
