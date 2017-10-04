<?php

namespace Coralie;

/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Abstract class representing an SQL dialect (MySQL, PostgreSQL, etc.)
 */

abstract class Dialect
{
	/**
	 * @var string
	 * Symbols to quote identifiers (two chars if different beginning and end).
	 */
	protected $identifierQuotes = '"';

	/**
	 * @var DatabaseConnection
	 */
	protected $connection;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $tablePrefix;

	/**
	 * @param string (optional) $table Name of the database table for which this
	 * 		  dialect will be used.
	 */
	public function __construct(
			DatabaseConnection $connection,
			?string $table = null)
	{
		$this->connection = $connection;
		$this->table = $table;

		$this->tablePrefix = ($table ? $this->quoteIdentifier(
				$table,
				false) : "") . ".";
	}

	/**
	 * Wraps a value with identifier quotes (double quotes for standard SQL).
	 * If the $value parameter is "*", it will not be quoted.  However, it may
	 * still be prefixed with the table name (depending on $prefix boolean).
	 *
	 * @param string $value Value to wrap in identifier quotes.
	 * @param bool $prefix Whether the identifier should be prefixed with the
	 * 		  table.
	 *
	 * @return string Value wrapped in identifier quotes.
	 */
	protected function quoteIdentifier(
			string $value,
			bool $prefix = true): string
	{
		if ($value !== "*")
			$value =
				$this->identifierQuotes[0] .
				$value .
				($this->identifierQuotes[1] ?? $this->identifierQuotes[0]);

		return ($prefix ? $this->tablePrefix : "") . $value;
	}

	/**
	 * Converts an array of columns to a query-ready string.
	 *
	 * @param array $columns Array of column names.
	 * @param bool $prefix (optional) Whether the columns should be prefixed
	 * 		  with the table (default: true).
	 * @param bool $parentheses (optional) Whether the columns should be
	 * 		  encapsulated with parentheses (default: false).
	 *
	 * @return string Query-ready column string.
	 */
	protected function prepareColumns(
			array $columns,
			bool $prefix = true,
			bool $parentheses = false): string
	{
		return $this->prepareList(array_map(function ($column) use ($prefix) {
			return $this->quoteIdentifier(
					$column,
					$prefix);
		}, $columns), $parentheses);
	}

	/**
	 * Converts an array of values to a query-ready string.
	 *
	 * @param array $columns Array of column names.
	 * @param bool $parentheses (optional) Whether the list should be
	 * 		  encapsulated with parentheses (default: true).
	 *
	 * @return string Query-ready list string.
	 */
	protected function prepareList(array $values, bool $parentheses = true)
	{
		$sql = implode(
				",",
				$values);

		return $parentheses ? "($sql)" : $sql;
	}

	/**
	 * @param array|null $columns SELECT columns.
	 *
	 * @return string SQL statement component.
	 */
	protected function prepareSelect(?array $columns): string
	{
		if ($columns === null)
			return "SELECT ''";

		return "SELECT " . $this->prepareColumns($columns ?? ['*']);
	}

	/**
	 * @param array $constraints two-dimensional array of query constraints.
	 * @param bool $withKeyword (optional) whether to prepend "WHERE " to the
	 * 		  result (default: true)
	 *
	 * @return string|null SQL statement component, or null if none.
	 */
	protected function prepareWhere(
			array $constraints,
			bool $withKeyword = true): ?string
	{
		if (!$constraints) return null;

		// TODO:  Validate comparison operators
		// TODO:  Add expression support

		return ($withKeyword ? "WHERE " : "") .
			implode(" ", array_map(function ($c, $idx) {
				// Add parentheses to nested conditions
				if (is_array($c[0]))
				{
					list($nested, $bool) = $c;

					return ($idx > 0 ? $bool : "") . // AND/OR
						" (" . $this->prepareWhere(
								$nested,
								false) . ")"; // Conditions
				}
				list($column, $comparison, $bool) = $c;

				return ($idx > 0 ? $bool . " " : "") . // AND/OR
					$this->quoteIdentifier($column) . $comparison . '?';

			}, $constraints, array_keys($constraints)));
	}

	/**
	 * @param int|null $limit LIMIT count.
	 *
	 * @return string|null SQL statement component, or null if none.
	 */
	protected function prepareLimit(?int $limit): ?string
	{
		return $limit !== null ? "LIMIT $limit" : null;
	}

	/**
	 * Implodes arguments (ignoring nulls).
	 *
	 * @param string|null ...$args Arguments to concatenate.
	 *
	 * @return string Execution-ready query string.
	 */
	public function compose(?string ...$args): string
	{
		// Only include/concatenate return values that aren't null
		return implode(
				" ", array_filter(
						$args, function ($item) {
							return $item !== null;
						})) . ";";
	}

	/**
	 * Composes a SELECT query to be executed.
	 *
	 * @param Query $query Query instance to build with.
	 *
	 * @return string Execution-ready query string.
	 */
	public function composeSelect(Query $query): string
	{
		return $this->compose(
				$this->prepareSelect($query->columns),

				"FROM " . $this->quoteIdentifier(
						$this->table,
						false),

				$this->prepareWhere($query->where),
				$this->prepareLimit($query->limit));
	}

	/**
	 * Composes an INSERT query to be executed.
	 *
	 * @param Query $query Query instance to build with.
	 *
	 * @return string Execution-ready query string.
	 */
	public function composeInsert(Query $query): string
	{
		return $this->compose(
				"INSERT INTO " . $this->quoteIdentifier(
						$this->table,
						false),

				$this->prepareColumns(
						$query->columns,
						false,
						true),

				"VALUES " . $this->prepareList(array_fill(
						0,
						count($query->columns),
						'?')));
	}

	/**
	 * Composes an INSERT query to be executed.
	 *
	 * @param Query $query Query instance to build with.
	 *
	 * @return string Execution-ready query string.
	 */
	public function composeUpdate(Query $query): string
	{
		return $this->compose(
				"UPDATE " . $this->quoteIdentifier(
						$this->table,
						false),

				"SET " . $this->prepareList(array_map(function ($column) {
					return $this->quoteIdentifier($column) . "=?";
				}, $query->columns), false),

				$this->prepareWhere($query->where));
	}

}