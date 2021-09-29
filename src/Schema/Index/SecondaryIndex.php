<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Index;

use LSS\YADbal\Schema\Index;

class SecondaryIndex extends Index
{
    public function __construct(string $name, string $firstColumn = '', bool $isUnique = false)
    {
        parent::__construct($name, $firstColumn);
        $this->indexType = $isUnique ? 'UNIQUE KEY' : $this->indexType;
    }
}
