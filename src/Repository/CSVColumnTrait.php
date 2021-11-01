<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   09 Jun 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

/**
 * for a column containing comma separated values
 * converts value,value,value to array and back again
 */
trait CSVColumnTrait
{
    /** @var string[] */
    private array $csvColumns = [];

    protected function beforeSaveCsvColumn(array $data): array
    {
        foreach ($this->csvColumns as $column) {
            if (isset($data[$column]) && is_array($data[$column])) {
                $data[$column] = join(',', $data[$column]);
            }
        }
        return $data;
    }

    protected function afterFindCsvColumn(array $data): array
    {
        assert(!empty($this->csvColumns), 'set csvColumns in the constructor');
        foreach ($this->csvColumns as $column) {
            if (isset($data[$column])) {
                if (empty($data[$column])) {
                    $data[$column] = [];
                } else {
                    $data[$column] = explode(',', $data[$column]);
                }
            }
        }
        return $data;
    }
}
