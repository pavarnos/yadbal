<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   30 Jun 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

use PHPUnit\Framework\TestCase;

class ArrayPaginatorTest extends TestCase
{
    public function testFromArrayEmpty(): void
    {
        $subject = ArrayPaginator::fromArray([], 0);
        self::assertEquals([], $subject->getItemsOnPage());
        self::assertEquals(0, $subject->getPageInformation()->totalItemCount);
    }

    public function testFromArray(): void
    {
        $subject = ArrayPaginator::fromArray($items = [1, 2, 3, 4, 5, 6], 1, 4);
        self::assertEquals([1, 2, 3, 4], $subject->getItemsOnPage());
        self::assertEquals(count($items), $subject->getPageInformation()->totalItemCount);
    }
}
