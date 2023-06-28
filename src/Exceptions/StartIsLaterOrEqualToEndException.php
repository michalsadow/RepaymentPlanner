<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * Start has to be earlier then end.
 */
class StartIsLaterOrEqualToEndException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'Start has to be earlier then end.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'start',
        'end',
    ];
}
