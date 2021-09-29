<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

use LSS\YADbal\AbstractRepository;

use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;

/**
 * when it takes multiple columns to define a group within which a display order must hold
 * eg person_training has (person_id, date_due) as one group within which rows must be ordered
 */
class DisplayOrder
{
    /** @const int default gap between rows in display_order value */
    public const DEFAULT_INCREMENT = 1;

    /** @const string default name for the field */
    public const DISPLAY_ORDER_FIELD = 'display_order';

    /**
     * @param AbstractRepository $repository
     * @param string[]           $groupByFields
     */
    public function __construct(private AbstractRepository $repository, private array $groupByFields)
    {
    }

    public function getNextDisplayOrder(array $data): int
    {
        // find the biggest value in the current group, return the next value up in sequence
        $select = $this->repository->selectAll(func('max', self::DISPLAY_ORDER_FIELD));
        foreach ($this->groupByFields as $field) {
            $select->andWhere(field($field)->eq($data[$field]));
        }
        return $this->repository->fetchInt($select) + self::DEFAULT_INCREMENT;
    }

    public function moveUp(int $id): void
    {
        $target = $this->repository->findOrException($id);
        $order  = $this->getOrderOfRowsInGroup($target);
        $this->moveTargetUp($id, $this->getGroupByValues($target), $order);
    }

    public function moveDown(int $id): void
    {
        $target = $this->repository->findOrException($id);
        $order  = $this->getOrderOfRowsInGroup($target);
        $this->moveTargetDown($id, $this->getGroupByValues($target), $order);
    }

    private function getOrderOfRowsInGroup(array $target): array
    {
        $select = $this->repository->selectAll('id', self::DISPLAY_ORDER_FIELD);
        foreach ($this->groupByFields as $field) {
            $select->andWhere(field($field)->eq($target[$field]));
            $select->orderBy($field); // so we can use the compound index
        }
        $select->orderBy(self::DISPLAY_ORDER_FIELD);
        $order = $this->repository->fetchPairs($select);
        if ($this->needsRenumber($order)) {
            return $this->renumber($order);
        }
        return $order;
    }

    private function moveTargetUp(int $id, array $fixed, array $order): void
    {
        $keys  = array_keys($order);
        $index = array_search($id, $keys, true);
        assert($index !== false); // for phpstan: we know the index is in the array
        if ($index === 0) {
            // already at the top: cannot move up
            return;
        }
        // swap the display orders for the two records
        $prev = $keys[$index - 1];
        $this->saveRow($id, $order[$prev], $fixed);
        $this->saveRow($prev, $order[$id], $fixed);
    }

    private function moveTargetDown(int $id, array $fixed, array $order): void
    {
        $keys  = array_keys($order);
        $index = array_search($id, $keys, true);
        assert($index !== false); // for phpstan: we know the index is in the array
        if ($index >= count($order) - 1) {
            // already at the bottom: cannot move down
            return;
        }
        // swap the display orders for the two records
        $next = $keys[$index + 1];
        $this->saveRow($id, $order[$next], $fixed);
        $this->saveRow($next, $order[$id], $fixed);
    }

    private function saveRow(int $id, string $order, array $fixed): void
    {
        $this->repository->save(['id' => $id, self::DISPLAY_ORDER_FIELD => $order] + $fixed);
    }

    private function needsRenumber(array $order): bool
    {
        // if the numbers do not increase, then we need to renumber
        $prev = self::DEFAULT_INCREMENT - 1;
        foreach ($order as $value) {
            if ($value <= $prev) {
                return true;
            }
            $prev = $value;
        }
        return false;
    }

    private function renumber(array $currentOrder): array
    {
        // calculate new order
        $newOrder = \Safe\array_combine(
            array_keys($currentOrder),
            range(self::DEFAULT_INCREMENT, count($currentOrder), self::DEFAULT_INCREMENT)
        );
        // save new order to database
        foreach ($newOrder as $id => $displayOrder) {
            if ($currentOrder[$id] != $displayOrder) {
                $this->repository->save(['id' => $id, self::DISPLAY_ORDER_FIELD => $displayOrder]);
            }
        }
        return $newOrder;
    }

    private function getGroupByValues(array $target): array
    {
        // the field values that stay constant in the group:
        // can be parent columns of child tables, so are needed for save()
        $fixed = [];
        foreach ($this->groupByFields as $field) {
            $fixed[$field] = $target[$field];
        }
        return $fixed;
    }
}
