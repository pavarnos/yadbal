<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   30 Jun 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

use PHPUnit\Framework\TestCase;

class PageInformationTest extends TestCase
{
    private const ITEMS_PER_PAGE = 10;
    private const PAGES_TO_SHOW  = 5;

    public function testShowAllEmpty(): void
    {
        $pages = PageInformation::showAllItems(0);
        self::assertEquals(0, $pages->itemsPerPage);
        self::assertEquals(1, $pages->maxPagesToShow);
        self::assertEquals(0, $pages->totalItemCount);
        self::assertEquals(0, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(null, $pages->firstItemNumber);
        self::assertEquals(null, $pages->lastItemNumber);

        // forPage() with no items is same as showAll()
        self::assertEquals($pages, PageInformation::forPage(0, 0));
    }

    public function testOneItem(): void
    {
        $pages = PageInformation::forPage(1, 1, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(1, $pages->totalItemCount);
        self::assertEquals(1, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(0, $pages->lastItemNumber);
    }

    public function testTwoItems(): void
    {
        $pages = PageInformation::forPage(1, 2, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(2, $pages->totalItemCount);
        self::assertEquals(2, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(1, $pages->lastItemNumber);
    }

    public function testOnePage(): void
    {
        $pages = PageInformation::forPage(1, self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE - 1, $pages->lastItemNumber);
    }

    public function testOneAndAHalfPages(): void
    {
        $itemCount = intval(ceil(1.5 * self::ITEMS_PER_PAGE));
        $pages     = PageInformation::forPage(1, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(2, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(2, $pages->lastPage);
        self::assertEquals(2, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(2, $pages->nextPage);
        self::assertEquals([1, 2], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE - 1, $pages->lastItemNumber);
    }

    public function test107Page1(): void
    {
        $itemCount = 107;
        $pages     = PageInformation::forPage(1, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(2, $pages->nextPage);
        self::assertEquals(range(1, self::PAGES_TO_SHOW), $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE - 1, $pages->lastItemNumber);
    }

    public function test107Page2(): void
    {
        $pageNumber = 2;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals($pageNumber - 1, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals($pageNumber + 1, $pages->nextPage);
        self::assertEquals(range(1, self::PAGES_TO_SHOW), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE * $pageNumber - 1, $pages->lastItemNumber);
    }

    public function test107Page3(): void
    {
        // should still be anchored left: 1 2 3 4 5
        $pageNumber = 3;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals($pageNumber + 1, $pages->nextPage);
        self::assertEquals(range(1, self::PAGES_TO_SHOW), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE * ($pageNumber - 1), $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE * $pageNumber - 1, $pages->lastItemNumber);
    }

    public function test107Page4(): void
    {
        // sliding away from the left now:  2 3 4 5 6
        $pageNumber = 4;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(2, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(6, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals($pageNumber + 1, $pages->nextPage);
        self::assertEquals(range(2, 6), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE * ($pageNumber - 1), $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE * $pageNumber - 1, $pages->lastItemNumber);
    }

    public function test107Page5(): void
    {
        // sliding away from the left now:  3 4 5 6 7
        $pageNumber = 5;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(3, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(7, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals($pageNumber + 1, $pages->nextPage);
        self::assertEquals(range(3, 7), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE * ($pageNumber - 1), $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE * $pageNumber - 1, $pages->lastItemNumber);
    }

    public function test107Page6(): void
    {
        // sliding away from the left now:  4 5 6 7 8
        $pageNumber = 6;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(4, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(8, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals($pageNumber + 1, $pages->nextPage);
        self::assertEquals(range(4, 8), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE * ($pageNumber - 1), $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE * $pageNumber - 1, $pages->lastItemNumber);
    }

    public function test107Page10(): void
    {
        // sliding in to the right: 7 8 9 10 11
        $pageNumber = 10;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(7, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(11, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals($pageNumber + 1, $pages->nextPage);
        self::assertEquals(range(7, 11), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE * ($pageNumber - 1), $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE * $pageNumber - 1, $pages->lastItemNumber);
    }

    public function test107Page11(): void
    {
        // sliding in to the right: 7 8 9 10 11
        $pageNumber = 11;
        $itemCount  = 107;
        $pages      = PageInformation::forPage($pageNumber, $itemCount, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals($itemCount % self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(11, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(7, $pages->firstPageInRange);
        self::assertEquals(11, $pages->lastPage);
        self::assertEquals(11, $pages->lastPageInRange);
        self::assertEquals($pageNumber, $pages->currentPage);
        self::assertEquals($pageNumber - 1, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals(range(7, 11), $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE * ($pageNumber - 1), $pages->firstItemNumber);
        self::assertEquals($itemCount - 1, $pages->lastItemNumber);
    }

    public function testShowAll(): void
    {
        $itemCount = 15;
        $pages     = PageInformation::forPage(0, $itemCount);
        self::assertEquals($itemCount, $pages->itemsPerPage);
        self::assertEquals(1, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals($itemCount, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals($itemCount - 1, $pages->lastItemNumber);
    }

    public function testFastModeEmpty(): void
    {
        $pages = PageInformation::forPage(1, 0, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(0, $pages->totalItemCount);
        self::assertEquals(0, $pages->currentItemCount);
        self::assertEquals(0, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(0, $pages->lastItemNumber);
    }

    public function testFastModeOneItem(): void
    {
        $pages = PageInformation::forFastMode(1, 1, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(1, $pages->totalItemCount);
        self::assertEquals(1, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(0, $pages->lastItemNumber);
    }

    public function testFastModeOnePageFull(): void
    {
        // page 1 is full. we have exactly one page worth of items
        // we have to allow nav to the next page in case there are more items: fast mode cannot tell
        $pages = PageInformation::forFastMode(1, self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(self::ITEMS_PER_PAGE + 1, $pages->totalItemCount); // guess by fast mode: maybe another page?
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(2, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(2, $pages->lastPage);
        self::assertEquals(2, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(2, $pages->nextPage);
        self::assertEquals([1, 2], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE - 1, $pages->lastItemNumber); // zero based
    }

    public function testFastModeOnePageFullTrySecondPage(): void
    {
        // page 1 is full, so we are trying the second page, but it reverts us back to the first page because there
        // is no data on page 2: we exactly fill one page only. Cannot see a Next Page
        $pages = PageInformation::forFastMode(2, 0, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->totalItemCount); // definitely no more pages
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->currentItemCount);
        self::assertEquals(1, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(1, $pages->lastPage);
        self::assertEquals(1, $pages->lastPageInRange);
        self::assertEquals(1, $pages->currentPage);
        self::assertEquals(null, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1], $pages->pageNumbers);
        self::assertEquals(0, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE - 1, $pages->lastItemNumber); // zero based
    }

    public function testFastModeOnePagePlusOneSecondPage(): void
    {
        $pages = PageInformation::forFastMode(2, 1, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals(self::ITEMS_PER_PAGE + 1, $pages->totalItemCount);
        self::assertEquals(1, $pages->currentItemCount);
        self::assertEquals(2, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(2, $pages->lastPage);
        self::assertEquals(2, $pages->lastPageInRange);
        self::assertEquals(2, $pages->currentPage);
        self::assertEquals(1, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1, 2], $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->firstItemNumber);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->lastItemNumber);
    }

    public function testFastModeSecondPage(): void
    {
        $itemCount       = intval(ceil(1.5 * self::ITEMS_PER_PAGE));
        $itemsOnThisPage = $itemCount - self::ITEMS_PER_PAGE;
        $pages           = PageInformation::forFastMode(2, $itemsOnThisPage, self::ITEMS_PER_PAGE, self::PAGES_TO_SHOW);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->itemsPerPage);
        self::assertEquals(self::PAGES_TO_SHOW, $pages->maxPagesToShow);
        self::assertEquals($itemCount, $pages->totalItemCount);
        self::assertEquals($itemsOnThisPage, $pages->currentItemCount);
        self::assertEquals(2, $pages->pageCount);
        self::assertEquals(1, $pages->firstPage);
        self::assertEquals(1, $pages->firstPageInRange);
        self::assertEquals(2, $pages->lastPage);
        self::assertEquals(2, $pages->lastPageInRange);
        self::assertEquals(2, $pages->currentPage);
        self::assertEquals(1, $pages->previousPage);
        self::assertEquals(null, $pages->nextPage);
        self::assertEquals([1, 2], $pages->pageNumbers);
        self::assertEquals(self::ITEMS_PER_PAGE, $pages->firstItemNumber);
        self::assertEquals($itemCount - 1, $pages->lastItemNumber);
    }
}
