<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   5 11 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Paginator;

/**
 * Behaviour shared by all paginators. Implements a sliding window
 *
 * Borrows heavily from https://github.com/jasongrimes/php-paginator, and PagerFanta
 */
abstract class AbstractPaginator
{
    protected PageInformation $pages;

    abstract public function getItemsOnPage(): array;

    public function getPageInformation(): PageInformation
    {
        return $this->pages;
    }
}
