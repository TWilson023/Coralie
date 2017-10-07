<?php

namespace Coralie;

use Coralie\Schema\Column;
use Coralie\Schema\ColumnTypes;

/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Abstract class representing an SQL dialect (MySQL, PostgreSQL, etc.)
 */

abstract class Dialect
{
	/**
	 * Valid comparison operators.
	 * 
	 * @var array
	 */
	public $comparisonOperators = ['<', '>', '<=', '>=', '='];
	
	/**
	 * Symbols to quote identifiers (two chars if different beginning and end).
	 * 
	 * @var string
	 */
	protected $identifierQuotes = '"';
	
	/**
	 * Data type names and lengths
	 * 
	 * @var array
	 */
	protected $dataTypes = [
			ColumnTypes::INTEGER => ['INTEGER'],
			ColumnTypes::SMALLINT => ['SMALLINT'],
			ColumnTypes::DECIMAL => ['DECIMAL'],
			ColumnTypes::FLOAT => ['FLOAT'],
			ColumnTypes::BIT => ['BIT'],
			ColumnTypes::CHARACTER => ['CHARACTER'],
			ColumnTypes::VARCHAR => ['VARCHAR', 65535],
			ColumnTypes::DATE => ['DATE'],
			ColumnTypes::TIME => ['TIME'],
			ColumnTypes::TIMESTAMP => ['TIMESTAMP'],
			ColumnTypes::BOOLEAN => ['BOOLEAN'],
	];

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
	 * @param bool $prefixTable (optional) Whether the columns should be
	 * 		  prefixed with the table (default: true).
	 * @param bool $parentheses (optional) Whether the columns should be
	 * 		  encapsulated with parentheses (default: false).
	 * @param string $prefix String to prepend each typed column with (separated
	 * 		  by a space).
	 *
	 * @return string Query-ready column string.
	 */
	protected function prepareColumns(
			array $columns,
			bool $prefixTable = true,
			bool $parentheses = false,
			string $prefix = ""): string
	{
		return $this->prepareList(array_map(function ($column)
				use ($prefixTable, $prefix) {
			$quoted = $this->quoteIdentifier(
					$column,
					$prefixTable);
			
			return (strlen($prefix) ? "$prefix " : "") . $quoted;
		}, $columns), $parentheses);
	}
	
	/**
	 * Converts an array of Column objects to a query-ready string for addition.
	 * 
	 * @param array $columns
	 * @param bool $includeKey whether primary keys should be specified.
	 * 
	 * @return string|null Query-ready column string.
	 */
	protected function prepareAddColumns(
			array $columns,
			bool $includeKey = false): ?string
	{
		if (!$columns) return null;
		
		return $this->prepareTypedColumns(
				$columns,
				false,
				"ADD",
				$includeKey);
	}
	
	/**
	 * Converts an array of Column objects to a query-ready string for changing.
	 * 
	 * @param array $columns
	 * @param bool $includeKey whether primary keys should be specified.
	 * 
	 * @return string|null Query-ready column string.
	 */
	protected function prepareAlterColumns(
			array $columns,
			bool $includeKey = false): ?string
	{
		if (!$columns) return null;
		
		return $this->prepareTypedColumns(
				$columns,
				false,
				"MODIFY",
				$includeKey);
	}
	
	/**
	 * Converts an array of Column objects to a query-ready string for dropping.
	 * 
	 * @param array $columns
	 * 
	 * @return string|null Query-ready column string.
	 */
	protected function prepareDropColumns(array $columns): ?string
	{
		if (!$columns) return null;
		
		return $this->prepareColumns(
				array_map(function (Column $column) {
					return $column->name;
				}, $columns),
				false,
				false,
				"DROP COLUMN");
	}
	
	/**
	 * Converts an array of Column objects to a query-ready string.
	 *
	 * @param Column[] $columns Array of Column objects.
	 * @param bool $parentheses (optional) Whether the columns should be
	 * 		  encapsulated with parentheses (default: false).
	 * @param string $prefix String to prepend each typed column with (separated
	 * 		  by a space).
	 * @param bool $includeKey whether primary keys should be specified.
	 *
	 * @return string Query-ready column string.
	 */
	protected function prepareTypedColumns(
			array $columns,
			bool $parentheses = true,
			string $prefix = "",
			bool $includeKey = true): string
	{
		// List items to be appended to the end (e.g. PRIMARY KEY (col))
		$appends = [];
		
		$items = array_map(function (Column $column)
				use (&$appends, $prefix, $includeKey) {
			$parts = [];
			
			if (strlen($prefix))
				$parts[] = $prefix;
			
			$quotedName = $this->quoteIdentifier(
					$column->name,
					false);
			
			// Column name
			$parts[] = $quotedName;
			
			// Column type
			$parts[] = $this->prepareDataType(
					$column->type,
					$column->length);
			
			// Column properties
			if ($column->properties)
				$parts[] = implode(
						' ',
						$column->properties);
			
			if ($column->isPrimary && $includeKey)
				$appends[] = "PRIMARY KEY ($quotedName)";
			
			return implode(' ', $parts);
		}, $columns);
		
		return $this->prepareList(
				array_merge(
						$items,
						$appends),
				$parentheses);
	}
	
	/**
	 * Prepares a query-ready data type string.
	 * 
	 * @param int $type ColumnTypes Data type.
	 * @param float $length Data type length/size.
	 * 
	 * @throws Exceptions\InvalidDataTypeException
	 * @return string Query-ready data type string.
	 */
	protected function prepareDataType(
			int $type,
			float $length): string
	{
		if (!isset($this->dataTypes[$type]))
			throw new Exceptions\InvalidDataTypeException();
		
		$length = ($length > -1) ?: ($this->dataTypes[$type][1] ?? -1);
		
		return $this->dataTypes[$type][0] . ($length > -1 ? "($length)" : "");
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
				array_filter($values));

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
	 * Composes an UPDATE query to be executed.
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
	
	/**
	 * Composes a DELETE query to be executed.
	 * 
	 * @param Query $query Query instance to build with.
	 * 
	 * @return string Execution-ready query string.
	 */
	public function composeDelete(Query $query): string
	{
		return $this->compose(
				"DELETE FROM " . $this->quoteIdentifier(
						$this->table,
						false),
				$this->prepareWhere($query->where));
	}
	
	/**
	 * Composes a CREATE TABLE (IF NOT EXISTS) query to be executed.
	 * 
	 * @param Query $query Query instance to build with.
	 * 
	 * @return string Execution-ready query string.
	 */
	public function composeCreate(Query $query): string
	{
		return $this->compose(
				"CREATE TABLE IF NOT EXISTS " . $this->quoteIdentifier(
						$this->table,
						false),
				$this->prepareTypedColumns($query->columns));
	}
	
	/**
	 * Composes a CREATE TABLE (IF NOT EXISTS) query to be executed.
	 * 
	 * @param Query $query Query instance to build with.
	 * 
	 * @return string Execution-ready query string.
	 */
	public function composeAlterTable(Query $query): string
	{
		return $this->compose(
				"ALTER TABLE " . $this->quoteIdentifier(
						$this->table,
						false),
				$this->prepareList([
						$this->prepareAddColumns($query->addedColumns),
						$this->prepareAlterColumns($query->columns),
						$this->prepareDropColumns($query->droppedColumns)
				], false));
	}
	
	/**
	 * Composes a DROP TABLE (IF EXISTS) query to be executed.
	 * 
	 * @param Query $query Query instance to build with.
	 * 
	 * @return string Execution-ready query string.
	 */
	public function composeDropTable(Query $query): string
	{
		return $this->compose(
				"DROP TABLE IF EXISTS " . $this->quoteIdentifier(
						$this->table,
						false));
	}
}