<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

use Latitude\QueryBuilder\ExpressionInterface;

trait DateCreatedFieldTrait
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
