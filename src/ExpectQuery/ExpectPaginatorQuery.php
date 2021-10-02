<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   07 Jul 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\ExpectQuery;

class ExpectPaginatorQuery extends ExpectQuery
{

    public function __construct(
        string $sql,
        array $params,
        private int $pageCount = 0,
        private string $columnName = 'paginator_row_count'
    ) {
        parent::__construct($sql, $params);
    }

    public function getResultInt(): int
    {
        return $this->pageCount;
    }

    public function getResultArray(): array
    {
        return [$this->columnName => $this->pageCount];
    }
}
