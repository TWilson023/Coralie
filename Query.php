<?php
/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Builds and prepares SQL queries.
 */

namespace Coralie;

abstract class QueryTypes
{
	const SELECT = 'select';
	const INSERT = 'insert';
	const UPDATE = 'update';
	const DELETE = 'delete';
}

class Query
{
	/**
	 * @var Dialect
	 */
	protected $dialect;

	/**
	 * QueryTypes specifier.
	 * @var string
	 */
	protected $type = QueryTypes::SELECT;

	/**
	 * Columns to select/affect.
	 * @var array|null
	 */
	public $columns;

	/**
	 * Array of where constraints with the following format:
	 *
	 * [
	 * 	['name', '=', 'AND'],
	 *  [[
	 * 		['age', '>', 'OR'],
	 * 		['name', '=', 'AND']
	 * 	], 'OR']
	 * ]
	 *
	 * Which would result in the following SQL:
	 * WHERE "name" = ? OR ("age" > ? AND "name" = ?)
	 *
	 * @var array
	 */
	public $where = [];

	/**
	 * Result count limit.
	 * @var int
	 */
	public $limit;

	/**
	 * Query parameters
	 * @var array
	 */
	public $params = [];

	public function __construct(
			DatabaseConnection $connection,
			Dialect $dialect)
	{
		$this->connection = $connection;
		$this->dialect = $dialect;
	}

	/**
	 * Starts a select statement with the specified columns.  You may pass
	 * either an array of column names, or specify each as an argument.
	 *
	 * @param array|mixed|null $columns array of columns, or null if none.
	 *
	 * @return Query
	 */
	public function select($columns = ["*"]): Query
	{
		$this->type = QueryTypes::SELECT;
		$this->columns = is_array($columns) ? $columns : func_get_args();

		return $this;
	}

	/**
	 * @param array $data Associative array of columns and values.
	 *
	 * @return Query
	 */
	public function insert(array $data): Query
	{
		$this->type = QueryTypes::INSERT;
		$this->columns = array_keys($data);
		$this->params = array_values($data);

		return $this;
	}

	/**
	 * @param array $changes Associative array of columns and values.
	 *
	 * @return Query
	 */
	public function update(array $changes): Query
	{
		$this->type = QueryTypes::UPDATE;
		$this->columns = array_keys($changes);
		$this->params = array_values($changes);

		return $this;
	}
	
	/**
	 * Initiate a DELETE query with optional WHERE constraints.
	 * 
	 * @param mixed (optional) $column Column or expression (s) to compare.
	 * @param mixed $operator (optional) Comparison operator (e.g. '=' or '<').
	 * @param mixed $value (optional) Value with which to compare.
	 * 
	 * @return Query
	 */
	public function delete(
			$column = null,
			?string $operator = null,
			$value = null): Query
	{
		$this->type = QueryTypes::DELETE;
		
		if ($column !== null)
			return $this->where(
					$column,
					$operator,
					$value,
					'AND');
		
		return $this;
	}

	/**
	 * Adds an AND clause to the query constraints.
	 *
	 * @param callable|array|mixed $column Column or expression (s) to compare.
	 * @param string $operator Comparison operator (e.g. '=' or '<').
	 * @param mixed $value Value with which to compare.
	 *
	 * @return Query
	 */
	public function and(
			$column,
			?string $operator,
			$value = null): Query
	{
		return $this->where(
				$column,
				$operator,
				$value,
				'AND');
	}

	/**
	 * Adds an OR clause to the query constraints.
	 *
	 * @param callable|array|mixed $column Column or expression (s) to compare.
	 * @param string $operator Comparison operator (e.g. '=' or '<').
	 * @param mixed $value Value with which to compare.
	 *
	 * @return Query
	 */
	public function or(
		$column,
		?string $operator,
		$value = null)
	{
		return $this->where(
				$column,
				$operator,
				$value,
				'OR');
	}
	
	/**
	 * @param callable|array|mixed $column Column or expression (s) to compare.
	 * @param string $operator Comparison operator (e.g. '=' or '<').
	 * @param mixed $value Value with which to compare.
	 * @param string $bool Boolean type ('AND' or 'OR')
	 *
	 * @throws Exceptions\MalformedConstraintException
	 * @return Query
	 */
	public function where(
			$column,
			?string $operator,
			$value = null,
			string $bool = 'AND'): Query
	{	
		if (is_array($column))
		{
			// Two-dimensional comparison array
			$chain = $this;
			foreach ($column as $comp)
			{
				if (count($comp) < 2)
					throw new Exceptions\MalformedConstraintException(
							"Invalid comparison array count (must be 2-3)");

				$chain = $chain->where(
						$comp[0],
						count($comp) == 2 ? '=' : $comp[1],
						count($comp) > 2 ? $comp[2] : $comp[1]);
			}

			return $chain;
		}

		if (is_callable($column))
			// Function passed (for nested constraints)
			return $this->addNestedWhere(
					$column,
					$bool);
		
		if (!in_array(
				$operator,
				$this->dialect->comparisonOperators))
			list($operator, $value) = ['=', $operator];
		
		return $this->addWhere(
				$column,
				$operator ?? '=',
				$value,
				$bool);
	}

	/**
	 * Adds a where constraint.
	 *
	 * @param string|mixed $column Column name or expression.
	 * @param string $operator Comparison operator.
	 * @param mixed $value Value with which to compare.
	 * @param string $bool Boolean type ('AND' or 'OR')
	 *
	 * @return Query
	 */
	protected function addWhere(
			$column,
			string $operator,
			$value,
			$bool = 'AND'): Query
	{
		// Add to where constraint array
		array_push(
				$this->where,
				[$column, $operator, $bool]);

		// Add to params for statement binding
		array_push(
				$this->params,
				$value);

		return $this;
	}

	/**
	 * Adds a where constraint.
	 *
	 * @param \Closure|Query $callable nested condition callable or Query.
	 * @param string $bool Boolean type ('AND' or 'OR')
	 *
	 * @return Query
	 */
	protected function addNestedWhere(
			$callable,
			string $bool = 'AND'): Query
	{
		if (is_callable($callable))
			return $this->addNestedWhere(
					$callable($this->new()),
					$bool);

		// Query ready-to-go ($callable is a Query object)
		array_push(
				$this->where,
				[$callable->where, $bool]);

		$this->params = array_merge(
				$this->params,
				$callable->params);

		return $this;
	}

	/**
	 * Limits the amount of results returned by the database.
	 *
	 * @param int $count Number of results to return.
	 *
	 * @return Query
	 */
	public function limit(int $count): Query
	{
		$this->limit = $count;

		return $this;
	}

	/**
	 * Creates a new query with the same dialect.
	 *
	 * @return Query
	 */
	protected function new(): Query
	{
		return new Query(
				$this->connection,
				$this->dialect);
	}

	/**
	 * Executes the query
	 *
	 * @return mixed
	 */
	public function execute()
	{
		// Call the connection's run[Type] method
		return $this->connection->{"run" . ucfirst($this->type)}(
				$this->build(),
				$this->params);
	}

	/**
	 * @return string Execution-ready query string.
	 */
	public function build(): string
	{
		// Call the dialect's compose[Type] method
		return $this->dialect->{"compose" . ucfirst($this->type)}($this);
	}

	public function __toString(): string
	{
		return $this->build();
	}
}