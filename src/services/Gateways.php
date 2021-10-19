<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\base\Gateway;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\events\RegisterGatewayTypesEvent;
use dukt\videos\gateways\Vimeo;
use dukt\videos\gateways\YouTube;
use yii\base\Component;

/**
 * Gateways service.
 *
 * An instance of the Gateways service is globally accessible via [[Plugin::gateways `Videos::$plugin->getGateways()`]].
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Gateways extends Component
{
    /**
     * @var array all gateways
     */
    private array $_gateways = [];

    /**
     * {@inheritdoc}
     *
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public function init(): void
    {
        parent::init();

        foreach ($this->_getGatewayTypes() as $gatewayType) {
            // type needs to be a valid path to an existing class
            if (class_exists($gatewayType) === false) {
                throw new GatewayNotFoundException(/* TODO: more precise message */);
            }

            $gateway = new $gatewayType();

            $this->_gateways[] = $gateway;
        }
    }

    /**
     * Get all gateways.
     *
     * @param null|bool $enabled
     * @return Gateway[]
     *
     * @since 3.0.0
     */
    public function getGateways(?bool $enabled = null): array
    {
        if ($enabled !== null) {
            return array_filter($this->_gateways, function ($_gateway) use ($enabled) {
                return $_gateway->isEnabled() === $enabled;
            });
        }

        return $this->_gateways;
    }

    /**
     * Has enabled gateways.
     *
     * @return bool
     *
     * @since 3.0.0
     */
    public function hasEnabledGateways(): bool
    {
        return count($this->getGateways(true)) > 0;
    }

    /**
     * Get one gateway by handle.
     *
     * @param string $gatewayHandle
     * @param null|bool $enabled
     * @return Gateway
     * @throws GatewayNotFoundException
     *
     * @since 3.0.0
     */
    public function getGatewayByHandle(string $gatewayHandle, ?bool $enabled = null): Gateway
    {
        foreach ($this->getGateways($enabled) as $gateway) {
            if ($gateway->getHandle() === $gatewayHandle) {
                return $gateway;
            }
        }

        throw new GatewayNotFoundException(/* TODO: more precise message */);
    }

    /**
     * Returns gateway types.
     *
     * @return array
     */
    private function _getGatewayTypes(): array
    {
        $gatewayTypes = [
            Vimeo::class,
            YouTube::class,
        ];

        $event = new RegisterGatewayTypesEvent([
            'gatewayTypes' => $gatewayTypes,
        ]);

        $this->trigger(RegisterGatewayTypesEvent::NAME, $event);

        sort($event->gatewayTypes);

        return $event->gatewayTypes;
    }
}
