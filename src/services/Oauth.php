<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\errors\OauthTokenNotFoundException;
use dukt\videos\models\Token;
use dukt\videos\Plugin as VideosPlugin;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Token\AccessToken;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Class Oauth service.
 *
 * An instance of the Oauth service is globally accessible via [[Plugin::oauth `Plugin::$plugin->getOauth()`]].
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class Oauth extends Component
{
    /**
     * Get one access token by gateway handle.
     *
     * @param string $gatewayHandle
     * @param bool   $refresh
     *
     * @return null|AccessToken
     *
     * @throws InvalidConfigException
     */
    public function getAccessTokenByGatewayHandle(string $gatewayHandle, bool $refresh = true): ?AccessToken
    {
        try {
            $token = VideosPlugin::$plugin->getTokens()->getTokenByGatewayHandle($gatewayHandle);

            return $this->_generateAccessTokenFromData($gatewayHandle, $token->accessToken, $refresh);
        } catch (OauthTokenNotFoundException $e) {
            return null;
        }
    }

    /**
     * Saves an access token.
     *
     * @param string      $gatewayHandle
     * @param AccessToken $accessToken
     *
     * @return void
     *
     * @throws InvalidConfigException
     * @throws OauthTokenSaveException
     */
    public function saveAccessToken(string $gatewayHandle, AccessToken $accessToken): void
    {
        $token = new Token();

        try {
            $token = VideosPlugin::$plugin->getTokens()->getTokenByGatewayHandle($gatewayHandle);
        } catch (OauthTokenNotFoundException $e) {
            $token->gateway = $gatewayHandle;
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
    }

    /**
     * Generate access token from data.
     *
     * @param string $gatewayHandle
     * @param array  $data
     * @param bool   $refreshToken
     *
     * @return null|AccessToken
     *
     * @throws InvalidConfigException
     */
    private function _generateAccessTokenFromData(string $gatewayHandle, array $data, bool $refreshToken = true): ?AccessToken
    {
        if (!isset($data['accessToken'])) {
            return null;
        }

        $token = new AccessToken([
            'access_token' => $data['accessToken'] ?? null,
            'expires' => $data['expires'] ?? null,
            'refresh_token' => $data['refreshToken'] ?? null,
            'resource_owner_id' => $data['resourceOwnerId'] ?? null,
            'values' => $data['values'] ?? null,
        ]);

        // Refresh OAuth token
        if ($refreshToken && !empty($token->getRefreshToken()) && $token->getExpires() && $token->hasExpired()) {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle);
            $provider = $gateway->getOauthProvider();
            $grant = new RefreshToken();
            $newToken = $provider->getAccessToken($grant, ['refresh_token' => $token->getRefreshToken()]);

            $token = new AccessToken([
                'access_token' => $newToken->getToken(),
                'expires' => $newToken->getExpires(),
                'refresh_token' => $token->getRefreshToken(),
                'resource_owner_id' => $newToken->getResourceOwnerId(),
                'values' => $newToken->getValues(),
            ]);

            VideosPlugin::$plugin->getOauth()->saveAccessToken($gateway->getHandle(), $token);
        }

        return $token;
    }
}
