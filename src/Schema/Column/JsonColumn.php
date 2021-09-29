<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   21 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema\Column;

class JsonColumn extends TextColumn
{
    public function __construct(string $name, string $description, string $columnType = 'json')
    {
        parent::__construct($name, $description, $columnType);
    }
}
