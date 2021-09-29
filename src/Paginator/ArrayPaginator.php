<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   6 12 2016
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

/**
 * Paginate an array
 */
class ArrayPaginator extends AbstractPaginator
{
    /** @var array the items to paginate: the full data set */
    private array $items = [];

    private function __construct()
    {
    }

    public static function fromArray(
        array $items,
        int $currentPageNumber,
        int $itemsPerPage = PageInformation::DEFAULT_ITEMS_PER_PAGE
    ): self {
        $result        = new self();
        $result->items = $items;
        $result->pages = PageInformation::forPage($currentPageNumber, count($items), $itemsPerPage);
        return $result;
    }

    public function getItemsOnPage(): array
    {
        if (empty($this->items)) {
            return [];
        }
        return array_slice($this->items, $this->pages->firstItemNumber ?? 0, $this->pages->itemsPerPage, true);
    }
}
