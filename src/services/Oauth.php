<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use Craft;
use dukt\videos\base\Gateway;
use dukt\videos\errors\OauthAccessTokenNotFoundException;
use dukt\videos\errors\OauthDeleteAccessTokenException;
use dukt\videos\errors\OauthRefreshAccessTokenException;
use dukt\videos\errors\OauthSaveAccessTokenException;
use dukt\videos\errors\TokenInvalidException;
use dukt\videos\errors\TokenNotFoundException;
use dukt\videos\models\Token;
use dukt\videos\Plugin as VideosPlugin;
use Exception;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Token\AccessToken;
use yii\base\Component;

/**
 * Oauth service.
 *
 * An instance of the Oauth service is globally accessible via [[Plugin::oauth `VideosPlugin::$plugin->getOauth()`]].
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Oauth extends Component
{
    /**
     * Returns OAuth provider options.
     *
     * @param Gateway $gateway
     * @param bool $parseEnv
     * @return array
     *
     * @since 3.0.0
     */
    public function getOauthProviderOptions(Gateway $gateway, bool $parseEnv = true): array
    {
        $options = [];

        $configSettings = Craft::$app->config->getConfigFromFile(VideosPlugin::$plugin->id);

        if (isset($configSettings['oauthProviderOptions'][$gateway->getHandle()]) === true) {
            $options = $configSettings['oauthProviderOptions'][$gateway->getHandle()];
        }

        $storedSettings = Craft::$app->plugins->getStoredPluginInfo(VideosPlugin::$plugin->id)['settings'];

        if (empty($options) === true && isset($storedSettings['oauthProviderOptions'][$gateway->getHandle()]) === true) {
            $options = $storedSettings['oauthProviderOptions'][$gateway->getHandle()];
        }

        if (isset($options['redirectUri']) === false) {
            $options['redirectUri'] = $gateway->getOauthRedirectUri();
        }

        return $parseEnv === true ? array_map('Craft::parseEnv', $options) : $options;
    }

    /**
     * Returns the OAuth access token by gateway.
     *
     * @param Gateway $gateway
     * @return AccessToken
     * @throws OauthAccessTokenNotFoundException
     *
     * @since 3.0.0
     */
    public function getOauthAccessTokenByGateway(Gateway $gateway): AccessToken
    {
        try {
            $token = VideosPlugin::$plugin->getTokens()->getTokenByGateway($gateway);

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

            return $this->refreshOauthAccessTokenByGateway($accessToken, $gateway);
        } catch (Exception $e) {
            throw new OauthAccessTokenNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * Refreshes Oauth access token by gateway.
     *
     * @param AccessToken $accessToken
     * @param Gateway $gateway
     * @return AccessToken
     * @throws OauthRefreshAccessTokenException
     *
     * @since 3.0.0
     */
    public function refreshOauthAccessTokenByGateway(AccessToken $accessToken, Gateway $gateway): AccessToken
    {
        try {
            if ($accessToken->getRefreshToken() !== null && $accessToken->getExpires() !== null && $accessToken->hasExpired() === true) {
                $newAccessToken = $gateway->getOauthProvider()->getAccessToken(new RefreshToken(), ['refresh_token' => $accessToken->getRefreshToken()]);

                $this->saveOauthAccessTokenByGateway($newAccessToken, $gateway);

                return $newAccessToken;
            }

            return $accessToken;
        } catch (Exception $e) {
            throw new OauthRefreshAccessTokenException(/* TODO: more precise message */);
        }
    }

    /**
     * Saves Oauth access token by gateway.
     *
     * @param AccessToken $accessToken
     * @param Gateway $gateway
     * @return void
     * @throws OauthSaveAccessTokenException
     *
     * @since 3.0.0
     */
    public function saveOauthAccessTokenByGateway(AccessToken $accessToken, Gateway $gateway): void
    {
        try {
            $token = new Token();

            try {
                $token = VideosPlugin::$plugin->getTokens()->getTokenByGateway($gateway);
            } catch (TokenNotFoundException $e) {
                $token->gateway = $gateway->getHandle();
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
     * Deletes Oauth access token by gateway.
     *
     * @param Gateway $gateway
     * @return void
     * @throws OauthDeleteAccessTokenException
     *
     * @since 3.0.0
     */
    public function deleteOauthAccessTokenByGateway(Gateway $gateway): void
    {
        try {
            VideosPlugin::$plugin->getTokens()->deleteTokenByGateway($gateway);
        } catch (Exception $e) {
            throw new OauthDeleteAccessTokenException(/* TODO: more precise message */);
        }
    }

    /**
     * Returns a token by its gateway handle.
     *
     * @param string $gatewayHandle
     * @param bool $refresh
     * @return null|AccessToken
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::getOauthAccessTokenByGateway]] instead.
     */
    public function getToken($gatewayHandle, $refresh = true)
    {
        try {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            return $this->getOauthAccessTokenByGateway($gateway);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Saves a token.
     *
     * @param string $gatewayHandle
     * @param AccessToken $token
     * @return bool
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::saveOauthAccessTokenByGateway]] instead.
     */
    public function saveToken($gatewayHandle, AccessToken $token): bool
    {
        try {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            return $this->saveOauthAccessTokenByGateway($token, $gateway);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a token.
     *
     * @param string $gatewayHandle
     * @return bool
     *
     * @since 2.0.0
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::deleteOauthAccessTokenByGateway]] instead.
     */
    public function deleteToken($gatewayHandle): bool
    {
        try {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            return $this->deleteOauthAccessTokenByGateway($gateway);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
