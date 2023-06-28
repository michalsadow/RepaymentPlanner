<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * Rate amount is negative.
 */
class NegativeRateException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'Rate amount is negative.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'date',
        'rate',
    ];
}
