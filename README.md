# Yet Another Database Abstraction Layer

A simple wrapper around PDO that provides

- utility functions to make database queries easier
- tools to declare your database schema in code and sync those with the server
- utilities to make common fields like display order, date created / updated easier
- fakes and stubs to ease tests and keep them away from talking to a physical database

It is currently MySQL only, with initial support for SQLite. Pull requests welcome to extend this to other DBMS.

## Why another one?

## Repository per table

Easiest way to use this is to declare one class per table in your database, and add utility functions to isolate your
code from queries in the database.

```php
class CompanyRepository extends AbstractRepository
{
    use DateCreatedFieldTrait, DateUpdatedFieldTrait;
    
    public const TABLE_NAME = 'company';

    public function getSchema(): Table
    {
        return (new TableBuilder(static::TABLE_NAME, 'Company profile'))
            ->addPrimaryKeyColumn()
            ->addStringColumn('name', FieldLength::COMPANY_NAME, 'name of company')
            ->addTextColumn('description', 'longer description in markdown format')
            ->addStringColumn('email_address', FieldLength::EMAIL, 'company generic contact email')
            ->addStringColumn('phone', FieldLength::PHONE, 'company phone number')
            ->addStringColumn('web_site', FieldLength::WEBSITE, 'company public web site')
            ->addTextColumn('address', 'physical address')
            ->addDateColumn('subscription_expires', 'Display less info after this date')
            ->addIntegerColumn('tech_staff', 'number of tech employed')
            ->addBooleanColumn('is_visible', 'true if the listing is shown to the public')
            ->addDateCreatedColumn()
            ->addDateUpdatedColumn()
            ->addStandardIndex('name')
            ->addStandardIndex('email_address')
            ->build();
    }

    public function getAllVisible(): array
    {
        $select = $this->selectAll()
                       ->andWhere(field('is_visible')->gt(0))
                       ->orderBy('name');
        return $this->fetchAll($select);
    }

    /**
     * @return array<int,string>
     */
    public function getList(): array
    {
        $select = $this->select('id', 'name')->from(static::TABLE_NAME)->orderBy('name');
        return $this->fetchPairs($select);
    }
}
```

## Test Utilities

Use a `FakeDatabaseConnection` to check that expected SQL queries and parameters are passed through to the database.
This can still allow room for bugs because it does not check that your declared schema matches the queries.

Use a `MemoryDatabaseConnection`. Build the tables first with `$repository->getSchema()->toSQLite()` then populate with
fake data to allow true end to end tests. See `DisplayOrderTest` for an example.

```php
```
