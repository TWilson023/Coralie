<?php

namespace Coralie;

use Coralie\Schema\Table;

abstract class Model
{
	/**
	 * @var DatabaseConnection
	 */
	public static $connection;
	
	/**
	 * Table name.
	 * 
	 * @var string
	 */
	protected static $table;
	
	/**
	 * Corresponding database table.
	 * 
	 * @var Table
	 */
	private static $tableSchema;
	
	/**
	 * Name of the primary key.
	 * 
	 * @var string
	 */
	protected $primaryKey = 'id';
	
	/**
	 * Whether the model *doesn't* currently match its database record.
	 * 
	 * @var bool
	 */
	protected $isDirty = false;
	
	/**
	 * Whether the model is currently attached to the database.
	 * 
	 * @var string
	 */
	protected $isAttached = false;
	
	/**
	 * Model attributes (set and retrieved by magic methods).
	 * 
	 * @var array
	 */
	protected $attributes = [];
	
	/**
	 * @param array $attributes Initial model attributes.
	 * 
	 * @see Model::assign()
	 */
	public function __construct(array $attributes = null)
	{
		static::init();
		$this->assign($attributes);
	}
	
	/**
	 * Initializes static members.
	 */
	protected static function init(): void
	{
		if (!static::$table)
			static::$table = end(explode(
							'\\',
							Utils\String::toSnakeCase(get_called_class())));
		
		if (!static::$tableSchema)
			static::$tableSchema = Table::get(static::$table);
		
		if (!static::$connection)
			static::$connection = Coralie::$connection;
	}
	
	/**
	 * Assigns model attributes.
	 * 
	 * @param array $attributes Associative array of attributes with values.
	 */
	public function assign(array $attributes): void
	{
		// TODO:  Validate columns with Table::getColumn()
		
		// Merge and override current attributes
		$this->attributes = array_merge(
				$this->attributes,
				$attributes);
		
		$this->isDirty = true;
	}
	
	/**
	 * Magic method for assigning an attribute value.
	 * 
	 * @param string $name Name of the attribute.
	 * @param mixed $value Assignment value.
	 */
	public function __set(
			string $name,
			$value): void
	{
		static::init();
		if (array_key_exists(
				$name,
				$this->attributes) || static::$table->getColumn($name) !== null)
		{
			$this->attributes[$name] = $value;
			$this->isDirty = true;
		}
		
		// TODO:  Throw exception if attribute could not be set.
	}
}