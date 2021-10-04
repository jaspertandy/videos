<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\errors\OauthTokenDeleteException;
use dukt\videos\errors\OauthTokenInvalidException;
use dukt\videos\errors\OauthTokenNotFoundException;
use dukt\videos\errors\OauthTokenSaveException;
use dukt\videos\models\Token;
use dukt\videos\records\Token as TokenRecord;
use Exception;
use yii\base\Component;

/**
 * Tokens service.
 *
 * An instance of the Tokens service is globally accessible via [[Plugin::oauth `VideosPlugin::$plugin->getTokens()`]].
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class Tokens extends Component
{
    /**
     * Get one token by its gateway handle.
     *
     * @param string $gatewayHandle
     *
     * @return Token
     *
     * @throws OauthTokenNotFoundException
     */
    public function getTokenByGatewayHandle(string $gatewayHandle): Token
    {
        $tokenRecord = TokenRecord::findOne(['gateway' => $gatewayHandle]);

        if ($tokenRecord === null) {
            throw new OauthTokenNotFoundException(/* TODO: more precise message */);
        }

        return new Token($tokenRecord->toArray([
            'id',
            'gateway',
            'accessToken',
        ]));
    }

    /**
     * Save a token.
     *
     * @param Token $token
     *
     * @return void
     *
     * @throws OauthTokenSaveException
     */
    public function saveToken(Token $token): void
    {
        try {
            if ($token->validate() === false) {
                throw new OauthTokenInvalidException(/* TODO: more precise message */);
            }

            $tokenRecord = new TokenRecord();

            if ($token->id !== null) {
                $tokenRecord = TokenRecord::findOne($token->id);

                if ($tokenRecord === null) {
                    throw new OauthTokenNotFoundException(/* TODO: more precise message */);
                }
            }

            $tokenRecord->gateway = $token->gateway;
            $tokenRecord->accessToken = $token->accessToken;

            $tokenRecord->save(false);
        } catch (Exception $e) {
            throw new OauthTokenSaveException(/* TODO: more precise message */);
        }
    }

    /**
     * Delete a token by its gateway handle.
     *
     * @param string $gatewayHandle
     *
     * @return void
     *
     * @throws OauthTokenDeleteException
     */
    public function deleteTokenByGatewayHandle(string $gatewayHandle): void
    {
        try {
            $tokenRecord = TokenRecord::findOne(['gateway' => $gatewayHandle]);

            if ($tokenRecord === null) {
                throw new OauthTokenNotFoundException(/* TODO: more precise message */);
            }

            if ($tokenRecord->delete() === false) {
                throw new OauthTokenDeleteException(/* TODO: more precise message */);
            }
        } catch (Exception $e) {
            throw new OauthTokenDeleteException(/* TODO: more precise message */);
        }
    }
}
