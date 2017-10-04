<?php

namespace Coralie;

/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Establishes and manages the SQL database connection.
 */

abstract class DatabaseConnection
{
	use Traits\Singleton;

	/**
	 * @var \PDO
	 */
	protected $pdo;

	/**
	 * Full name of the dialect class to use.
	 * @var string
	 */
	protected $dialectClass;

	public function init(array $settings): void
	{
		$connString = $settings["type"] . ":" .
			"host=" . $settings["host"] . ";" .
			"dbname=" . $settings["database"] . ";" .
			"charset=" . $settings["charset"];

		$options = [
				\PDO::ATTR_ERRMODE				=> \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE	=> \PDO::FETCH_OBJ,
				\PDO::ATTR_EMULATE_PREPARES		=> false
		];

		$this->pdo = new \PDO(
				$connString,
				$settings["username"],
				$settings["password"],
				$options);
	}

	/**
	 * Starts a query with the specified table.
	 *
	 * @param string $name Name of the table.
	 *
	 * @return Query
	 */
	public function table(string $name): Query
	{
		return new Query(
				$this,
				new $this->dialectClass(
						$this,
						$name));
	}

	/**
	 * Executes a raw SQL SELECT query.
	 *
	 * @param string $sql Execution-ready SQL query.
	 * @param array $params Parameters to bind.
	 *
	 * @return array Results array.
	 */
	public function runSelect(
			string $sql,
			array $params): array
	{
		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		return $statement->fetchAll();
	}

	/**
	 * @see DatabaseConnection::runStatement
	 */
	public function runInsert(
			string $sql,
			array $params): bool
	{
		return $this->runStatement(
				$sql,
				$params);
	}

	/**
	 * @see DatabaseConnection::runStatement
	 */
	public function runUpdate(
			string $sql,
			array $params): bool
	{
		return $this->runStatement(
				$sql,
				$params);
	}

	/**
	 * Executes a raw SQL statement.
	 *
	 * @param string $sql Execution-ready SQL query.
	 * @param array $params Parameters to bind.
	 *
	 * @return bool Execution success boolean.
	 */
	public function runStatement(
			string $sql,
			array $params): bool
	{
		$statement = $this->pdo->prepare($sql);

		return $statement->execute($params);
	}

	/**
	 * Quotes the specified value for use in an SQL query.
	 *
	 * @param $value
	 *
	 * @return string Quoted (if necessary) value.
	 */
	public function quote($value): string
	{
		// TODO:  Type-check value
		return $this->pdo->quote($value);
	}

}