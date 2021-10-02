## Yet Another Database Abstraction Layer

A simple wrapper around PDO that provides

- utility functions to make database queries easier with less boilerplate
- tools to declare your database schema in code and sync those with the server
- utilities to make common fields like display order, date created / updated, json easier to work with
- a lightweight paginator
- fakes and stubs to ease tests so they don't have to talk to a physical database

It is currently MySQL only, with some support for SQLite to help with unit tests. Pull requests welcome to extend this
to other DBMS.

## Why another one?

This is based on an ancient library i wrote in 2007 to allow a database schema definition all in PHP and (at the click
of a button) generate alter table statements to synch the mysql server with the PHP. This was handy for a number of
sites that had a single install location or a very limited number of users. It allowed

- a build process to update the schema on demand.
- custom reports to introspect the schema and generate a reasonably rich description of the tables and fields. Users
  could pick which a table to start with and the report writer knows what tables can join with it because of the foreign
  key relationships.
- a database diagram to be generated with graphviz
- and automated schema documentation, both supporting the report builder.
- cross checking of foreign key relationships so we could do different kinds of data integrity checks
  (without relying on the DBMS to maintain referential integrity, which didn't make sense in all cases)
- checking that dependant models are correctly wired up to handle onChange and onDelete events when data in the parent
  model changes. (This is done in a pre-release build step). You can use foreign key constraints in most cases, but not
  all

Yeah, there are better ways to do this now with full ORMs and other goodness. But this is

- battle tested over many years
- as lightweight as possible internally
- tries hard to minimise the amount of boilerplate code needed to use it
- has high test coverage
- phpstan --level=max
- PHP8.0 with strict types

And I still use it in a few places and for new greenfield projects.

## Installation

```
composer require lss/yadbal
```

`lss/yadbal` replaces [lss/schema](https://github.com/pavarnos/schema) which only did the schema stuff. This improves on
it by supporting MySQL 8 and PHP8, adding Repository classes and Database Connections, adding Paginators, and providing
core services for `lss/yareport`: a low effort reporting writing tool.

## Repository per table

Use the `TableBuilder` convenience methods to declare your schema, or just create new columns and add them to
the `Schema` as needed.

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
    use DateCreatedColumnTrait;
    
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
        // date_created is automatically calculated
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
if you know the parent id too. eg

- `findChildOrNull($id, $parentId)`
- `findChildOrException($id, $parentId)`
- on new row, `save()` throws an exception if parent id is not set
- on save existing row, `save()` will only update if the id matches the parent id

```php
    $job = $jobRepository->findChildOrException($jobId, $companyId);
```

Declare one class per table in your database. Put them all in the same directory in a directory structure that mirrors
the table structure. eg if your database structure is

- `company`
- `company_employee`
- `company_job`
- `user`
- `user_mentor`

Then your filesystem might look like

- `Repository/CompanyRepository.php`  : extends AbstractRepository
- `Repository/Company/EmployeeRepository.php` : extends AbstractChildRepository
- `Repository/Company/JobRepository.php` : extends AbstractChildRepository
- `Repository/UserRepository.php` : extends AbstractRepository
- `Repository/User/MentorRepository.php` : extends AbstractChildRepository

Add utility functions to isolate your code from queries in the database: ideally you will not have any SelectQuery
instances in your own code. They are there if you need them, but try to move all query building code to methods on your
Repository classes. Call those methods to get the data you need. This encapsulation will create a nice layer between
your business logic and your database logic. It also makes the Repositories much easier to mock when writing tests. You
can mock `$companyRepository->getVisibleCompanies()` and return the desired array instead of having to mess around
rebuilding your SQL queries in your tests.

## Schema Syncing

The above code snippets have examples of declaring your schema for each table.

Create a class that asks all your Repositories for their schema via `getSchema()`

```php
/**
 * Get the database schema: a set of tables that are in the database.
 * Note this is the ideal / declared schema from the code.
 * After changing the software, it may need to be upgraded using SchemaUpgrade
 */
class SchemaFromDeclarations
{
    public const REPOSITORY_PATH = '/path/to/repositories';
    
    private SchemaInterface $schema;
    
    public function __construct(private ContainerInterface $container) 
    {
    }    

    public function build(): SchemaInterface
    {
        if (!empty($this->schema)) {
            return $this->schema;
        }
        $this->schema = new Schema();
        $finder = new Symfony\Finder();
        $finder->in(self::REPOSITORY_PATH)->name('*Repository.php')->sortByName()->notName('Abstract*.php');
        foreach ($finder as $fileInfo) {
            $className = $this->fileNameToClassName($fileInfo->getRealPath() ?: '');
            $repository = $this->container->get($className);
            assert($repository instanceof AbstractRepository);
            $this->schema->addTable($repository->getSchema());
        }
        return $this->schema;
    }
    
    private function fileNameToClassName(string $fileName): string
    {
        // ...
    }
}
```

And then a something that can do the upgrading for you

```php
class DatabaseUpgradeCommand 
{
    public function __construct(
        private SchemaFromDeclarations $wanted,
        private SchemaFromMySQL $actual,
        private DatabaseConnectionInterface $database
    ) {
    }

    public function doUpgrade(): void
    {
        $wanted  = $this->wanted->build();
        $upgrade = (new SchemaUpgradeCalculator())->getUpgradeSQL($wanted, $this->actual->build());
        $this->database->transaction(
            function () use ($upgrade): void {
                foreach ($upgrade as $query) {
                    $this->database->execute($query);
                }
            }
        );
    }
}
```

I usually create a symfony console command that dumps the upgrade statements unless a `--apply` option is passed.

### known limitations

- does not handle the meta stuff at the end of the table eg type, encoding etc
- works better with a single integer primary key index field first in the table
- the sync / comparator can cope with one or more columns added / deleted renamed but gets easily confused if you do a
  lot of big changes at once. Having unique comments on each field helps it resync itself. You can change one of (column
  name, data type, comment) for the field to be altered. Change two and it will think it is a new field, deleting the
  old one and adding a new one

## Test Utilities

Use a `FakeDatabaseConnection` to check that expected SQL queries and parameters are passed through to the lowest
database layer without actually touching a real database. They are good for unit tests, but can still allow room for
bugs because it does not check that your declared schema matches the queries. So you still want to do end to end tests.

Use a `MemoryDatabaseConnection` for end to end tests. Build the tables first
with `$repository->getSchema()->toSQLite()` then populate with fake data to allow true end-to-end tests.
See `DisplayOrderTest` for an example. You may have trouble with calculated columns... pull request welcome if you know
of a fix for that?

