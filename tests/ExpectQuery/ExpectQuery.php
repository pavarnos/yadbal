<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   07 Jul 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\ExpectQuery;

use PHPUnit\Framework\TestCase;

class ExpectQuery
{
    public const IGNORE = ' --ignore-- '; // an unlikely value

    protected string $message;

    /** @var callable */
    protected $checkParamsMatch;

    public function __construct(protected string $sql, protected array $params, string $name = '')
    {
        $this->message          = empty($name) ? $sql : ($name . ': ' . $sql);
        $this->checkParamsMatch = function ($expectedParams, $actualParams): void {
            foreach ($expectedParams as $index => $value) {
                if ($value === self::IGNORE) {
                    $actualParams[$index] = self::IGNORE;
                }
            }
            TestCase::assertEquals($expectedParams, $actualParams, $this->message);
        };
    }

    /**
     * @param callable $checkParamsMatch
     * @return static
     */
    public function setParamCheck(callable $checkParamsMatch): static
    {
        $this->checkParamsMatch = $checkParamsMatch;
        return $this;
    }

    public function setParamCheckPassword(int|string $passwordIndex): static
    {
        $this->checkParamsMatch = function (array $expected, array $actual) use ($passwordIndex): void {
            // this mess lets us check the password is correctly hashed:
            // cannot compare hashes directly because they differ each time
            TestCase::assertTrue(password_verify($expected[$passwordIndex], $actual[$passwordIndex]), $this->message);
            unset($expected[$passwordIndex]);
            unset($actual[$passwordIndex]);
            foreach ($expected as $index => $value) {
                if ($value === self::IGNORE) {
                    $actual[$index] = self::IGNORE;
                }
            }
            TestCase::assertEquals($expected, $actual, $this->message);
        };
        return $this;
    }

    public function assertMatch(string $sql, array $params = []): void
    {
        $sql = str_replace('`', '', $sql);
        if ($this->sql[0] === '|') {
            TestCase::assertMatchesRegularExpression($this->sql, $sql, $this->message);
        } else {
            TestCase::assertEquals($this->sql, $sql, $this->message);
        }
        ($this->checkParamsMatch)($this->params, $params);
    }

    public function getResultArray(): array
    {
        return [];
    }

    public function getResultInt(): int
    {
        return 0;
    }
}
