<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\web\twig;

use dukt\videos\helpers\DateTimeHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class Extension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('durationNumeric', [DateTimeHelper::class, 'formatDateIntervalToReadable'], ['is_safe' => ['html']]),
            new TwigFilter('durationISO8601', [DateTimeHelper::class, 'formatDateIntervalToISO8601'], ['is_safe' => ['html']]),
        ];
    }
}
