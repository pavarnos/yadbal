<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

trait DisplayOrderColumnTrait
{
    public function moveUp(int $id): void
    {
        $this->getRecordMover()->moveUp($id);
    }

    public function moveDown(int $id): void
    {
        $this->getRecordMover()->moveDown($id);
    }

    protected function beforeSaveDisplayOrder(array $data): array
    {
        if (empty($data['id'])) {
            $data[DisplayOrder::DISPLAY_ORDER_FIELD] = $this->getRecordMover()->getNextDisplayOrder($data);
        }
        return $data;
    }

    abstract protected function getRecordMover(): DisplayOrder;
}
