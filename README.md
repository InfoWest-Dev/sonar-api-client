# PHP Sonar API Client

> **Warning**
> This package is currently undergoing a major refactor and is not ready for production use. We are rebranding from `kengineering/sonar-api-client` to `infowest-dev/sonar-api-client` with breaking changes including namespace updates and improved credential handling. See `dev/docs/` for the migration plan.

An object-oriented client for Sonar ISP management software's API. This client enables you to interact with Sonar's GraphQL API using PHP objects, supporting both individual and concurrent operations through queries and mutations.

## Installation

Install the package via composer:

```bash
composer require kengineering/sonar-api-client
```

## Requirements

Before making any requests, your application must have environment variables initialized. The client requires two specific variables in your `.env` file:

```env
SONAR_URL="your_sonar_url"
SONAR_KEY="your_api_key"
```

## Usage

The client provides two primary approaches for interacting with the Sonar API:

1. Direct object interaction through classes in `Kengineering\Sonar\Objects\` for single operations
2. Batch operations using `Kengineering\Sonar\Request` for concurrent queries or mutations

### Basic Queries

The client provides straightforward methods for retrieving data:

```php
// Get all accounts (paginated)
$accounts = Accounts::get();

// Get first account
$first_account = Accounts::first();
```

### Complex Queries

For more complex data requirements, you can build queries that fetch related data in a single request:

```php
// Get accounts with addresses
$accounts_with_addresses = Accounts::query()->addToChild(Address::class);

// Get accounts with status and type
$accounts_with_details = Accounts::query()
    ->addToChild(AccountStatus::class)
    ->addToParent(AccountType::class);
```

The query structure follows Sonar's GraphQL schema as a node tree. Use `addToParent()` to query related parent objects, `addToChild()` for child objects, and `end()` when you need to traverse multiple relationship layers.

## Searching In Queries

Every query instance provides search capabilities through its `search` property. You can apply various search filters:

```php
$account = Account::query()->search->intSearch('id', 12321);

$account = Account::query()
    ->search->stringSearch('name', 'John')
    ->search->intSearch('age', 25);
```

### Relation Searching
To search through related models, use `reverseRelationSearch`. This method accepts the relation name and a callback to define search criteria:

```php
$account = Account::query()
    ->addToChild(AccountStatus::class)
    ->reverseRelationSearch('account_status', function(Search $search) {
        $search->stringSearch('name', 'active');
    });
```

### Saving Objects

Objects can be created in Sonar in two ways. You can either set properties after instantiation or pass them through the constructor:

```php
$account = new Account;
$account->name = 'name';
$account->save();

$account = new Account(['name' => 'name']);
$account->save();
```

### Updating Objects

When updating existing objects, the client automatically determines the operation type based on the object's state. Simply modify the properties and call `save()`:

```php
$account = Account::first();
$account->name = 'name';
$account->save();
```

### Deleting Objects

Objects can be removed from Sonar using the `delete()` method. The operation will throw an error if Sonar has restrictions preventing deletion:

```php
$account = Account::first();
$account->delete();
```

### Batching Queries

For scenarios requiring multiple queries in a single request, use the Request class. Results are returned in the same order as the queries, or by name if specified:

```php
$request = new Request('query');

$request->addOperations([
    Account::query(),
    AccountStatus::query()
]);

$results = $request->get();
$accounts = $results[0];
$accountStatuses = $results[1];
```

Named queries provide clearer access to results:

```php
$request = new Request('query');

$request->addOperations([
    'accounts' => Account::query(),
    'account_statuses' => AccountStatus::query()
]);

$results = $request->get();
$accounts = $results['accounts'];
$accountStatuses = $results['account_statuses'];
```

### Batching Mutations

Mutations can be batched similarly to queries. Pass `true` to the batch_request parameter of mutation operations to prepare them for batching:

```php
$request = new Request('mutation');

$request->addOperations([
    $account->save(true),
    $account_status->delete(true)
]);
```

### Object Functions

Many objects provide helper functions to streamline common operations. These functions automatically fetch required related data if not already loaded:

```php
$account = Account::first();
$serviceable_address = $account->serviceableAddress();
```

Objects also include functions for managing relationships, which can be included in batch operations:

```php
$account = Account::first();
$service = Service::first();

$account->addService($service);
```
