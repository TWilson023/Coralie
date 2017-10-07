<?php

namespace Coralie\Schema;

class Column
{
	/**
	 * Name of the column.
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Column type (e.g. 'integer', 'boolean', etc.)
	 * 
	 * @var string
	 */
	public $type;
	
	/**
	 * Data length/size.
	 * 
	 * @var float
	 */
	public $length;
	
	/**
	 * Column properties (e.g. 'NOT NULL', 'AUTO_INCREMENT').
	 * 
	 * @var array
	 */
	public $properties;
	
	/**
	 * @var bool
	 */
	public $isPrimary;
	
	/**
	 * Whether the column is new (in the context of migrations).
	 * 
	 * @var bool
	 */
	public $isNew;
	
	
	public function __construct(
			string $name,
			?string $type,
			float $length = -1,
			array $properties = [],
			bool $isPrimary = false,
			bool $isNew = true)
	{
		$this->name = $name;
		$this->type = $type;
		$this->length = $length;
		$this->properties = $properties;
		$this->isPrimary = $isPrimary;
		$this->isNew = $isNew;
	}
	
	/**
	 * Sets the column length to the specified value.
	 * 
	 * @param float $length
	 * 
	 * @return Column
	 */
	public function length(float $length): Column
	{
		$this->length = $length;
		return $this;
	}
}