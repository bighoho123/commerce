<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftcommercetests\unit\stats;

use Craft;
use Codeception\Test\Unit;
use craft\commerce\stats\TotalOrders;
use craftcommercetests\fixtures\OrdersFixture;
use DateTime;
use UnitTester;

/**
 * TotalOrdersTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.2
 */
class TotalOrdersTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'orders' => [
                'class' => OrdersFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param string $dateRange
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int $total
     * @param int $daysDiff
     */
    public function testGetData(string $dateRange, DateTime $startDate, DateTime $endDate, int $total, int $daysDiff): void
    {
        $stat = new TotalOrders($dateRange, $startDate, $endDate);
        $data = $stat->get();

        self::assertIsArray($data);
        self::assertArrayHasKey('total', $data);
        self::assertEquals($total, $data['total']);
        self::assertArrayHasKey('chart', $data);
        self::assertIsArray($data['chart']);
        self::assertArrayHasKey($startDate->format('Y-m-d'), $data['chart']);
        self::assertArrayHasKey($endDate->format('Y-m-d'), $data['chart']);

        codecept_debug('date_default_timezone_get: '.date_default_timezone_get());
        codecept_debug("data['total']: ".$data['total']);
        codecept_debug('$daysDiff + 1: ' . ($daysDiff + 1));
        codecept_debug("count(data['chart']): ".count($data['chart']));
        foreach ($data['chart'] as $outerArrayKey => $outerArrayValue) {
            codecept_debug('$outerArrayKey: '.($outerArrayKey === '' ? '\'\'' : $outerArrayKey));
            foreach ($outerArrayValue as $arrayKey => $arrayValue) {
                codecept_debug('$arrayKey => $arrayValue: '.$arrayKey.' => '.(is_null($arrayValue) ? 'null' : $arrayValue));
            }
        }

        $results = \Craft::$app->getDb()->createCommand(
        'SELECT * FROM `commerce_orders` `orders` INNER JOIN `elements` `elements` ON `elements`.`id` = `orders`.`id` WHERE (`dateOrdered` >= \'2021-05-20 07:00:00\') AND (`dateOrdered` <= \'2021-05-21 06:59:59\') AND (`isCompleted`=1) AND (`elements`.`dateDeleted` IS NULL)'
        )->queryAll();

        codecept_debug('count($results): '.count($results));

        foreach ($results as $num => $result) {
            codecept_debug('row '.$num.': ');
            foreach ($result as $column => $value) {
                codecept_debug($column.': '.$value);
            }
        }

        self::assertCount($daysDiff + 1, $data['chart']);

        $firstItem = array_shift($data['chart']);
        self::assertArrayHasKey('total', $firstItem);
        self::assertArrayHasKey('datekey', $firstItem);
        self::assertEquals($startDate->format('Y-m-d'), $firstItem['datekey']);
        self::assertEquals($total, $firstItem['total']);
    }

    protected function _before()
    {
        Craft::$app->setTimeZone('America/Los_Angeles');
    }

    /**
     * @return array[]
     */
    public function getDataDataProvider(): array
    {
        return [
            [
                TotalOrders::DATE_RANGE_TODAY,
                (new DateTime('now', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0),
                (new DateTime('now', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0),
                2,
                (new DateTime('now', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0)
                    ->diff((new DateTime('now', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0))
                    ->days
            ],
            [
                TotalOrders::DATE_RANGE_CUSTOM,
                (new DateTime('7 days ago', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0),
                (new DateTime('5 days ago', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0),
                0,
                (new DateTime('5 days ago', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0)
                    ->diff((new DateTime('7 days ago', new \DateTimeZone('America/Los_Angeles')))->setTime(0, 0))
                    ->days
            ],
        ];
    }
}
