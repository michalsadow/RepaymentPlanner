<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * Given type of period is unknown.
 */
class UnknownPeriodTypeException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'Given type of period is unknown.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'givenPeriodType',
        'properPeriodTypes',
    ];
}
