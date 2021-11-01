<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

use LSS\YADbal\AbstractRepository;
use LSS\YADbal\DatabaseConnectionInterface;
use LSS\YADbal\Schema\Table;
use LSS\YADbal\Schema\TableBuilder;

use function Latitude\QueryBuilder\field;

class ExampleRepository extends AbstractRepository
{
    use DateCreatedColumnTrait, DateUpdatedColumnTrait, JsonColumnTrait, CSVColumnTrait, SetColumnTrait;

    public const TABLE_NAME = 'example';

    public function __construct(DatabaseConnectionInterface $database)
    {
        parent::__construct($database);
        $this->jsonColumns[] = 'value_json';
        $this->csvColumns[] = 'value_csv';
        $this->setColumns[] = 'value_set';
    }

    public function getSchema(): Table
    {
        return (new TableBuilder(self::TABLE_NAME, ''))
            ->addPrimaryKeyColumn()
            ->addStringColumn('value', 2)
            ->addIntegerColumn('value_int')
            ->addStringColumn('value_csv', 100)
            ->addSetColumn('value_set', ['a','b','c'])
            ->addJsonColumn('value_json')
            ->addDateUpdatedColumn()
            ->addDateCreatedColumn()
            ->build();
    }

    public function getValue(int $id): string
    {
        // to test fetchInt
        $select = $this->selectAll('value')->where(field('id')->eq($id));
        return $this->fetchString($select);
    }

    public function getValues(array $ids): array
    {
        // to test fetchColumn
        $select = $this->selectAll('value')->where(field('id')->in(...$ids))->orderBy('value');
        return $this->fetchColumn($select, 'value');
    }
}
