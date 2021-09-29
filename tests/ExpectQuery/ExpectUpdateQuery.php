<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   07 Jul 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\ExpectQuery;

class ExpectUpdateQuery extends ExpectQuery
{
    private int $affectedRows;

    public function __construct(string $sql, array $params, int $affectedRows = 0)
    {
        parent::__construct($sql, $params);
        $this->affectedRows = $affectedRows;
    }

    public function getResultInt(): int
    {
        return $this->affectedRows;
    }
}
