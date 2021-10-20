<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use Craft;
use craft\helpers\Json;
use dukt\videos\base\Gateway;
use dukt\videos\errors\OauthAccessTokenNotFoundException;
use dukt\videos\errors\OauthDeleteAccessTokenException;
use dukt\videos\errors\OauthRefreshAccessTokenException;
use dukt\videos\errors\OauthSaveAccessTokenException;
use dukt\videos\errors\TokenDeleteException;
use dukt\videos\errors\TokenInvalidException;
use dukt\videos\errors\TokenNotFoundException;
use dukt\videos\errors\TokenSaveException;
use dukt\videos\Plugin as VideosPlugin;
use dukt\videos\records\Token;
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
            $options['redirectUri'] = $gateway->getOauthRedirectUrl();
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
            $token = Token::findOne(['gateway' => $gateway->getHandle()]);

            if ($token === null) {
                throw new TokenNotFoundException(/* TODO: more precise message */);
            }

            $accessTokenData = Json::decode($token->accessToken);

            if (isset($accessTokenData['accessToken']) === false) {
                throw new TokenInvalidException(/* TODO: more precise message */);
            }

            $accessToken = new AccessToken([
                'access_token' => $accessTokenData['accessToken'] ?? null,
                'expires' => $accessTokenData['expires'] ?? null,
                'refresh_token' => $accessTokenData['refreshToken'] ?? null,
                'resource_owner_id' => $accessTokenData['resourceOwnerId'] ?? null,
                'values' => $accessTokenData['values'] ?? null,
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

                if (!$newAccessToken instanceof AccessToken) {
                    throw new OauthRefreshAccessTokenException(/* TODO: more precise message */);
                }

                $this->saveOauthAccessTokenByGateway(new AccessToken([
                    'access_token' => $newAccessToken->getToken(),
                    'expires' => $newAccessToken->getExpires(),
                    'refresh_token' => $accessToken->getRefreshToken(),
                    'resource_owner_id' => $newAccessToken->getResourceOwnerId(),
                    'values' => $newAccessToken->getValues(),
                ]), $gateway);

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
            $token = Token::findOne(['gateway' => $gateway->getHandle()]);

            if ($token === null) {
                $token = new Token();
                $token->gateway = $gateway->getHandle();
            }

            $token->accessToken = [
                'accessToken' => $accessToken->getToken(),
                'expires' => $accessToken->getExpires(),
                'refreshToken' => $accessToken->getRefreshToken(),
                'resourceOwnerId' => $accessToken->getResourceOwnerId(),
                'values' => $accessToken->getValues(),
            ];

            if ($token->save() === false) {
                //throw new TokenInvalidException(/* TODO: more precise message */);
                throw new TokenSaveException(/* TODO: more precise message */);
            }
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
            $token = Token::findOne(['gateway' => $gateway->getHandle()]);

            if ($token === null) {
                throw new TokenNotFoundException(/* TODO: more precise message */);
            }

            if ($token->delete() === false) {
                throw new TokenDeleteException(/* TODO: more precise message */);
            }
        } catch (Exception $e) {
            throw new OauthDeleteAccessTokenException(/* TODO: more precise message */);
        }
    }
}
