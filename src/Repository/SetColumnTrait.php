<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   09 Jun 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

/**
 * for MySQL SET columns
 * converts PHP ?array to the values compatible with MySQL
 * and back again
 */
trait SetColumnTrait
{
    /** @var string[] */
    private array $setColumns = [];

    protected function beforeSaveSetColumn(array $data): array
    {
        foreach ($this->setColumns as $column) {
            if (isset($data[$column])) {
                if (empty($data[$column])) {
                    $data[$column] = null;
                } elseif (is_array($data[$column])) {
                    $data[$column] = join(',', $data[$column]);
                }
            }
        }
        return $data;
    }

    protected function afterFindSetColumn(array $data): array
    {
        assert(!empty($this->setColumns), 'set setColumns in the constructor');
        foreach ($this->setColumns as $column) {
            if (isset($data[$column])) {
                if (is_null($data[$column])) {
                    $data[$column] = [];
                } elseif (!empty($data[$column])) {
                    $data[$column] = explode(',', $data[$column]);
                }
            }
        }
        return $data;
    }
}
