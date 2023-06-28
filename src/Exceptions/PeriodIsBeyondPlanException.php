<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * Period is beyond plan - app adds new periods until one exceedes schedule - than it goes one step back.
 *
 * @phpcs:disable Generic.Files.LineLength
 */
class PeriodIsBeyondPlanException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'Period is beyond plan - app adds new periods until one exceedes schedule - than it goes one step back.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'firstDay',
        'lastDay',
    ];
}
