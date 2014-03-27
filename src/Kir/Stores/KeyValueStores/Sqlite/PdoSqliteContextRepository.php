<?php
namespace Kir\Stores\KeyValueStores\Sqlite;

use Kir\Stores\KeyValueStores\Helpers\TypeCheckHelper;
use Kir\Stores\KeyValueStores\Sqlite\Helper\Sqlite;
use PDO;
use PDOStatement;
use PDOException;
use InvalidArgumentException;
use Kir\Stores\KeyValueStores\InvalidOperationException;
use Kir\Stores\KeyValueStores\IterableContextRepositoryWithIterableStores;

class PdoSqliteContextRepository implements IterableContextRepositoryWithIterableStores {
	/**
	 * @var PDO
	 */
	private $db = null;

	/**
	 * @var int
	 */
	private $ttl = null;

	/**
	 * @var array
	 */
	private $existCache = array();

	/**
	 * @var array
	 */
	private $instanceCache = array();

	/**
	 * @var PDOStatement
	 */
	private $preparedQueries = array();

	/**
	 * @param string $filename
	 * @param int $maxTtl
	 */
	public function __construct($filename, $maxTtl = null) {
		$this->db = $db = new Sqlite($filename);

		$db->prepare("DELETE FROM s_keyvalue WHERE ttl IS NOT NULL AND ttl < " . time());

		$this->preparedQueries['iterator'] = $db->prepare('SELECT id, name FROM s_contexts ORDER BY name');
		$this->preparedQueries['has'] = $db->prepare('SELECT COUNT(*) FROM s_contexts WHERE name=:name');
		$this->preparedQueries['add'] = $db->prepare('INSERT INTO s_contexts (name) VALUES (:name)');
		$this->preparedQueries['get'] = $db->prepare('SELECT id FROM s_contexts WHERE name=:name');
		$this->preparedQueries['remove'] = $db->prepare('DELETE FROM s_contexts WHERE name=:name');
	}

	/**
	 * @return PdoSqliteStore[]
	 */
	public function getIterator() {
		$stmt = $this->getPreparedQuery('iterator');
		$stmt->execute();
		$array = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
		$stmt->closeCursor();
		$array = array_values($array);
		return new \ArrayIterator($array);
	}

	/**
	 * @param string $name
	 * @throws InvalidArgumentException
	 * @throws InvalidOperationException
	 * @return bool
	 */
	public function has($name) {
		$name = TypeCheckHelper::convertKey($name);
		if(array_key_exists($name, $this->existCache)) {
			return $this->existCache[$name];
		}
		try {
			$stmt = $this->getPreparedQuery('has');
			$stmt->bindParam(':name', $name, PDO::PARAM_STR);
			$stmt->execute();
			$this->existCache[$name] = $stmt->fetchColumn(0) > 0;
			$stmt->closeCursor();
			return $this->existCache[$name];
		} catch(PDOException $e) {
			throw new InvalidOperationException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $name
	 * @return PdoSqliteStore
	 * @throws InvalidOperationException
	 * @throws InvalidArgumentException
	 */
	public function get($name) {
		$name = TypeCheckHelper::convertKey($name);
		if(!array_key_exists($name, $this->instanceCache)) {
			$id = $this->add($name);
			$this->existCache[$name] = true;
			$this->instanceCache[$name] = new PdoSqliteStore($this->db, $id, $this->ttl);
		}
		return $this->instanceCache[$name];
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws InvalidOperationException
	 * @throws InvalidArgumentException
	 */
	public function remove($name) {
		$name = TypeCheckHelper::convertKey($name);
		try {
			$stmt = $this->getPreparedQuery('remove');
			$stmt->bindValue(':name', $name);
			$stmt->execute();
			$stmt->closeCursor();

			if(array_key_exists($name, $this->existCache)) {
				unset($this->existCache[$name]);
			}
			if(array_key_exists($name, $this->instanceCache)) {
				unset($this->instanceCache[$name]);
			}
		} catch(PDOException $e) {
			throw new InvalidOperationException($e->getMessage(), $e->getCode());
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @throws InvalidOperationException
	 * @return string
	 */
	private function add($name) {
		try {
			if(!$this->has($name)) {
				$stmt = $this->getPreparedQuery('add');
				$stmt->bindValue(':name', $name);
				$stmt->execute();
				$stmt->closeCursor();
			}
			$stmt = $this->getPreparedQuery('get');
			$stmt->bindValue(':name', $name);
			$stmt->execute();
			$id = $stmt->fetchColumn(0);
			$stmt->closeCursor();
		} catch(PDOException $e) {
			throw new InvalidOperationException($e->getMessage(), $e->getCode());
		}
		return $id;
	}

	/**
	 * @param string $name
	 * @return PDOStatement
	 */
	private function getPreparedQuery($name) {
		return $this->preparedQueries[$name];
	}
}