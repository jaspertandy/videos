<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\base;

use Throwable;

/**
 * Base exception.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
abstract class Exception extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct(?string $message = null, ?Throwable $previous = null)
    {
        if ($previous !== null) {
            parent::__construct($message, $previous->getCode(), $previous);
        } else {
            parent::__construct($message);
        }
    }
}
