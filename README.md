php-sqlite-key-value-store
==========================
[![Travis](https://travis-ci.org/rkrx/php-sqlite-key-value-store.svg)](https://travis-ci.org/rkrx/php-sqlite-key-value-store)

A sqlite-based key-value-store implementation.


Installation
------------

`composer require "rkr/sqlite-key-value-store" "v0.0.1"`


Dependencies
------------

- phpunit: ~3.7


Usage
-----

```PHP
<?php
namespace Example;
use Kir\Stores\KeyValueStores\Sqlite\PdoSqliteContextRepository;

require 'vendor/autoload.php';

$sqliteFilename = ':memory:';
$repository = new PdoSqliteContextRepository($sqliteFilename);
$store = $repository->get('persons');
$store->set('alfred', json_encode(['age' => 37]));
$value = json_decode($store->get('alfred', '[]'), true);
print_r($value);
```
