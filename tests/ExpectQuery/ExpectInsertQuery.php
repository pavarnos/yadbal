<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   07 Jul 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\ExpectQuery;

class ExpectInsertQuery extends ExpectQuery
{
    private int $insertId;

    public function __construct(string $sql, array $params, int $insertId = 0)
    {
        parent::__construct($sql, $params);
        $this->insertId = $insertId;
    }

    public function getResultInt(): int
    {
        return $this->insertId;
    }
}
