<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   06 Jan 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

trait JsonColumnTrait
{
    /** @var string[]: set this in the constructor */
    private array $jsonColumns = [];

    /**
     * convert array values to json encoded strings for the selected columns
     * @param array $data
     * @return array
     */
    protected function beforeSaveJsonColumns(array $data): array
    {
        assert(!empty($this->jsonColumns), 'set jsonColumns in the constructor');
        foreach ($this->jsonColumns as $column) {
            if (isset($data[$column]) && is_array($data[$column])) {
                $data[$column] = \Safe\json_encode($data[$column]);
            }
        }
        return $data;
    }

    protected function afterFindJsonColumns(array $data): array
    {
        assert(!empty($this->jsonColumns), 'set jsonColumns in the constructor');
        foreach ($this->jsonColumns as $column) {
            if (!empty($data[$column])) {
                $data[$column] = \Safe\json_decode($data[$column], true);
            }
        }
        return $data;
    }
}
