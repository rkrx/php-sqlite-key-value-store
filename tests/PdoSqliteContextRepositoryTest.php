<?php
namespace Kir\Stores\KeyValueStores\Sqlite;

use Kir\Stores\KeyValueStores\ComplianceTests\Common\ContextRepositoryTest;
use Kir\Stores\KeyValueStores\ComplianceTests\ContextRepositoryTestInterface;
use Kir\Stores\KeyValueStores\ComplianceTests\Helpers\ClosureContextRepositoryFactory;

class PdoSqliteContextRepositoryTest extends ContextRepositoryTest implements ContextRepositoryTestInterface {
	/**
	 */
	public function setUp() {
		parent::setContextRepository(new ClosureContextRepositoryFactory(function () {
			return new PdoSqliteContextRepository(':memory:', null);
		}));
	}

	/**
	 */
	public function testGet() {
		$stores = new PdoSqliteContextRepository(':memory:', null);
		$store = $stores->get('test');
		$this->assertInstanceOf('Kir\\Stores\\KeyValueStores\\Sqlite\\PdoSqliteStore', $store);
		parent::testGet();
	}
}
 