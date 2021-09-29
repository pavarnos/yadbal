<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   31 7 2019
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

/**
 * get the database schema for the repository
 */
interface GetSchemaInterface
{
    public function getSchema(): Table;
}
