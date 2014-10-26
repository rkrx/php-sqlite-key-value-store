<?php
namespace Kir\Stores\KeyValueStores\Sqlite;

use Kir\Stores\KeyValueStores\Sqlite\Helper\Sqlite;
use Kir\Stores\KeyValueStores\ComplianceTests\Common\ReadWriteStoreTest;
use Kir\Stores\KeyValueStores\ComplianceTests\Helpers\ClosureStoreFactory;
use Kir\Stores\KeyValueStores\ComplianceTests\ReadWriteStoreTestInterface;

class PdoSqliteStoreTest extends ReadWriteStoreTest implements ReadWriteStoreTestInterface {
	/**
	 */
	public function setUp() {
		$db = new Sqlite(':memory:');
		parent::setStoreFactory(new ClosureStoreFactory(function () use ($db) {
			return new PdoSqliteStore($db, 1, null);
		}));
	}
}
 