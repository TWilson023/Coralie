<?php

namespace Coralie\Schema;

use Coralie\Coralie;

abstract class Migration
{
	/**
	 * Whether the database should be modified upon running the migration.
	 * 
	 * @var bool
	 */
	protected static $modify = false;
	
	/**
	 * Run migrations.
	 */
	public abstract function up(): void;
	
	/**
	 * Reverse migrations.
	 */
	public abstract function down(): void;
	
	/**
	 * Runs the migration.
	 * 
	 * @param bool $up Whether the migration is being run forward.
	 * @param bool $modify
	 */
	public function runMigration(
			bool $up = true,
			bool $modify = true): void
	{
		static::$modify = $modify;
		
		$this->{$up ? 'up' : 'down'}();
	}
	
	/**
	 * Uses a callable to make changes to a Table.
	 * 
	 * @see Table::with()
	 */
	protected function table(
			string $name,
			callable $callable): void
	{
		Table::with(
				$name,
				$callable);
		
		if (static::$modify)
		{
			$table = Table::get($name);
			
			// TODO:  Run create/update query
			if ($table->isNew)
			{
				Coralie::$connection->table($name)
						->createTable($table->columns)->execute();
				$table->isNew = false;
			}
			else
			{
				$toAdd = [];
				$toAlter = [];
				
				foreach ($table->columns as $column)
				{
					if ($column->isNew)
					{
						$toAdd[] = $column;
						$column->isNew = false;
					}
					else
					{
						$toAlter[] = $column;
					}
				}
				
				Coralie::$connection->table($name)
						->alterTable(
								$toAdd,
								$toAlter,
								$table->droppedColumns)
						->execute();
			}
			
			foreach ($table->columns as $column)
				$column->isNew = false;
		}
	}
	
	/**
	 * Drops a table from the database (may result in data loss!).
	 * 
	 * @see Table::drop()
	 */
	protected function dropTable(string $name)
	{
		Table::drop($name);
		
		if (static::$modify)
			Coralie::$connection->table($name)->dropTable()->execute();
	}
}