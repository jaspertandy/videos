<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use dukt\videos\errors\ApiClientCreateException;
use dukt\videos\errors\ApiResponseException;
use dukt\videos\errors\GatewayMethodNotFoundException;
use dukt\videos\errors\OauthAccessTokenNotFoundException;
use dukt\videos\errors\OauthAccountNotFoundException;
use dukt\videos\errors\OauthLoginException;
use dukt\videos\errors\OauthLogoutException;
use dukt\videos\errors\VideoIdExtractException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\OauthAccount;
use dukt\videos\models\Video;
use dukt\videos\models\VideoExplorer;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use GuzzleHttp\Client;
use JsonSerializable;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use ReflectionClass;
use yii\base\InvalidConfigException;

/**
 * Gateway is the base class for classes representing video gateways.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
abstract class Gateway implements JsonSerializable
{
    /**
     * @var null|AbstractProvider the oauth provider (used for non reinit on each call)
     */
    private ?AbstractProvider $_oauthProvider = null;

    /**
     * @var null|Client the api client (used for non reinit on each call)
     */
    private ?Client $_apiClient = null;

    /**
     * Returns the name of the gateway.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getName(): string
    {
        $reflection = new ReflectionClass($this);

        return $reflection->getShortName();
    }

    /**
     * Returns the handle of the gateway based on its class name.
     *
     * @return string
     * @throws ReflectionException
     *
     * @since 3.0.0
     */
    final public function getHandle(): string
    {
        $reflection = new ReflectionClass($this);

        return strtolower($reflection->getShortName());
    }

    /**
     * Returns the icon’s alias.
     *
     * @return string
     *
     * @since 2.0.0
     */
    abstract public function getIconAlias(): string;

    /**
     * Returns the icon URL.
     *
     * @return null|string
     * @throws InvalidArgumentException
     *
     * @since 3.0.0
     */
    final public function getIconUrl(): ?string
    {
        $iconAlias = $this->getIconAlias();
        $iconUrl = null;

        if (file_exists(Craft::getAlias($iconAlias)) === true) {
            $iconUrl = Craft::$app->assetManager->getPublishedUrl($iconAlias, true);

            if ($iconUrl === false) {
                return null;
            }
        }

        return $iconUrl;
    }

    /**
     * Returns the OAuth provider’s name.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getOauthProviderName(): string
    {
        return $this->getName();
    }

    /**
     * Returns the OAuth provider options.
     *
     * @param bool $parseEnv
     * @return array
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     */
    public function getOauthProviderOptions(bool $parseEnv = true): array
    {
        return VideosPlugin::$plugin->getOauth()->getOauthProviderOptions($this, $parseEnv);
    }

    /**
     * Creates the OAuth provider’s instance.
     *
     * @param array $options
     * @return AbstractProvider
     *
     * @since 2.0.0
     */
    abstract public function createOauthProvider(array $options): AbstractProvider;

    /**
     * Returns the OAuth provider.
     *
     * @return AbstractProvider
     * @throws InvalidConfigException
     *
     * @since 3.0.0
     */
    final public function getOauthProvider(): AbstractProvider
    {
        if ($this->_oauthProvider === null) {
            $this->_oauthProvider = $this->createOauthProvider($this->getOauthProviderOptions());
        }

        return $this->_oauthProvider;
    }

    /**
     * Returns the OAuth login URL.
     *
     * @return string
     *
     * @since 3.0.0
     */
    public function getOauthLoginUrl(): string
    {
        return UrlHelper::actionUrl('videos/oauth/login', ['gatewayHandle' => $this->getHandle()]);
    }

    /**
     * Returns the OAuth redirect URL.
     *
     * @return string
     *
     * @since 3.0.0
     */
    public function getOauthRedirectUrl(): string
    {
        return UrlHelper::actionUrl('videos/oauth/callback');
    }

    /**
     * Returns the OAuth logout URL.
     *
     * @return string
     *
     * @since 3.0.0
     */
    public function getOauthLogoutUrl(): string
    {
        return UrlHelper::actionUrl('videos/oauth/logout', ['gatewayHandle' => $this->getHandle()]);
    }

    /**
     * Returns the OAuth javascript origin URL.
     *
     * @return string
     * @throws SiteNotFoundException
     *
     * @since 3.0.0
     */
    public function getOauthJavascriptOrigin(): string
    {
        return UrlHelper::baseUrl();
    }

    /**
     * Returns the OAuth scope.
     *
     * @return array
     *
     * @since 3.0.0
     */
    public function getOauthScope(): array
    {
        return [];
    }

    /**
     * Returns the OAuth authorization options.
     *
     * @return array
     *
     * @since 3.0.0
     */
    public function getOauthAuthorizationOptions(): array
    {
        return [];
    }

    /**
     * Returns the OAuth authorization URL.
     *
     * @return string
     * @throws InvalidConfigException
     *
     * @since 3.0.0
     */
    final public function getOauthAuthorizationUrl(): string
    {
        $options = $this->getOauthAuthorizationOptions();
        $options['scope'] = $this->getOauthScope();

        return $this->getOauthProvider()->getAuthorizationUrl($options);
    }

    /**
     * Returns the OAuth provider’s API console URL.
     *
     * @return string
     *
     * @since 2.0.0
     */
    abstract public function getOauthProviderApiConsoleUrl(): string;

    /**
     * Returns the OAuth access token.
     *
     * @return AccessToken
     * @throws OauthAccessTokenNotFoundException
     *
     * @since 3.0.0
     */
    final public function getOauthAccessToken(): AccessToken
    {
        return VideosPlugin::$plugin->getOauth()->getOauthAccessTokenByGateway($this);
    }

    /**
     * Oauth login.
     *
     * @param string $code
     * @return void
     * @throws OauthLoginException
     *
     * @since 3.0.0
     */
    final public function oauthLogin(string $code): void
    {
        try {
            // try to get an access token (using the authorization code grant)
            $accessToken = $this->getOauthProvider()->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            VideosPlugin::$plugin->getOauth()->saveOauthAccessTokenByGateway($accessToken, $this);
        } catch (Exception $e) {
            throw new OauthLoginException(/* TODO: more precise message */);
        }
    }

    /**
     * Is enabled.
     *
     * @return bool
     *
     * @since 3.0.0
     */
    final public function isEnabled(): bool
    {
        try {
            $this->getOauthAccessToken();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Oauth logout.
     *
     * @return void
     * @throws OauthLogoutException
     *
     * @since 3.0.0
     */
    final public function oauthLogout(): void
    {
        try {
            VideosPlugin::$plugin->getOauth()->deleteOauthAccessTokenByGateway($this);
        } catch (Exception $e) {
            throw new OauthLogoutException(/* TODO: more precise message */);
        }
    }

    /**
     * Returns the OAuth account.
     *
     * @return OauthAccount
     * @throws OauthAccountNotFoundException
     *
     * @since 3.0.0
     */
    final public function getOauthAccount(): OauthAccount
    {
        try {
            if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
                $oauthAccount = VideosPlugin::$plugin->getCache()->get(OauthAccount::generateCacheKey(['gateway_handle' => $this->getHandle()]));

                if ($oauthAccount instanceof OauthAccount) {
                    return $oauthAccount;
                }
            }

            $resourceOwner = $this->getOauthProvider()->getResourceOwner($this->getOauthAccessToken());

            $oauthAccount = new OauthAccount([
                'id' => $resourceOwner->getId(),
                'name' => $resourceOwner->toArray()['name'] ?? '',
            ]);

            if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
                VideosPlugin::$plugin->getCache()->set(OauthAccount::generateCacheKey(['gateway_handle' => $this->getHandle()]), $oauthAccount);
            }

            return $oauthAccount;
        } catch (Exception $e) {
            throw new OauthAccountNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * Extracts the video ID from the video URL.
     *
     * @param string $videoUrl
     * @return string
     * @throws VideoIdExtractException
     *
     * @since 3.0.0
     */
    abstract public function extractVideoIdFromVideoUrl(string $videoUrl): string;

    /**
     * Creates an authenticated guzzle client.
     *
     * @return Client
     * @throws ApiClientCreateException
     *
     * @since 3.0.0
     */
    abstract public function createApiClient(): Client;

    /**
     * Returns an authenticated guzzle client.
     *
     * @return Client
     * @throws ApiClientCreateException
     *
     * @since 3.0.0
     */
    final public function getApiClient(): Client
    {
        if ($this->_apiClient === null) {
            $this->_apiClient = $this->createApiClient();
        }

        return $this->_apiClient;
    }

    /**
     * Return a video from its public URL.
     *
     * @param $videoUrl
     * @return Video
     * @throws VideoNotFoundException
     *
     * @since 3.0.0
     */
    final public function getVideoByUrl(string $videoUrl): Video
    {
        try {
            $videoId = $this->extractVideoIdFromVideoUrl($videoUrl);

            return $this->getVideoById($videoId);
        } catch (VideoIdExtractException $e) {
            throw new VideoNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * Returns one video by its ID.
     *
     * @param string $videoId
     * @return Video
     * @throws VideoNotFoundException
     *
     * @since 3.0.0
     */
    final public function getVideoById(string $videoId): Video
    {
        try {
            if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
                $video = VideosPlugin::$plugin->getCache()->get(Video::generateCacheKey(['id' => $videoId, 'gateway_handle' => $this->getHandle()]));

                if ($video instanceof Video) {
                    return $video;
                }
            }

            $video = $this->fetchVideoById($videoId);

            if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
                VideosPlugin::$plugin->getCache()->set(Video::generateCacheKey(['id' => $videoId, 'gateway_handle' => $this->getHandle()]), $video);
            }

            return $video;
        } catch (Exception $e) {
            throw new VideoNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * Requests the video from the API and then returns it as video object.
     *
     * @param string $videoId
     * @return Video
     * @throws VideoNotFoundException
     *
     * @since 3.0.0
     */
    abstract public function fetchVideoById(string $videoId): Video;

    /**
     * Returns the URL format of the embed.
     *
     * @return string
     *
     * @since 2.0.0
     */
    abstract public function getEmbedFormat(): string;

    /**
     * Returns a list of videos.
     *
     * @param string $method
     * @param array $options
     * @return mixed
     * @throws GatewayMethodNotFoundException
     *
     * @since 3.0.0
     */
    final public function getVideos(string $method, array $options = [])
    {
        $realMethod = 'getVideos'.ucwords($method);

        if (method_exists($this, $realMethod) === true) {
            return $this->{$realMethod}($options);
        }

        throw new GatewayMethodNotFoundException(/* TODO: more precise message */);
    }

    /**
     * Number of videos per page.
     *
     * @return int
     *
     * @since 2.0.0
     */
    public function getVideosPerPage(): int
    {
        return VideosPlugin::$plugin->getSettings()->videosPerPage;
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function supportsSearch(): bool
    {
        return false;
    }

    /**
     * Returns the videos'explorer.
     *
     * @return VideoExplorer
     * @throws ApiResponseException
     *
     * @since 3.0.0
     */
    abstract public function getExplorer(): VideoExplorer;

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    final public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'handle' => $this->getHandle(),
            'supportsSearch' => $this->supportsSearch(),
            'explorer' => $this->getExplorer(),
        ];
    }

    /**
     * Performs a GET request on the API.
     *
     * @param string $uri
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 3.0.0
     */
    final protected function fetch(string $uri, array $options = []): array
    {
        try {
            $client = $this->getApiClient();

            $response = $client->request('GET', $uri, $options);

            $responseBody = (string)$response->getBody();

            return Json::decode($responseBody);
        } catch (Exception $e) {
            throw new ApiResponseException(/* TODO: more precise message */);
        }
    }
}
