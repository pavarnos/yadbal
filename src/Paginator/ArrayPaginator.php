<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   6 12 2016
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

/**
 * Paginate an array. Pass in the full data set as $items. The paginator will return slices
 */
class ArrayPaginator extends AbstractPaginator
{
    /**
     * @param array $items the items to paginate: the full data set
     * @param int   $currentPageNumber
     * @param int   $itemsPerPage
     */
    public function __construct(
        private array $items,
        int $currentPageNumber,
        int $itemsPerPage = PageInformation::DEFAULT_ITEMS_PER_PAGE
    ) {
        parent::__construct(PageInformation::forPage($currentPageNumber, count($items), $itemsPerPage));
    }

    public function getItemsOnPage(): array
    {
        if (empty($this->items)) {
            return [];
        }
        return array_slice($this->items, $this->pages->firstItemNumber ?? 0, $this->pages->itemsPerPage, true);
    }
}
