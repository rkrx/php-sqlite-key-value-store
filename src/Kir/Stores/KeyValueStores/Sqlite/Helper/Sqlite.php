<?php
namespace Kir\Stores\KeyValueStores\Sqlite\Helper;

use PDO;

class Sqlite extends PDO {
	/**
	 * @param string $filename
	 */
	public function __construct($filename = null) {
		parent::__construct(sprintf('sqlite:%s', $filename));
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->exec('CREATE TABLE IF NOT EXISTS s_contexts(id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL);');
		$this->exec('CREATE UNIQUE INDEX IF NOT EXISTS unique_name ON s_contexts(name);');
		$this->exec('CREATE TABLE IF NOT EXISTS s_keyvalue(context_id TEXT, name TEXT, value TEXT, ttl INTEGER, PRIMARY KEY (context_id, name), FOREIGN KEY(context_id) REFERENCES s_contexts(id));');
		$this->exec('CREATE INDEX IF NOT EXISTS context_index ON s_keyvalue(context_id);');
	}
} 