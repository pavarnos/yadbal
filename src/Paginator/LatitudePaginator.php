<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

use Latitude\QueryBuilder\Query\SelectQuery;
use LSS\YADbal\AbstractRepository;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\express;
use function Latitude\QueryBuilder\func;

/**
 * Paginate a Latitude SqlQuery
 */
class LatitudePaginator extends AbstractPaginator
{
    public const PAGINATOR_ROW_COUNT_COLUMN = 'paginator_row_count';

    /**
     * @param PageInformation $pages
     * @param array           $rows the current page of data from the database
     */
    public function __construct(PageInformation $pages, private array $rows)
    {
        parent::__construct($pages);
    }

    public static function forShowAll(array $rows): self
    {
        return new self(PageInformation::showAllItems(count($rows)), $rows);
    }

    public static function forFastMode(array $rows, int $currentPageNumber, int $pageSize): self
    {
        return new self(PageInformation::forFastMode($currentPageNumber, count($rows), $pageSize), $rows);
    }

    public static function forPage(array $rows, int $currentPageNumber, int $pageSize, int $totalItemCount): self
    {
        return new self(PageInformation::forPage($currentPageNumber, $totalItemCount, $pageSize), $rows);
    }

    public static function getPageSelect(SelectQuery $select, int $pageNumber, int $pageSize): SelectQuery
    {
        assert($pageNumber > 0);
        return $select->limit($pageSize)->offset(($pageNumber - 1) * $pageSize);
    }

    public static function getCountSelect(SelectQuery $select, AbstractRepository $repository): SelectQuery
    {
        $countSelect = clone($select);
        $countSelect->limit(null)->offset(null)->orderBy(null);
        $counter = alias(func('count', '*'), self::PAGINATOR_ROW_COUNT_COLUMN);
        if (self::needsSubSelect($select)) {
            return $repository->selectAll($counter)
                              ->from(alias(express('(%s)', $countSelect), 'toBeCounted'));
            // weird way of doing a sub select: see https://github.com/shadowhand/latitude/issues/37
        }
        return $countSelect->columns($counter);
    }

    /**
     * @param SelectQuery $select
     * @return bool true if we need to wrap the query in a sub select so our count(*) returns the correct value
     */
    private static function needsSubSelect(SelectQuery $select): bool
    {
        $sql = $select->compile()->sql();
        if (stripos($sql, 'union') !== false
            || stripos($sql, 'distinct') !== false
            || stripos($sql, 'group by') !== false) {
            return true;
        }
        return false;
    }

    public function getItemsOnPage(): array
    {
        return $this->rows;
    }
}
