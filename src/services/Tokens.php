<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\base\Gateway;
use dukt\videos\errors\TokenInvalidException;
use dukt\videos\errors\TokenNotFoundException;
use dukt\videos\errors\TokenSaveException;
use dukt\videos\models\Token;
use dukt\videos\Plugin as VideosPlugin;
use dukt\videos\records\Token as TokenRecord;
use Exception;
use yii\base\Component;

/**
 * Tokens service.
 *
 * An instance of the Tokens service is globally accessible via [[Plugin::tokens `VideosPlugin::$plugin->getTokens()`]].
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.8
 * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth]] instead.
 */
class Tokens extends Component
{
    /**
     * Saves a token.
     *
     * @param Token $token
     * @return void
     * @throws TokenSaveException
     *
     * @since 2.0.8
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::getOauthAccessTokenByGateway]] instead.
     */
    public function saveToken(Token $token): void
    {
        try {
            if ($token->validate() === false) {
                throw new TokenInvalidException(/* TODO: more precise message */);
            }

            $tokenRecord = new TokenRecord();

            if ($token->id !== null) {
                $tokenRecord = TokenRecord::findOne($token->id);

                if ($tokenRecord === null) {
                    throw new TokenNotFoundException(/* TODO: more precise message */);
                }
            }

            $tokenRecord->gateway = $token->gateway;
            $tokenRecord->accessToken = $token->accessToken;

            $tokenRecord->save(false);
        } catch (Exception $e) {
            throw new TokenSaveException(/* TODO: more precise message */);
        }
    }

    /**
     * Get a token by its gateway handle.
     *
     * @param string $gatewayHandle
     * @return null|Token
     *
     * @since 2.0.8
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::getOauthAccessTokenByGateway]] instead.
     */
    public function getToken($gatewayHandle)
    {
        try {
            $gateway = VideosPlugin::$plugin->getGateways()->getGatewayByHandle($gatewayHandle, true);

            return $this->getTokenByGateway($gateway);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Deletes a token.
     *
     * @param int $id
     * @return bool
     *
     * @since 2.0.8
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::deleteOauthAccessTokenByGateway]] instead.
     */
    public function deleteTokenById(int $id): bool
    {
        $tokenRecord = TokenRecord::findOne($id);

        if (!$tokenRecord) {
            return true;
        }

        $tokenRecord->delete();

        return true;
    }
}
