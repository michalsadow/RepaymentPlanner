<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;
use PHPUnit\Framework\TestCase;
use Przeslijmi\RepaymentPlanner\Exceptions\FirstRepaymentExceedesScheduleException;
use Przeslijmi\RepaymentPlanner\Exceptions\InstallmentDonoexException;
use Przeslijmi\RepaymentPlanner\Exceptions\NegativePaymentException;
use Przeslijmi\RepaymentPlanner\Exceptions\NegativeRateException;
use Przeslijmi\RepaymentPlanner\Exceptions\StartIsLaterOrEqualToEndException;
use Przeslijmi\RepaymentPlanner\Exceptions\UnknownPeriodTypeException;
use Przeslijmi\RepaymentPlanner\Period;
use Przeslijmi\RepaymentPlanner\Schedule;

/**
 * Methods for testing Schedule tool.
 */
final class ScheduleTest extends TestCase
{

    /**
     * Deliver various algorithms to use in planning.
     *
     * @return array[]
     */
    public function algorithmsProvider(): array
    {

        return [
            [ 'linear', 'setRepaymentsLinearStyle', [], ],
            [ 'baloon', 'setRepaymentsBalloonStyle', [], ],
            [ 'annuit', 'setRepaymentsAnnuitStyle', [], ],
            [ 'annuitZero', 'setRepaymentsAnnuitStyle', [ 0 ], ],
        ];
    }

    /**
     * Test repayment schedule with no-daily calculations.
     *
     * @param string $algorithm Name of algorithm to test.
     * @param string $method    Name of Schedule method to use to define that algorithm.
     * @param array  $params    Params to use to define that algorithm.
     *
     * @return void
     *
     * @dataProvider algorithmsProvider
     */
    public function testNoDailySheduleString(string $algorithm, string $method, array $params): void
    {

        // Prepare schedule.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->setFirstRepaymentDate(new DateTime('2020-02-01'));
        $sch->addRate(new DateTime('2020-06-01'), 0.08);
        $sch->addPayment(new DateTime('2020-02-01'), 0.0);

        // Last step - and calculations.
        $sch->$method(...$params);
        $sch->calc();

        // Get values.
        $actual   = trim(str_replace("\r\n", "\n", $sch->toString()));
        $expected = trim(str_replace("\r\n", "\n", $this->getContents($algorithm . 'ToString', 'txt')));

        // Test.
        $this->assertEquals($expected, $actual);
        $this->assertFalse($sch->getIsCalcDaily());
        $this->assertEquals(0.0, round($sch->getCapitalEngagementAt(new DateTime('2030-05-31')), 2));
    }

    /**
     * Test repayment schedule with daily calculations.
     *
     * @param string $algorithm Name of algorithm to test.
     * @param string $method    Name of Schedule method to use to define that algorithm.
     * @param array  $params    Params to use to define that algorithm.
     *
     * @return void
     *
     * @dataProvider algorithmsProvider
     */
    public function testDailySheduleString(string $algorithm, string $method, array $params): void
    {

        // Prepare schedule.
        $sch = new Schedule(500, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->setFirstRepaymentDate(new DateTime('2020-02-01'));
        $sch->setIsCalcDaily(true);
        $sch->addRate(new DateTime('2020-06-01'), 0.08);

        // This is important - it adds 600 to previous 500, but than overwrites whole day with one amount 1000.
        // Expected amount is 1000 - so here overwriting is also tested.
        $sch->addPayment(new DateTime('2020-01-01'), 600);
        $sch->addPayment(new DateTime('2020-01-01'), 1000, false, true);

        // Last step - and calculations.
        $sch->$method(...$params);
        $sch->calc();

        // Get values.
        $actual   = trim(str_replace("\r\n", "\n", $sch->toString()));
        $expected = trim(str_replace("\r\n", "\n", $this->getContents($algorithm . 'DailyToString', 'txt')));

        // Test.
        $this->assertEquals($expected, $actual);
        $this->assertTrue($sch->getIsCalcDaily());
        $this->assertEquals(0.0, round($sch->getCapitalEngagementAt(new DateTime('2030-05-31')), 2));
    }

    /**
     * Test quarterly repayment schedule exported to CSV.
     *
     * @param string $algorithm Name of algorithm to test.
     * @param string $method    Name of Schedule method to use to define that algorithm.
     * @param array  $params    Params to use to define that algorithm.
     *
     * @return void
     *
     * @dataProvider algorithmsProvider
     */
    public function testQuarterlySheduleCsv(string $algorithm, string $method, array $params): void
    {

        // Prepare schedule.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2021-12-31'), 'quarterly');
        $sch->setFirstRepaymentDate(new DateTime('2020-02-01'));
        $sch->addRate(new DateTime('2020-06-01'), 0.08);

        // Last step - and calculations.
        $sch->$method(...$params);
        $sch->calc();
        $sch->toCsvFile($this->getUriForActuals('.' . $algorithm . 'ToCsv', 'csv'));

        // Get values.
        $actual   = trim(str_replace("\r\n", "\n", $this->getContents('.' . $algorithm . 'ToCsv', 'csv')));
        $expected = trim(str_replace("\r\n", "\n", $this->getContents($algorithm . 'ToCsv', 'csv')));

        // Test.
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test yearly repayment schedule exported to TXT.
     *
     * @param string $algorithm Name of algorithm to test.
     * @param string $method    Name of Schedule method to use to define that algorithm.
     * @param array  $params    Params to use to define that algorithm.
     *
     * @return void
     *
     * @dataProvider algorithmsProvider
     */
    public function testYearlySheduleTxt(string $algorithm, string $method, array $params): void
    {

        // Prepare schedule.
        $sch = new Schedule(2888.88, 0.045, new DateTime('2020-01-01'), new DateTime('2025-12-31'), 'yearly');
        $sch->setFirstRepaymentDate(new DateTime('2020-02-01'));

        // Last step - and calculations.
        $sch->$method(...$params);
        $sch->calc();
        $sch->toTextFile($this->getUriForActuals('.' . $algorithm . 'ToText', 'txt'));

        // Get values.
        $actual   = trim(str_replace("\r\n", "\n", $this->getContents('.' . $algorithm . 'ToText', 'txt')));
        $expected = trim(str_replace("\r\n", "\n", $this->getContents($algorithm . 'ToText', 'txt')));

        // Test.
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test if too lat start date will throw.
     *
     * @return void
     */
    public function testIfTooLateStartDateThrows(): void
    {

        // Prepare.
        $this->expectException(StartIsLaterOrEqualToEndException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->setStart(new DateTime('2021-01-01'));
    }

    /**
     * Test if too early end dat wii throw.
     *
     * @return void
     */
    public function testIfTooEarlyEndDateThrows(): void
    {

        // Prepare.
        $this->expectException(StartIsLaterOrEqualToEndException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->setEnd(new DateTime('2018-01-01'));
    }

    /**
     * Test if equal start and end date will throw.
     *
     * @return void
     */
    public function testIfEqualEndAndStartDateThrows(): void
    {

        // Prepare.
        $this->expectException(StartIsLaterOrEqualToEndException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-01-01'), 'monthly');
    }

    /**
     * Test if first repayment set as before schedule starts will throw.
     *
     * @return void
     */
    public function testIfFirstRepaymentBeforeScheduleStartsThrows(): void
    {

        // Prepare.
        $this->expectException(FirstRepaymentExceedesScheduleException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->setFirstRepaymentDate(new DateTime('2019-02-01'));
    }

    /**
     * Test if first repayment set as after schedule ends will throw.
     *
     * @return void
     */
    public function testIfFirstRepaymentAfterScheduleEndsThrows(): void
    {

        // Prepare.
        $this->expectException(FirstRepaymentExceedesScheduleException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->setFirstRepaymentDate(new DateTime('2021-01-01'));
    }

    /**
     * Test if giving negative rate will throw.
     *
     * @return void
     */
    public function testIfNegativeRateThrows(): void
    {

        // Prepare.
        $this->expectException(NegativeRateException::class);

        // Test.
        $sch = new Schedule(1000, -0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
    }

    /**
     * Test if giving negative payment will throw.
     *
     * @return void
     */
    public function testIfNegativePaymentThrows(): void
    {

        // Prepare.
        $this->expectException(NegativePaymentException::class);

        // Test.
        $sch = new Schedule(-1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
    }

    /**
     * Test if giving unknown period type throw.
     *
     * @return void
     */
    public function testIfWrongPeriodTypeThrows(): void
    {

        // Prepare.
        $this->expectException(UnknownPeriodTypeException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2021-12-31'), 'whooaaaaa');
    }

    /**
     * Test if asking for nonexisting Installement will throw.
     *
     * @return void
     */
    public function testIfNonexistingInstallementThrows(): void
    {

        // Prepare.
        $this->expectException(InstallmentDonoexException::class);

        // Test.
        $sch = new Schedule(1000, 0.05, new DateTime('2020-01-01'), new DateTime('2020-12-31'), 'monthly');
        $sch->getInstallments()->getInstallmentForDate(new DateTime('2019-01-01'));
    }

    /**
     * Deliver contents of schedule for given scenario (from resources/forTesting/).
     *
     * @param string $scenario Name of scenario (ie. `txt` file from `resources/forTesting/` dir).
     * @param string $format   Format of scenario (txt, json, csv).
     *
     * @return string
     */
    private function getContents(string $scenario, string $format = 'txt'): string
    {

        return file_get_contents(( dirname(dirname(__FILE__)) . '/resources/forTesting/' . $scenario . '.' . $format ));
    }

    /**
     * Get uri where actual result of test was generated.
     *
     * @param string $scenario Name of scenario, eg. `balloon`.
     * @param string $format   Format of scenario (txt, json, csv).
     *
     * @return string
     */
    private function getUriForActuals(string $scenario, string $format = 'txt'): string
    {

        return dirname(dirname(__FILE__)) . '/resources/forTesting/' . $scenario . '.' . $format;
    }
}
