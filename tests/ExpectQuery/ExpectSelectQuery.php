<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   07 Jul 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\ExpectQuery;

class ExpectSelectQuery extends ExpectQuery
{
    private array $result;

    public function __construct(string $sql, array $params = [], array $result = [], string $name = '')
    {
        parent::__construct($sql, $params, $name);
        $this->result = $result;
    }

    public function getResultArray(): array
    {
        return $this->result;
    }
}
