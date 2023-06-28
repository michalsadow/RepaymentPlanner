<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * First repayment is before or after schedule.
 */
class FirstRepaymentExceedesScheduleException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'First repayment is before or after schedule.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'scheduleStart',
        'scheduleEnd',
        'firstRepaymentDate',
    ];
}
