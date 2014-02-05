<?php
namespace Kir\Stores\KeyValueStores\Sqlite;

use PDO;
use PDOStatement;
use Exception;
use InvalidArgumentException;
use Kir\Stores\KeyValueStores\InvalidOperationException;
use Kir\Stores\KeyValueStores\Common\TypeCheckHelper;
use Kir\Stores\KeyValueStores\ReadWriteStore;

class PdoSqliteStore implements ReadWriteStore {
	/**
	 * @var PDOStatement
	 */
	private $preparedQueries = array();

	/**
	 * @param PDO $db
	 * @param int $id
	 */
	public function __construct(PDO $db, $id) {
		static $x = 0; $x++;
		$id = intval($id);
		$this->preparedQueries['has'] = $db->prepare("SELECT COUNT(*) FROM s_keyvalue WHERE context_id={$id} AND name=:key");
		$this->preparedQueries['get'] = $db->prepare("SELECT value FROM s_keyvalue WHERE context_id={$id} AND name=:key");
		$this->preparedQueries['set'] = $db->prepare("REPLACE INTO s_keyvalue (context_id, name, value) VALUES ({$id}, :key, :value)");
		$this->preparedQueries['rem'] = $db->prepare("DELETE FROM s_keyvalue WHERE context_id={$id} AND name=:key");
	}

	/**
	 * @param string $key
	 * @return bool
	 * @throws InvalidArgumentException
	 * @throws InvalidOperationException
	 */
	public function has($key) {
		$key = TypeCheckHelper::convertKey($key);
		$stmt = $this->getPreparedQuery('has');
		try {
			$stmt->bindParam(':key', $key, PDO::PARAM_STR);
			$stmt->execute();
			$res = $stmt->fetchColumn(0) > 0;
			$stmt->closeCursor();
		} catch (Exception $e) {
			$stmt->closeCursor();
			throw new InvalidOperationException($e->getMessage());
		}
		return $res;
	}

	/**
	 * @param string $key
	 * @param mixed $default If the key does not exist, use this
	 * @return mixed
	 * @throws InvalidArgumentException
	 * @throws InvalidOperationException
	 */
	public function get($key, $default = null) {
		$key = TypeCheckHelper::convertKey($key);
		$default = TypeCheckHelper::convertDefault($default);
		if(!$this->has($key)) {
			return $default;
		}
		$stmt = $this->getPreparedQuery('get');
		try {
			$stmt->bindParam(':key', $key, PDO::PARAM_STR);
			$stmt->execute();
			$string = $stmt->fetchColumn(0);
			$stmt->closeCursor();
		} catch (Exception $e) {
			$stmt->closeCursor();
			throw new InvalidOperationException($e->getMessage());
		}
		return unserialize($string);
	}

	/**
	 * @param string $key
	 * @param mixed $value The value to store.
	 * @return $this
	 * @throws InvalidOperationException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function set($key, $value) {
		$key = TypeCheckHelper::convertKey($key);
		$value = TypeCheckHelper::convertValue($value);
		$stmt = $this->getPreparedQuery('set');
		try {
			$string = serialize($value);
			$stmt->bindParam(':key', $key, PDO::PARAM_STR);
			$stmt->bindParam(':value', $string, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
		} catch (Exception $e) {
			$stmt->closeCursor();
			throw new InvalidOperationException($e->getMessage());
		}
		return $this;
	}

	/**
	 * @param string $key
	 * @return $this
	 * @throws InvalidOperationException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function remove($key) {
		$key = TypeCheckHelper::convertKey($key);
		$stmt = $this->getPreparedQuery('rem');
		try {
			if(!$this->has($key)) {
				throw new InvalidOperationException("Entry not found");
			}
		} catch (InvalidOperationException $e) {
			throw $e;
		} catch (Exception $e) {
			throw new InvalidOperationException($e->getMessage());
		}
		try {
			$stmt->bindParam(':key', $key);
			$stmt->execute();
			$stmt->closeCursor();
		} catch (Exception $e) {
			$stmt->closeCursor();
			throw new InvalidOperationException($e->getMessage());
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return PDOStatement
	 */
	private function getPreparedQuery($name) {
		return $this->preparedQueries[$name];
	}
}