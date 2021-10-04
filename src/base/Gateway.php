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
use dukt\videos\errors\OauthLogoutException;
use dukt\videos\errors\OauthRefreshAccessTokenException;
use dukt\videos\errors\OauthSaveAccessTokenException;
use dukt\videos\errors\TokenInvalidException;
use dukt\videos\errors\TokenNotFoundException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\OauthAccount;
use dukt\videos\models\Token;
use dukt\videos\models\Video;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use League\OAuth2\Client\Grant\RefreshToken;
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
     * Returns the OAuth provider’s name.
     *
     * @return string
     */
    public function getOauthProviderName(): string
    {
        return $this->getName();
    }

    /**
     * Returns the redirect URI.
     *
     * @return string
     */
    public function getOauthRedirectUri(): string
    {
        return UrlHelper::actionUrl('videos/oauth/callback');
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
        try {
            $token = VideosPlugin::$plugin->getTokens()->getTokenByGatewayHandle($this->getHandle());

            if (isset($token->accessToken['accessToken']) === false) {
                throw new TokenInvalidException(/* TODO: more precise message */);
            }

            $accessToken = new AccessToken([
                'access_token' => $token->accessToken['accessToken'] ?? null,
                'expires' => $token->accessToken['expires'] ?? null,
                'refresh_token' => $token->accessToken['refreshToken'] ?? null,
                'resource_owner_id' => $token->accessToken['resourceOwnerId'] ?? null,
                'values' => $token->accessToken['values'] ?? null,
            ]);

            return $this->refreshOauthAccessToken($accessToken);
        } catch (Exception $e) {
            throw new OauthAccessTokenNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * Refreshes Oauth access token.
     *
     * @param AccessToken $accessToken
     *
     * @return AccessToken
     *
     * @throws OauthRefreshAccessTokenException
     */
    final public function refreshOauthAccessToken(AccessToken $accessToken): AccessToken
    {
        try {
            if ($accessToken->getRefreshToken() !== null && $accessToken->getExpires() !== null && $accessToken->hasExpired() === true) {
                $newAccessToken = $this->getOauthProvider()->getAccessToken(new RefreshToken(), ['refresh_token' => $accessToken->getRefreshToken()]);

                $this->saveOauthAccessToken($newAccessToken);

                return $newAccessToken;
            }

            return $accessToken;
        } catch (Exception $e) {
            throw new OauthRefreshAccessTokenException(/* TODO: more precise message */);
        }
    }

    /**
     * Saves Oauth access token.
     *
     * @param AccessToken $accessToken
     *
     * @return void
     *
     * @throws OauthSaveAccessTokenException
     */
    final public function saveOauthAccessToken(AccessToken $accessToken): void
    {
        try {
            $token = new Token();

            try {
                $token = VideosPlugin::$plugin->getTokens()->getTokenByGatewayHandle($this->getHandle());
            } catch (TokenNotFoundException $e) {
                $token->gateway = $this->getHandle();
            }

            $token->accessToken = [
                'accessToken' => $accessToken->getToken(),
                'expires' => $accessToken->getExpires(),
                'resourceOwnerId' => $accessToken->getResourceOwnerId(),
                'values' => $accessToken->getValues(),
            ];

            if (!empty($accessToken->getRefreshToken())) {
                $token->accessToken['refreshToken'] = $accessToken->getRefreshToken();
            }

            VideosPlugin::$plugin->getTokens()->saveToken($token);
        } catch (Exception $e) {
            throw new OauthSaveAccessTokenException(/* TODO: more precise message */);
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
            VideosPlugin::$plugin->getTokens()->deleteTokenByGatewayHandle($this->getHandle());
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
                'name' => isset($resourceOwner->toArray()['name']) === true ? $resourceOwner->toArray()['name'] : '',
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

            $video = $this->callVideoById($videoId);

            if (VideosPlugin::$plugin->getCache()->isEnabled() === true) {
                VideosPlugin::$plugin->getCache()->set(Video::generateCacheKey(['id' => $videoId, 'gateway_handle' => $this->getHandle()]), $video);
            }

            return $video;
        } catch (Exception $e) {
            throw new VideoNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * OAuth Connect.
     *
     * @return Response
     *
     * @throws InvalidConfigException
     */
    public function oauthConnect(): Response
    {
        Craft::$app->getSession()->set('videos.oauthState', $this->getOauthProvider()->getState());

        return Craft::$app->getResponse()->redirect($this->getOauthAuthorizationUrl());
    }

    /**
     * OAuth Callback.
     *
     * @return Response
     *
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public function oauthCallback(): Response
    {
        $provider = $this->getOauthProvider();

        $code = Craft::$app->getRequest()->getParam('code');

        try {
            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            // Save token
            $this->saveOauthAccessToken($token);

            // Reset session variables

            // Redirect
            Craft::$app->getSession()->setNotice(Craft::t('videos', 'Connected to {gateway}.', ['gateway' => $this->getName()]));
        } catch (Exception $e) {
            Craft::error('Couldn’t connect to video gateway:'."\r\n"
                .'Message: '."\r\n".$e->getMessage()."\r\n"
                .'Trace: '."\r\n".$e->getTraceAsString(), __METHOD__);

            // Failed to get the token credentials or user details.
            Craft::$app->getSession()->setError($e->getMessage());
        }

        $redirectUrl = UrlHelper::cpUrl('videos/settings');

        return Craft::$app->getResponse()->redirect($redirectUrl);
    }

    /**
     * Returns the OAuth provider options.
     *
     * @param bool $parse
     *
     * @return array
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function getOauthProviderOptions(bool $parse = true): array
    {
        return VideosPlugin::$plugin->getOauthProviderOptions($this->getHandle(), $parse);
    }

    /**
     * Returns the HTML of the embed from a video ID.
     *
     * @param       $videoId
     * @param array $options
     *
     * @return string
     */
    public function getEmbedHtml($videoId, array $options = []): string
    {
        $embedAttributes = [
            'title' => 'External video from '.$this->getHandle(),
            'frameborder' => '0',
            'allowfullscreen' => 'true',
            'allowscriptaccess' => 'true',
            'allow' => 'autoplay; encrypted-media',
        ];

        $disableSize = $options['disable_size'] ?? false;

        if (!$disableSize) {
            $this->parseEmbedAttribute($embedAttributes, $options, 'width', 'width');
            $this->parseEmbedAttribute($embedAttributes, $options, 'height', 'height');
        }

        $title = $options['title'] ?? false;

        if ($title) {
            $this->parseEmbedAttribute($embedAttributes, $options, 'title', 'title');
        }

        $this->parseEmbedAttribute($embedAttributes, $options, 'iframeClass', 'class');

        $embedUrl = $this->getEmbedUrl($videoId, $options);

        $embedAttributesString = '';

        foreach ($embedAttributes as $key => $value) {
            $embedAttributesString .= ' '.$key.'="'.$value.'"';
        }

        return '<iframe src="'.$embedUrl.'"'.$embedAttributesString.'></iframe>';
    }

    /**
     * Returns the URL of the embed from a video ID.
     *
     * @param       $videoId
     * @param array $options
     *
     * @return string
     */
    public function getEmbedUrl($videoId, array $options = []): string
    {
        $format = $this->getEmbedFormat();

        if (\count($options) > 0) {
            $queryMark = '?';

            if (strpos($this->getEmbedFormat(), '?') !== false) {
                $queryMark = '&';
            }

            $options = http_build_query($options);

            $format .= $queryMark.$options;
        }

        return sprintf($format, $videoId);
    }

    /**
     * Returns the javascript origin URL.
     *
     * @return string
     *
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getJavascriptOrigin(): string
    {
        return UrlHelper::baseUrl();
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
     * @return mixed
     */
    public function getVideosPerPage()
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

    // Private Methods
    // =========================================================================

    /**
     * Parse embed attribute.
     *
     * @param $embedAttributes
     * @param $options
     * @param $option
     * @param $attribute
     *
     * @return null
     */
    private function parseEmbedAttribute(&$embedAttributes, &$options, $option, $attribute)
    {
        if (isset($options[$option])) {
            $embedAttributes[$attribute] = $options[$option];
            unset($options[$option]);
        }

        return null;
    }
}
