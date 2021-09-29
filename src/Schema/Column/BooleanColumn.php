<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

class BooleanColumn extends IntegerColumn
{
    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, '0', 'tinyint');
    }
}
