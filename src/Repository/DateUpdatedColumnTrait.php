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
 * Use this in association with TableBuilder->addDateUpdatedColumn()
 * to automatically set date updated on each save
 */
trait DateUpdatedColumnTrait
{
    protected function beforeSaveDateUpdated(array $data): array
    {
        if (!isset($data['date_updated'])) {
            $data['date_updated'] = $this->now();
        }
        return $data;
    }

    abstract public function now(): ExpressionInterface;
}
