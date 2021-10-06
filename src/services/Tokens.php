<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\base\Gateway;
use dukt\videos\errors\TokenDeleteException;
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
 */
class Tokens extends Component
{
    /**
     * Returns one token by gateway.
     *
     * @param Gateway $gateway
     * @return Token
     * @throws TokenNotFoundException
     *
     * @since 3.0.0
     */
    public function getTokenByGateway(Gateway $gateway): Token
    {
        $tokenRecord = TokenRecord::findOne(['gateway' => $gateway->getHandle()]);

        if ($tokenRecord === null) {
            throw new TokenNotFoundException(/* TODO: more precise message */);
        }

        return new Token($tokenRecord->toArray([
            'id',
            'gateway',
            'accessToken',
        ]));
    }

    /**
     * Saves a token.
     *
     * @param Token $token
     * @return void
     * @throws TokenSaveException
     *
     * @since 2.0.8
     * TODO: report breaking changes (and update since ?)
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
     * Deletes a token by gateway.
     *
     * @param Gateway $gateway
     * @return void
     * @throws TokenDeleteException
     *
     * @since 3.0.0
     */
    public function deleteTokenByGateway(Gateway $gateway): void
    {
        try {
            $tokenRecord = TokenRecord::findOne(['gateway' => $gateway->getHandle()]);

            if ($tokenRecord === null) {
                throw new TokenNotFoundException(/* TODO: more precise message */);
            }

            if ($tokenRecord->delete() === false) {
                throw new TokenDeleteException(/* TODO: more precise message */);
            }
        } catch (Exception $e) {
            throw new TokenDeleteException(/* TODO: more precise message */);
        }
    }

    /**
     * Get a token by its gateway handle.
     *
     * @param string $gatewayHandle
     * @return null|Token
     *
     * @since 2.0.8
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Tokens::getTokenByGateway]] instead.
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
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Tokens::getTokenByGateway]] instead.
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
