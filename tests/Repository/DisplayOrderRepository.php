<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

use LSS\YADbal\AbstractRepository;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use LSS\YADbal\Schema\Table;
use LSS\YADbal\Schema\TableBuilder;

/**
 * wanted to use an anonymous class byt there was no syntax that allowed us to specify the correct return type
 * that both phpstan and phpstorm would accept
 */
class DisplayOrderRepository extends AbstractRepository
{
    use DisplayOrderColumnTrait;

    public const TABLE_NAME = DisplayOrderTest::TABLE_NAME;

    public function getSchema(): Table
    {
        return (new TableBuilder(self::TABLE_NAME, 'A training activity for the person to do'))
            ->addPrimaryKeyColumn()
            ->addForeignKeyColumn('person', '', '', ForeignKeyColumn::ACTION_CASCADE)
            ->addDateColumn('date_due', 'Date when it should be completed')
            ->addDisplayOrderColumn()
            ->addStringColumn('value', 2)
            ->addStandardIndex('person_date', ['person_id', 'date_due', DisplayOrder::DISPLAY_ORDER_FIELD])
            ->build();
    }

    protected function getRecordMover(): DisplayOrder
    {
        return new DisplayOrder($this, ['person_id', 'date_due']);
    }
}
