## Yet Another Database Abstraction Layer

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
     * @return string[]
     */
    public function getList(): array
    {
        $select = $this->select('id', 'name')->from(static::TABLE_NAME)->orderBy('name');
        return $this->fetchPairs($select);
    }
}
```

Use a ChildRepository for dependent tables

```php
class ChildRepository extends AbstractChildRepository
{
    public const MAX_DAYS_OLD = 28;
    
    public const TABLE_NAME = 'company_job';

    public function getSchema(): Table
    {
        return (new TableBuilder(self::TABLE_NAME, ''))
            ->addPrimaryKeyColumn()
            ->addForeignKeyColumn(CompanyRepository::TABLE_NAME, '', '', ForeignKeyColumn::ACTION_CASCADE)
            ->addStringColumn('title', FieldLength::PAGE_TITLE, 'title of job')
            ->addTextColumn('content', 'description of job in plain text')
            ->addStringColumn('web_site', FieldLength::WEBSITE, 'where to apply')
            ->addDateColumn('date_expires', 'show the job until this date')
            ->addDateCreatedColumn()
            ->addStandardIndex('date_expires')
            ->build();
    }

    public function getVisibleFor(int $companyId): array
    {
        $select = $this->getSelect();
        $select->andWhere(field('company_id')->eq($companyId));
        $this->whereIsNotExpired($select);
        return $this->fetchAll($select);
    }

    public function getAllFor(int $companyId): array
    {
        $select = $this->getSelect();
        $select->andWhere(field('company_id')->eq($companyId));
        return $this->fetchAll($select);
    }
    
    protected function beforeSave(array $data): array
    {
        if (empty($data['date_expires'])) {
            $data['date_expires'] = Carbon::now()->addDays(self::MAX_DAYS_OLD)->toDateString();
        }
        return parent::beforeSave($data);
    }

    private function whereIsNotExpired(SelectQuery $select): SelectQuery
    {
        $select->andWhere(field(static::TABLE_NAME . '.date_expires')->gte($this->now()));
        return $select;
    }
}
```

The ChildRepository has some tools to prevent insecure direct object references, so you can only access the child record
if you know the parent id too.

```php
    $job = $jobRepository->findChildOrException($jobId, $companyId);
```

Similarly, for saving records you must supply the correct parent id to update, or a non zero parent id to create a new
row

## Test Utilities

Use a `FakeDatabaseConnection` to check that expected SQL queries and parameters are passed through to the database.
This can still allow room for bugs because it does not check that your declared schema matches the queries.

Use a `MemoryDatabaseConnection`. Build the tables first with `$repository->getSchema()->toSQLite()` then populate with
fake data to allow true end-to-end tests. See `DisplayOrderTest` for an example.

