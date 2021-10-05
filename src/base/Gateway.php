<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use dukt\videos\errors\ApiResponseException;
use dukt\videos\errors\GatewayMethodNotFoundException;
use dukt\videos\errors\JsonParsingException;
use dukt\videos\errors\OauthAccessTokenNotFoundException;
use dukt\videos\errors\OauthAccountNotFoundException;
use dukt\videos\errors\OauthLoginException;
use dukt\videos\errors\OauthLogoutException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\helpers\UrlHelper as VideosUrlHelper;
use dukt\videos\models\OauthAccount;
use dukt\videos\models\Token;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Gateway is the base class for classes representing video gateways.
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  1.0
 */
abstract class Gateway implements GatewayInterface
{
    /**
     * Returns the handle of the gateway based on its class name.
     *
     * @return string
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
     */
    public function getOauthProviderName(): string
    {
        return $this->getName();
    }

    /**
     * Returns the OAuth provider options.
     *
     * @param bool $parseEnv
     *
     * @return array
     */
    public function getOauthProviderOptions(bool $parseEnv = true): array
    {
        return VideosPlugin::$plugin->getOauth()->getOauthProviderOptions($this, $parseEnv);
    }

    /**
     * Returns the OAuth provider.
     *
     * @return AbstractProvider
     *
     * @throws InvalidConfigException
     */
    final public function getOauthProvider(): AbstractProvider
    {
        return $this->createOauthProvider($this->getOauthProviderOptions());
    }

    /**
     * Returns the OAuth redirect URI.
     *
     * @return string
     */
    public function getOauthRedirectUri(): string
    {
        return UrlHelper::actionUrl('videos/oauth/callback');
    }

    /**
     * Returns the OAuth javascript origin URL.
     *
     * @return string
     *
     * @throws SiteNotFoundException
     */
    public function getOauthJavascriptOrigin(): string
    {
        return UrlHelper::baseUrl();
    }

    /**
     * Returns the OAuth scope.
     *
     * @return array
     */
    public function getOauthScope(): array
    {
        return [];
    }

    /**
     * Returns the OAuth authorization options.
     *
     * @return array
     */
    public function getOauthAuthorizationOptions(): array
    {
        return [];
    }

    /**
     * Returns the OAuth authorization URL.
     *
     * @return string
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
     *
     * @throws OauthAccessTokenNotFoundException
     */
    final public function getOauthAccessToken(): AccessToken
    {
        return VideosPlugin::$plugin->getOauth()->getOauthAccessTokenByGateway($this);
    }

    /**
     * Oauth login.
     *
     * @param string $code
     *
     * @return void
     *
     * @throws OauthLoginException
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
     * Oauth logout.
     *
     * @return void
     *
     * @throws OauthLogoutException
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
     *
     * @throws OauthAccountNotFoundException
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
     * Returns one video by its ID.
     *
     * @param string $videoId
     *
     * @return Video
     *
     * @throws VideoNotFoundException
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
     * Returns the HTML of the embed from a video ID.
     *
     * @param string $videoId
     * @param array  $options
     *
     * @return string
     */
    public function getEmbedHtml(string $videoId, array $options = []): string
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
     * @param array  $options
     *
     * @return string
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
     * @param $method
     * @param $options
     *
     * @return mixed
     *
     * @throws GatewayMethodNotFoundException
     */
    public function getVideos($method, $options)
    {
        $realMethod = 'getVideos'.ucwords($method);

        if (method_exists($this, $realMethod)) {
            return $this->{$realMethod}($options);
        }

        throw new GatewayMethodNotFoundException('Gateway method “'.$realMethod.'” not found.');
    }

    /**
     * Number of videos per page.
     *
     * @return int
     */
    public function getVideosPerPage(): int
    {
        return VideosPlugin::$plugin->getSettings()->videosPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSearch(): bool
    {
        return false;
    }

    /**
     * Returns an authenticated Guzzle client.
     *
     * @return Client
     */
    abstract protected function createClient(): Client;

    /**
     * Performs a GET request on the API.
     *
     * @param       $uri
     * @param array $options
     *
     * @return array
     *
     * @throws ApiResponseException
     */
    protected function get($uri, array $options = []): array
    {
        $client = $this->createClient();

        try {
            $response = $client->request('GET', $uri, $options);
            $body = (string)$response->getBody();
            $data = Json::decode($body);
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
            $body = (string)$response->getBody();

            try {
                $data = Json::decode($body);
            } catch (JsonParsingException $e) {
                throw $badResponseException;
            }
        }

        $this->checkResponse($response, $data);

        return $data;
    }

    /**
     * Checks a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param                   $data
     *
     * @throws ApiResponseException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $code = 0;
            $error = $data['error'];

            if (\is_array($error)) {
                $code = $error['code'];
                $error = $error['message'];
            }

            throw new ApiResponseException($error, $code);
        }
    }
}
