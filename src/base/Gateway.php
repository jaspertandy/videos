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
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\helpers\UrlHelper as VideosUrlHelper;
use dukt\videos\models\OauthAccount;
use dukt\videos\models\Video;
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
abstract class Gateway implements GatewayInterface, JsonSerializable
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
     * Returns the handle of the gateway based on its class name.
     *
     * @return string
     * @throws ReflectionException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    final public function getHandle(): string
    {
        $reflection = new ReflectionClass($this);

        return strtolower($reflection->getShortName());
    }

    /**
     * Returns the icon URL.
     *
     * @return null|string
     * @throws InvalidArgumentException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
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
     * TODO: report breaking changes (and update since ?)
     */
    public function getOauthProviderOptions(bool $parseEnv = true): array
    {
        return VideosPlugin::$plugin->getOauth()->getOauthProviderOptions($this, $parseEnv);
    }

    /**
     * Returns the OAuth provider.
     *
     * @return AbstractProvider
     * @throws InvalidConfigException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    final public function getOauthProvider(): AbstractProvider
    {
        if ($this->_oauthProvider === null) {
            $this->_oauthProvider = $this->createOauthProvider($this->getOauthProviderOptions());
        }

        return $this->_oauthProvider;
    }

    /**
     * Returns the OAuth redirect URI.
     *
     * @return string
     *
     * @since 3.0.0
     */
    public function getOauthRedirectUri(): string
    {
        return UrlHelper::actionUrl('videos/oauth/callback');
    }

    /**
     * Returns the redirect URI.
     *
     * @return string
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Gateway::getOauthRedirectUri]] instead.
     */
    public function getRedirectUri(): string
    {
        return $this->getOauthRedirectUri();
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
     * Returns the javascript origin URL.
     *
     * @return string
     * @throws SiteNotFoundException
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Gateway::getOauthJavascriptOrigin]] instead.
     */
    public function getJavascriptOrigin(): string
    {
        return $this->getOauthJavascriptOrigin();
    }

    /**
     * Returns the OAuth scope.
     *
     * @return array
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
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
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
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
     * Returns the OAuth token.
     *
     * @return null|AccessToken
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Gateway::getOauthAccessToken]] instead.
     */
    public function getOauthToken()
    {
        try {
            return $this->getOauthAccessToken();
        } catch (Exception $e) {
        }

        return null;
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
     * Is OAuth logged in.
     *
     * @return bool
     *
     * @since 3.0.0
     */
    final public function isOauthLoggedIn(): bool
    {
        try {
            $this->getOauthAccessToken();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Has token.
     *
     * @return bool
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Gateway::isOauthLoggedIn]] instead.
     */
    public function hasToken(): bool
    {
        return $this->isOauthLoggedIn();
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
     * Returns the account.
     *
     * @return mixed
     * @throws Exception
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Gateway::getOauthAccount]] instead.
     */
    public function getAccount()
    {
        try {
            return $this->getOauthAccount();
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Returns one video by its ID.
     *
     * @param string $videoId
     * @return Video
     * @throws VideoNotFoundException
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
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
     * Return a video from its public URL.
     *
     * @param $videoUrl
     * @return Video
     * @throws VideoNotFoundException
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Videos::getVideoByUrl]] instead.
     */
    public function getVideoByUrl($videoUrl)
    {
        return VideosPlugin::$plugin->getVideos()->getVideoByUrl($videoUrl);
    }

    /**
     * Returns the HTML of the embed from a video ID.
     *
     * @param string $videoId
     * @param array $options
     * @return string
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    final public function getEmbedHtml(string $videoId, array $options = []): string
    {
        $urlOptions = [];
        $attributeOptions = [
            'title' => [
                'value' => 'External video from '.$this->getHandle(),
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

        $embedUrl = $this->getEmbedUrl($videoId, $urlOptions);

        $embedAttributesString = implode(' ', array_map(function ($value, $attr) {
            return sprintf('%s="%s"', $attr, $value);
        }, $attributeOptions, array_keys($attributeOptions)));

        return '<iframe src="'.$embedUrl.'"'.$embedAttributesString.'></iframe>';
    }

    /**
     * Returns the URL of the embed from a video ID.
     *
     * @param string $videoId
     * @param array $options
     * @return string
     *
     * @since 2.0.0
     * TODO: report breaking changes (and update since ?)
     */
    final public function getEmbedUrl(string $videoId, array $options = []): string
    {
        $format = $this->getEmbedFormat();

        $formatParts = parse_url($format);
        $formatPartQueryParams = [];

        if (isset($formatParts['query']) === true) {
            parse_str($formatParts['query'], $formatPartQueryParams);
        }

        $formatParts['query'] = http_build_query(array_merge($formatPartQueryParams, $options));

        return sprintf(VideosUrlHelper::buildUrl($formatParts), $videoId);
    }

    /**
     * Returns a list of videos.
     *
     * @param string $method
     * @param array $options
     * @return mixed
     * @throws GatewayMethodNotFoundException
     *
     * @since 2.0.0
     */
    public function getVideos(string $method, array $options = [])
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
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public function jsonSerialize()
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
