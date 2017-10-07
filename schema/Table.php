<?php

namespace Coralie\Schema;

class Table
{
	/**
	 * Existing Table array
	 * 
	 * @var Table[]
	 */
	private static $tables = [];
	
	/**
	 * Dropped Tables array
	 * 
	 * @var Table[]
	 */
	private static $dropped = [];
	
	/**
	 * Table name.
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Whether the table is new (in the context of migrations).
	 * 
	 * @var bool
	 */
	public $isNew;
	
	/**
	 * Associative column array.
	 * 
	 * @var Column[]
	 */
	public $columns = [];
	
	/**
	 * Associative array of dropped columns
	 * 
	 * @var Column[]
	 */
	public $droppedColumns = [];
	
	public function __construct(
			string $name,
			bool $isNew = false)
	{
		$this->name = $name;
		$this->isNew = $isNew;
	}
	
	/**
	 * Adds a primary key to the table.
	 * 
	 * @param string $name Name of the column.
	 * @param int $type (optional) Column data type
	 * 		  (default:  ColumnTypes::INTEGER).
	 * @param bool $autoIncrement (optional) Whether the column should be auto-
	 * 		  incremented (default:  true).
	 * 
	 * @return Column
	 */
	public function addPrimary(
			string $name,
			bool $autoIncrement = true): Column
	{
		return $this->columns[] = new Column(
				$name,
				ColumnTypes::INTEGER,
				-1,
				$autoIncrement ? [] : ['AUTO INCREMENT'],
				true);
	}
	
	/**
	 * Adds an integer column to the table.
	 * 
	 * @param string $name Name of the column.
	 * 
	 * @return Column
	 */
	public function addInteger(string $name): Column
	{
		return $this->columns[] = new Column(
				$name,
				ColumnTypes::INTEGER);
	}
	
	/**
	 * Adds a string/varchar column to the table.
	 * 
	 * @param string $name Name of the column.
	 * @param float $length (optional) column size/length.
	 * 
	 * @return Column
	 */
	public function addString(
			string $name,
			float $length = -1): Column
	{
		return $this->columns[] = new Column(
				$name,
				ColumnTypes::VARCHAR,
				$length);
	}
	
	/**
	 * @param string $column Name of the column.
	 * 
	 * @return Column|null Corresponding column, or null if none found.
	 */
	public function getColumn(string $column): ?Column
	{
		return $this->columns[$column] ?? null;
	}
	
	/**
	 * @param string $column Name of the column.
	 */
	public function dropColumn(string $column): void
	{
		if (array_key_exists(
				$column,
				$this->columns))
		{
			$this->droppedColumns[$column] = $this->columns[$column];
			unset($this->columns[$column]);
		}
		else
		{
			$this->droppedColumns = new Column(
					$column,
					null);
		}
	}
	
	/**
	 * Use the specified table in a callable function to perform operations.
	 * 
	 * @param string $table Name of the table.
	 * @param callable $callable Function to receive Table argument.
	 */
	public static function with(
			string $table,
			callable $callable): void
	{
		$callable(static::get($table));
	}
	
	public static function get(string $table): Table
	{
		if (!array_key_exists(
				$table,
				static::$tables))
			return static::$tables[$table] = new static(
					$table,
					true);
			
		return static::$tables[$table];
	}
	
	/**
	 * Drops the specified table from the database (may result in data loss!)
	 * 
	 * @param string $table Name of the table.
	 */
	public static function drop(string $table)
	{
		if (array_key_exists(
				$table,
				static::$tables))
		{
			static::$dropped[$table] = static::$tables[$table];
			unset(static::$tables[$table]);
		}
		else
			static::$dropped[$table] = new static($table);
	}
}