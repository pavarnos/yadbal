<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   30 Jun 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

use LSS\YADbal\AbstractRepository;
use LSS\YADbal\DatabaseConnection\SQLQueryFactoryTrait;
use PHPUnit\Framework\TestCase;

use function Latitude\QueryBuilder\field;

class LatitudePaginatorTest extends TestCase
{
    use SQLQueryFactoryTrait;

    public function testForShowAllEmpty(): void
    {
        $subject = LatitudePaginator::forShowAll([]);
        self::assertEquals([], $subject->getItemsOnPage());
        self::assertEquals(0, $subject->getPageInformation()->totalItemCount);
    }

    public function testForShowAll(): void
    {
        $subject = LatitudePaginator::forShowAll($items = [1, 2, 3, 4, 5, 6]);
        self::assertEquals($items, $subject->getItemsOnPage());
        self::assertEquals(count($items), $subject->getPageInformation()->totalItemCount);
    }

    public function testForPageEmpty(): void
    {
        $subject = LatitudePaginator::forPage([], $pageNumber = 1, $pageSize = 20, 0);
        self::assertEquals([], $subject->getItemsOnPage());
        self::assertEquals(0, $subject->getPageInformation()->totalItemCount);
        self::assertEquals($pageSize, $subject->getPageInformation()->itemsPerPage);
        self::assertEquals($pageNumber, $subject->getPageInformation()->currentPage);
    }

    public function testForPage(): void
    {
        $subject = LatitudePaginator::forPage($items = [1, 2, 3, 4], $pageNumber = 3, $pageSize = 4, $itemCount = 45);
        self::assertEquals($items, $subject->getItemsOnPage());
        self::assertEquals($itemCount, $subject->getPageInformation()->totalItemCount);
        self::assertEquals($pageSize, $subject->getPageInformation()->itemsPerPage);
        self::assertEquals($pageNumber, $subject->getPageInformation()->currentPage);
    }

    public function testGetPageSelect(): void
    {
        $select  = $this->select()->from('foo')->where(field('a')->eq($param = 9));
        $subject = LatitudePaginator::getPageSelect($select, $pageNumber = 5, $pageSize = 10)->compile();
        self::assertEquals('SELECT * FROM `foo` WHERE `a` = ? LIMIT 10 OFFSET 40', $subject->sql());
        self::assertEquals([$param], $subject->params());
    }

    public function testGetCountSelectPlain(): void
    {
        $select  = $this->select()->from('foo')->where(field('a')->eq($param = 9));
        $subject = LatitudePaginator::getCountSelect($select, $this->createMock(AbstractRepository::class))->compile();
        self::assertEquals('SELECT count(*) AS `paginator_row_count` FROM `foo` WHERE `a` = ?', $subject->sql());
        self::assertEquals([$param], $subject->params());
    }

    public function testGetCountSelectGrouped(): void
    {
        $select     = $this->select()->from('foo')->where(field('a')->eq($param = 9))->groupBy('b');
        $repository = $this->createMock(AbstractRepository::class);
        $repository->expects(self::once())->method('selectAll')->willReturn($this->select());
        $subject = LatitudePaginator::getCountSelect($select, $repository)->compile();
        self::assertEquals(
            'SELECT * FROM (SELECT * FROM `foo` WHERE `a` = ? GROUP BY `b`) AS `toBeCounted`',
            $subject->sql()
        );
        self::assertEquals([$param], $subject->params());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupQueryFactory();
    }
}
