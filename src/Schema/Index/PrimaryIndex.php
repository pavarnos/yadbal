<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Index;

use LSS\YADbal\Schema\Index;

class PrimaryIndex extends Index
{
    protected string $indexType = 'PRIMARY KEY';

    public function __construct(string $column = 'id')
    {
        parent::__construct('', $column);
    }

    public function toSQLite(): string
    {
        return '';
    }
}
