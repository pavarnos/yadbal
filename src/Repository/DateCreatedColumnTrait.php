<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

use Latitude\QueryBuilder\ExpressionInterface;

/**
 * Use this in association with TableBuilder->addDateCreatedColumn()
 * to automatically set date updated on first save of a new row
 */
trait DateCreatedColumnTrait
{
    protected function beforeSaveDateCreated(array $data): array
    {
        if (empty($data['id']) && !isset($data['date_created'])) {
            $data['date_created'] = $this->now();
        }
        return $data;
    }

    abstract public function now(): ExpressionInterface;
}
