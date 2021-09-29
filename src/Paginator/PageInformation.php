<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   6 12 2016
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

/**
 * A data transfer object that holds information about the current state of a paginator.
 *
 * Some of these are redundant (eg firstPage is always 1, lastPage is always pageCount. But they are useful for nice
 * easy to read renderers: you can get exactly the value you want without having to calculate it from the others
 * and make any assumptions about how it works.
 *
 * The default values are set assuming there are no items to display (totalItemCount == 0).
 */
class PageInformation
{
    /** @const int */
    public const DEFAULT_ITEMS_PER_PAGE = 50;

    /** @const int */
    public const DEFAULT_PAGES_TO_SHOW = 10;

    /** @var int how many items fit on each page */
    public int $itemsPerPage = 0;

    /** @var int how many items are in the full unpaginated list */
    public int $totalItemCount = 0;

    /** @var int how many pages we need to show all the items */
    public int $pageCount = 0;

    /** @var int always 1 */
    public int $firstPage = 1;

    /** @var int (almost) always pageCount */
    public int $lastPage = 1;

    /** @var int 1..pageCount */
    public int $currentPage = 1;

    /** @var int|null 1..pageCount or null if there is no previous page eg we are at the first page */
    public ?int $previousPage = null;

    /** @var int|null 1..pageCount or null if there is no next page eg we are at the last page */
    public ?int $nextPage = null;

    /** @var int[] visible page numbers in the range 1..pageCount */
    public array $pageNumbers = [];

    /** @var int 1..pageCount first item of pageNumbers */
    public int $firstPageInRange = 1;

    /** @var int 1..pageCount last item of pageNumbers */
    public int $lastPageInRange = 1;

    /** @var int|null range is 0..(totalItemCount - 1) or null if no items */
    public ?int $firstItemNumber = null;

    /** @var int|null range is 0..(totalItemCount - 1) or null if no items */
    public ?int $lastItemNumber = null;

    /** @var int number of items on this page: will be < itemsPerPage on the last page */
    public int $currentItemCount = 0;

    /** @var int how big the sliding window is: anything above 3 looks good and works well */
    public int $maxPagesToShow = 1;

    /**
     * @var bool normal mode makes two queries: one for the number of records (pages), and one for the current page.
     * Fast Mode makes a query for the current page only: so we cannot show the total record count and cannot
     * go more than one page forward at a time. This is an acceptable tradeoff for some large reports because users
     * almost never want to do that, and the gain in speed makes them happy
     */
    public bool $useFastMode = false;

    /**
     * named constructor used when we want to show all items (no pagination)
     * @param int $totalItemCount
     * @return self
     */
    public static function showAllItems(int $totalItemCount): self
    {
        $self                   = new self();
        $self->itemsPerPage     = $totalItemCount;
        $self->totalItemCount   = $totalItemCount;
        $self->currentItemCount = $totalItemCount;
        $self->firstItemNumber  = 0;
        $self->lastItemNumber   = max(0, $totalItemCount - 1);
        $self->pageCount        = 1;
        $self->pageNumbers      = [1];
        return $self;
    }

    /**
     * named constructor for showing one page plus links to the other pages around it
     * @param int $currentPageNumber
     * @param int $totalItemCount
     * @param int $itemsPerPage
     * @param int $maxPagesToShow
     * @return self
     */
    public static function forPage(
        int $currentPageNumber,
        int $totalItemCount,
        int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
        int $maxPagesToShow = self::DEFAULT_PAGES_TO_SHOW
    ): self {
        if ($currentPageNumber <= 0) {
            return self::showAllItems($totalItemCount);
        }
        $self                 = new self();
        $self->itemsPerPage   = $itemsPerPage;
        $self->maxPagesToShow = $maxPagesToShow;
        $self->totalItemCount = $totalItemCount;
        if ($totalItemCount <= 0) {
            return $self;
        }
        $self->firstPage        = 1;
        $self->pageCount        = intval(ceil($totalItemCount / $self->itemsPerPage));
        $self->lastPage         = $self->pageCount;
        $self->currentPage      = max(1, min($currentPageNumber, $self->pageCount));
        $self->previousPage     = ($self->currentPage > 1) ? ($self->currentPage - 1) : null;
        $self->nextPage         = ($self->currentPage < $self->pageCount) ? $self->currentPage + 1 : null;
        $delta                  = intval(ceil($self->maxPagesToShow / 2));
        $self->firstPageInRange = max(
            $self->firstPage,
            min($self->currentPage - $delta + 1, $self->lastPage - $self->maxPagesToShow + 1)
        );
        $self->lastPageInRange  = min(
            $self->lastPage,
            $self->firstPageInRange + $self->maxPagesToShow - ($self->pageCount > 1 ? 1 : 0)
        );
        $self->pageNumbers      = range($self->firstPageInRange, $self->lastPageInRange);
        $self->firstItemNumber  = ($self->currentPage - 1) * $self->itemsPerPage;
        $self->currentItemCount = min($self->itemsPerPage, $totalItemCount - $self->firstItemNumber);
        $self->lastItemNumber   = max(0, $self->firstItemNumber + $self->currentItemCount - 1);
        return $self;
    }

    /**
     * named constructor for showing one page plus links to the other pages around it
     * @param int $currentPageNumber
     * @param int $itemsOnThisPage
     * @param int $itemsPerPage
     * @param int $maxPagesToShow
     * @return self
     */
    public static function forFastMode(
        int $currentPageNumber,
        int $itemsOnThisPage,
        int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
        int $maxPagesToShow = self::DEFAULT_PAGES_TO_SHOW
    ): self {
        assert($currentPageNumber > 0);
        assert($itemsOnThisPage >= 0);
        $totalItemCount = $itemsOnThisPage + $itemsPerPage * $currentPageNumber;
        if ($itemsOnThisPage === 0 && $currentPageNumber > 1) {
            // we guessed there might be more items, but the last page was full and the next is empty, so display the last page again
            $currentPageNumber--;
        } elseif ($itemsOnThisPage >= $itemsPerPage) {
            // guess that there is at least one more item on the next page if the current page is full
            $totalItemCount += 1;
        }
        $self              = self::forPage($currentPageNumber, $totalItemCount, $itemsPerPage, $maxPagesToShow);
        $self->useFastMode = true;
        return $self;
    }
}
