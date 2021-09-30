<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository\Example;

use LSS\YADbal\AbstractChildRepository;
use LSS\YADbal\Repository\ExampleRepository;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use LSS\YADbal\Schema\Table;
use LSS\YADbal\Schema\TableBuilder;

class ChildRepository extends AbstractChildRepository
{
    public const TABLE_NAME = 'example_child';

    public function getSchema(): Table
    {
        return (new TableBuilder(self::TABLE_NAME, ''))
            ->addPrimaryKeyColumn()
            ->addForeignKeyColumn(ExampleRepository::TABLE_NAME, '', '', ForeignKeyColumn::ACTION_CASCADE)
            ->addStringColumn('value', 2)
            ->build();
    }
}
