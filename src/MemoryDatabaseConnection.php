<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   09 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal;

use Carbon\Carbon;
use Latitude\QueryBuilder\ExpressionInterface;
use LSS\YADbal\DatabaseConnection\PDOConnection;

use function Latitude\QueryBuilder\express;

class MemoryDatabaseConnection extends DatabaseConnection
{
    public function __construct(PDOConnection $read = null, PDOConnection $write = null)
    {
        $read ??= new PDOConnection('sqlite::memory:');
        parent::__construct($read, $write);
    }

    public function now(): ExpressionInterface
    {
        return express('%s', Carbon::now()->toDateTimeString());
    }
}
