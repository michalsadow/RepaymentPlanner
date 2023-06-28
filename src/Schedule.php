<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateInterval;
use DateTime;
use Przeslijmi\RepaymentPlanner\Exceptions\FirstRepaymentExceedesScheduleException;
use Przeslijmi\RepaymentPlanner\Exceptions\NegativePaymentException;
use Przeslijmi\RepaymentPlanner\Exceptions\NegativeRateException;
use Przeslijmi\RepaymentPlanner\Exceptions\StartIsLaterOrEqualToEndException;
use Przeslijmi\RepaymentPlanner\Flows;

/**
 * Main Class object - repayment Schedule generator.
 */
class Schedule extends Flows
{

    /**
     * Engagements in dates.
     *
     * @var array
     */
    private $engagements = [];

    /**
     * Rates to be used for interests calculations.
     *
     * @var array
     */
    private $rates = [];

    /**
     * Start of Schedule.
     *
     * @var DateTime
     */
    private $start;

    /**
     * First repayment date for calculation (as a result of grace period).
     *
     * @var DateTime
     */
    private $firstRepaymentDate;

    /**
     * End of Schedule
     *
     * @var DateTime
     */
    private $end;

    /**
     * Do take days calculation for each period (default, false)?
     *
     * @var boolean
     */
    private $isCalcDaily = false;

    /**
     * Installments collection object.
     *
     * @var Installments
     */
    private $installments;

    /**
     * Period type object.
     *
     * @var PeriodType
     */
    private $periodType;

    /**
     * Style of repayments (manual, linear, annuit, balloon).
     *
     * @var string
     */
    protected $repaymentsStyle = 'manual';

    /**
     * Constructor.
     *
     * @param float    $amount     Amount to be repayed since today.
     * @param float    $rate       Interest rate to use.
     * @param DateTime $start      Start of Schedule.
     * @param DateTime $end        End of Schudule.
     * @param string   $periodType Period type to use: yearly, quarterly, monthly.
     */
    public function __construct(
        float $amount,
        float $rate,
        DateTime $start,
        DateTime $end,
        string $periodType = 'monthly'
    ) {

        $realStart = ( clone $start )->add(new DateInterval('P1D'));

        // Save settings.
        $this->setStart($realStart);
        $this->setEnd($end);
        $this->addPayment($start, $amount);
        $this->addRate($start, $rate);

        // Prepare child objects.
        $this->periodType   = new PeriodType($periodType);
        $this->installments = new Installments($this);
    }

    /**
     * Getter for installments collection.
     *
     * @return Installments
     */
    public function getInstallments(): Installments
    {

        return $this->installments;
    }

    /**
     * Getter for period type object.
     *
     * @return PeriodType
     */
    public function getPeriodType(): PeriodType
    {

        return $this->periodType;
    }

    /**
     * Sets first day of calculation.
     *
     * @param DateTime $start First day of calculation.
     *
     * @throws StartIsLaterOrEqualToEndException When start is later than end.
     * @return self
     */
    public function setStart(DateTime $start): self
    {

        // Throw.
        if (empty($this->end) === false && $this->end < $start) {
            throw new StartIsLaterOrEqualToEndException([ $start->format('Y-m-d'), $this->end->format('Y-m-d') ]);
        }

        // Save.
        $this->start = $start->setTime(0, 0, 0);

        return $this;
    }

    /**
     * Return start date.
     *
     * @return DateTime
     */
    public function getStart(): DateTime
    {

        return $this->start;
    }

    /**
     * Sets last day of calculation.
     *
     * @param DateTime $end Last day of calculation.
     *
     * @throws StartIsLaterOrEqualToEndException When start is later than end.
     * @return self
     */
    public function setEnd(DateTime $end): self
    {

        // Throw.
        if (empty($this->start) === false && $this->start > $end) {
            throw new StartIsLaterOrEqualToEndException([ $this->start->format('Y-m-d'), $end->format('Y-m-d') ]);
        }

        // Save.
        $this->end = $end->setTime(0, 0, 0);

        return $this;
    }

    /**
     * Return end date.
     *
     * @return DateTime
     */
    public function getEnd(): DateTime
    {

        return $this->end;
    }

    /**
     * Getter for repayment style.
     *
     * @return string
     */
    public function getRepaymentsStyle(): string
    {

        return $this->repaymentsStyle;
    }

    /**
     * Sets first repayment date for calculation (as a result of grace period).
     *
     * @param DateTime $firstRepaymentDate First repayment date for calculation (as a result of grace period).
     *
     * @throws FirstRepaymentExceedesScheduleException When that happens.
     * @return self
     */
    public function setFirstRepaymentDate(DateTime $firstRepaymentDate): self
    {

        // Throw.
        if ($firstRepaymentDate < $this->getStart() || $firstRepaymentDate > $this->getEnd()) {
            throw new FirstRepaymentExceedesScheduleException([
                $this->getStart()->format('Y-m-d'),
                $this->getEnd()->format('Y-m-d'),
                $firstRepaymentDate->format('Y-m-d'),
            ]);
        }

        // Save.
        $this->firstRepaymentDate = $firstRepaymentDate->setTime(0, 0, 0);

        return $this;
    }

    /**
     * Return first repayment date for calculation (as a result of grace period).
     *
     * @return null|DateTime
     */
    public function getFirstRepaymentDate(): ?DateTime
    {

        return $this->firstRepaymentDate;
    }

    /**
     * Sets if calculation has to be done on daily basis or not (default is daily).
     *
     * @param boolean $isCalcDaily If calculation has to be done on daily basis or not.
     *
     * @return self
     */
    public function setIsCalcDaily(bool $isCalcDaily): self
    {

        // Save param.
        $this->isCalcDaily = $isCalcDaily;

        return $this;
    }

    /**
     * Return if calculation has to be done on daily basis or not (default is daily).
     *
     * @return boolean
     */
    public function getIsCalcDaily(): bool
    {

        return $this->isCalcDaily;
    }

    /**
     * Add rate to be used for interesets calculations.
     *
     * @param DateTime $date Effectiveness of date.
     * @param float    $rate Rate to use (not less than 0.00).
     *
     * @throws NegativeRateException When negative rate is given.
     * @return self
     */
    public function addRate(DateTime $date, float $rate): self
    {

        // Throw.
        if ($rate < 0.0) {
            throw new NegativeRateException([ $date->format('Y-m-d'), $rate ]);
        }

        // Add rate.
        $this->rates[$date->format('Y-m-d')] = [
            'date' => $date,
            'rate' => $rate,
        ];

        // Sort.
        ksort($this->rates);

        return $this;
    }

    /**
     * Returns rate for given day.
     *
     * @param DateTime $date Date to check for.
     *
     * @return float
     */
    public function getRateAt(DateTime $date): float
    {

        // Lvd.
        $date = $date->format('Y-m-d');
        $rate = 0.0;

        // Return rate at the end of this date.
        foreach ($this->rates as $rateDate => $rateAtDate) {

            if ($rateDate <= $date) {
                $rate = $rateAtDate['rate'];
            } else {
                return $rate;
            }
        }

        return $rate;
    }

    /**
     * Return all rates and engagements inbetween of two dates.
     *
     * ## Return example
     * ```
     * [
     *     '2021-01-01' => [
     *         'annualRate' => double(0.01),
     *         'engagement' => double(100),
     *         'days' => int(14),
     *         'percentage' => double(0.45161290322581),
     *     ],
     *     '2021-01-15' => [
     *         'annualRate' => double(0.02),
     *         'engagement' => double(100),
     *         'days' => int(17),
     *         'percentage' => double(0.54838709677419),
     *     ],
     * ]
     * ```
     *
     * @param DateTime $firstDate First date of range.
     * @param DateTime $lastDate  Last date of range.
     *
     * @return array[]
     */
    public function getRatesAndEngagementsBetween(DateTime $firstDate, DateTime $lastDate): array
    {

        // Lvd.
        $ticks            = [];
        $days             = (int) $firstDate->diff($lastDate)->format('%a');
        $prevTick         = null;
        $crcs             = [];
        $sumOfPercentages = 0;

        // Scan.
        for ($day = 0; $day <= $days; ++$day) {

            // Get date of this day.
            $date = ( clone $firstDate )->add(new DateInterval('P' . $day . 'D'));

            // Get rate for this date.
            $ticks[$date->format('Y-m-d')]['annualRate'] = $this->getRateAt($date);
            $ticks[$date->format('Y-m-d')]['engagement'] = $this->getCapitalEngagementAt($date);
        }

        // Ignore unchanged.
        foreach ($ticks as $date => $tick) {

            $crc = md5(serialize($tick));

            if (isset($crcs[$crc]) === false) {
                $crcs[$crc] = 0;
            }
            ++$crcs[$crc];

            // Ignore if is identicall to previous.
            if ($prevTick === $tick) {
                unset($ticks[$date]);
            } else {
                // Add crc.
                $ticks[$date]['crc'] = $crc;
            }

            // Save what previous is.
            $prevTick = $tick;
        }

        // Calc percentage.
        foreach ($ticks as $date => $tick) {

            // Define.
            $ticks[$date]['days']       = $crcs[$tick['crc']];
            $ticks[$date]['percentage'] = ( $crcs[$tick['crc']] / ( $days + 1 ) );
            unset($ticks[$date]['crc']);

            // Sum test.
            $sumOfPercentages += $ticks[$date]['percentage'];
        }

        // Make sure percentage sum up to 100.
        // @codeCoverageIgnoreStart
        // It happens very rare - I was unable to find schedule example that gets into that hole.
        if ((float) $sumOfPercentages !== (float) 1) {

            // Calc diff.
            $diff = ( 1 - $sumOfPercentages );

            // Find last.
            $allDates = array_keys($ticks);
            $lastDate = array_pop($allDates);

            // Add to last tick.
            $ticks[$lastDate]['percentage'] += $diff;
        }

        // @codeCoverageIgnoreEnd
        // Continue CC from now on.
        return $ticks;
    }

    /**
     * Returns engagement for given day.
     *
     * @param DateTime $date Date to check for.
     *
     * @return float
     */
    public function getCapitalEngagementAt(DateTime $date): float
    {

        // Lvd.
        $date           = $date->format('Y-m-d');
        $lastEngagement = 0.0;

        // Return engagement at the end of this date.
        foreach ($this->engagements as $engagementDate => $engagementAmount) {

            // For this exact date.
            if ($engagementDate === $date) {
                return $engagementAmount;
            }

            // This is too old - return previous one.
            if ($engagementDate > $date) {
                return $lastEngagement;
            }

            // Save this engagement.
            $lastEngagement = $engagementAmount;
        }

        return $lastEngagement;
    }

    /**
     * Calcs engagement for every single day when payemnt or repayment occured.
     *
     * There was an extra check in here:
     * ```
     * // Balance can't be lower than zero.
     * if (round($balance, 2) < 0.0) {
     *     throw new RepaymentsAreGreaterThenPaymentsException([ (string) $balance ]);
     * }
     * ```
     * but it looks like the other parts of code are managing with this situation and final result is proper.
     *
     * @return self
     */
    protected function calcEngagements(): self
    {

        // Lvd.
        $engagements = [];
        $balance     = 0;

        // Clear.
        $this->engagements = [];

        // Get flows.
        foreach ($this->flows as $flow) {

            // Lvd.
            $date     = ( clone $flow->getDate() )->add(new DateInterval('P1D'))->format('Y-m-d');
            $balance += $flow->getBalance();

            // Add empty.
            if (isset($engagements[$date]) === false) {
                $engagements[$date] = 0.0;
            }

            // Save this.
            $engagements[$date] += $balance;
        }

        // Sort and save.
        ksort($engagements);
        $this->engagements = $engagements;

        return $this;
    }

    /**
     * Add amount (payment or repayment).
     *
     * @param DateTime $date        Date of payment/repayment.
     * @param float    $amount      Amount of payment/repayment.
     * @param boolean  $isRepayment Is this a repayment flow.
     * @param boolean  $overwrite   Optional, false. Set to true to *set* instead od *add*.
     *
     * @throws NegativePaymentException When negative payment amount is given
     *                                  (repayment can be negative - it is a correction).
     * @return self
     */
    public function addPayment(DateTime $date, float $amount, bool $isRepayment = false, bool $overwrite = false): self
    {

        // Fast track.
        if ($amount === 0.0) {
            return $this;
        }

        // Throw.
        if ($amount < 0.0 && $isRepayment === false) {
            throw new NegativePaymentException([ $date->format('Y-m-d'), (string) $amount ]);
        }

        // Lvd.
        if ($isRepayment === false) {
            $payment   = $amount;
            $repayment = 0.0;
        } else {
            $payment   = 0.0;
            $repayment = $amount;
        }

        // Read or create flow.
        if (( $flow = ( $this->flows[$date->format('Y-m-d')] ?? null ) ) === null) {

            // Get flow.
            $flow = new Flow($date);

            // Save it.
            $this->flows[$date->format('Y-m-d')] = $flow;
        }

        // Include amounts.
        if ($overwrite === false) {
            $flow->addPayment($payment);
            $flow->addRepayment($repayment);
        } else {
            $flow->setPayment($payment);
            $flow->setRepayment($repayment);
        }

        // Sort.
        ksort($this->flows);

        return $this;
    }

    /**
     * Adds repayment to schedule.
     *
     * @param DateTime $date      Date of repayment.
     * @param float    $amount    Amount of repayment.
     * @param boolean  $overwrite Optional, false. Set to true to *set* instead od *add*.
     *
     * @return self
     */
    public function addRepayment(DateTime $date, float $amount, bool $overwrite = false): self
    {

        return $this->addPayment($date, $amount, true, $overwrite);
    }

    /**
     * Calculates whole Schedule basing on object properties.
     *
     * @return self
     */
    public function calc(): self
    {

        // Calc engagements.
        $this->calcEngagements();

        // Lvd.
        $unitCalcs = [];

        foreach ($this->getInstallments()->getAll() as $installment) {
            $installment->calc();
        }

        return $this;
    }

    /**
     * Returns Schedule as string.
     *
     * @return string
     */
    public function toString(): string
    {

        // Lvd.
        $result       = PHP_EOL . PHP_EOL;
        $installments = $this->getInstallments();

        // Add header.
        $result .= 'Settings:' . PHP_EOL;
        $result .= '  - first day:   ' . $this->getStart()->format('Y-m-d') . PHP_EOL;
        $result .= '  - grace till:  ' . $this->getFirstRepaymentDate()->format('Y-m-d') . PHP_EOL;
        $result .= '  - last day:    ' . $this->getEnd()->format('Y-m-d') . PHP_EOL;
        $result .= '  - repayments:  ' . $this->repaymentsStyle . PHP_EOL;
        $result .= '  - daily calcs: ' . [ 'no', 'yes' ][(int) $this->getIsCalcDaily()] . PHP_EOL;
        $result .= '  - period type: ' . $this->getPeriodType()->getType() . PHP_EOL;

        // Add header.
        $result .= PHP_EOL . 'Payments:' . PHP_EOL;

        // Add every payment.
        foreach ($this->flows as $flow) {

            // Ignore empty flows.
            if ($flow->getPayment() === 0.0) {
                continue;
            }

            // Show.
            $result .= '  - ' . $flow->getDate()->format('Y-m-d') . ': ';
            $result .= str_pad(number_format($flow->getPayment(), 2, '.', '`'), 13, ' ', STR_PAD_LEFT) . PHP_EOL;
        }

        // Add header.
        $result .= PHP_EOL . 'Interests rates:' . PHP_EOL;

        // Add every payment.
        foreach ($this->rates as $rate) {
            $result .= '  - ' . $rate['date']->format('Y-m-d') . ': ';
            $result .= str_pad(number_format(( $rate['rate'] * 100 ), 2, '.', '`'), 5, ' ', STR_PAD_LEFT);
            $result .= '%' . PHP_EOL;
        }

        // Start.
        $result .= PHP_EOL;
        $result .= '|-----|---------|------------|------------|------|---------------|---------------|---------------|';
        $result .= PHP_EOL;
        $result .= '| no  | period  | start      |     end    | days |   interests   |    capital    |     whole     |';
        $result .= PHP_EOL;
        $result .= '|-----|---------|------------|------------|------|---------------|---------------|---------------|';
        $result .= PHP_EOL;

        // Add every calculation.
        foreach ($installments->getAll() as $installment) {

            // Lvd.
            $period = $installment->getPeriod();

            // Add lines.
            $result .= '| ';
            $result .= str_pad((string) $installment->getOrder(), 3, ' ', STR_PAD_LEFT) . ' | ';
            $result .= str_pad($period->getName(), 7, ' ', STR_PAD_RIGHT) . ' | ';
            $result .= $period->getFirstDay()->format('Y-m-d') . ' | ';
            $result .= $period->getLastDay()->format('Y-m-d') . ' | ';
            $result .= str_pad((string) $period->getLength(), 4, ' ', STR_PAD_LEFT) . ' | ';
            $result .= str_pad(number_format($installment->getInterests(), 2, '.', '`'), 13, ' ', STR_PAD_LEFT) . ' | ';
            $result .= str_pad(number_format($installment->getCapital(), 2, '.', '`'), 13, ' ', STR_PAD_LEFT) . ' | ';
            $result .= str_pad(number_format($installment->getWhole(), 2, '.', '`'), 13, ' ', STR_PAD_LEFT) . ' |';
            $result .= PHP_EOL;
        }

        // End.
        $result .= '|-----|---------|------------|------------|------|---------------|---------------|---------------|';
        $result .= PHP_EOL . PHP_EOL;
        $result .= 'Sum of:' . PHP_EOL;
        $result .= '  - capital:   ';
        $result .= str_pad(number_format($installments->getSumOfCapital(), 2, '.', '`'), 13, ' ', STR_PAD_LEFT);
        $result .= PHP_EOL;
        $result .= '  - interests: ';
        $result .= str_pad(number_format($installments->getSumOfInterests(), 2, '.', '`'), 13, ' ', STR_PAD_LEFT);
        $result .= PHP_EOL . PHP_EOL . PHP_EOL;

        return $result;
    }

    /**
     * Deliver Schedule as TXT file.
     *
     * @param null|string $fileName Name of the file - if not given file will not be saved.
     *
     * @return string
     */
    public function toTextFile(?string $fileName = null): string
    {

        // Lvd.
        $contents = $this->toString();

        // Create file and save.
        if ($fileName !== null) {
            file_put_contents($fileName, $contents);
        }

        return $contents;
    }

    /**
     * Deliver Schedule as CSV file.
     *
     * @param null|string $fileName Name of the file - if not given file will not be saved.
     *
     * @return string
     */
    public function toCsvFile(?string $fileName = null): string
    {

        // Lvd.
        $installments = $this->getInstallments();
        $contents     = 'number,period,start,stop,length,interests,capital,whole' . PHP_EOL;

        // Add every calculation.
        foreach ($installments->getAll() as $installment) {

            // Lvd.
            $period = $installment->getPeriod();

            // Add lines.
            $contents .= $installment->getOrder() . ',';
            $contents .= $period->getName() . ',';
            $contents .= $period->getFirstDay()->format('Y-m-d') . ',';
            $contents .= $period->getLastDay()->format('Y-m-d') . ',';
            $contents .= $period->getLength() . ',';
            $contents .= $installment->getInterests() . ',';
            $contents .= $installment->getCapital() . ',';
            $contents .= $installment->getWhole();
            $contents .= PHP_EOL;
        }

        // Create file and save.
        if ($fileName !== null) {
            file_put_contents($fileName, $contents);
        }

        return $contents;
    }
}
