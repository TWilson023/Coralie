<?php

namespace Coralie;

use Coralie\Connections\MySqlConnection;
use Coralie\Schema\Column;
use Coralie\Schema\ColumnTypes;

class Coralie
{
	/**
	 * Database connection.
	 * 
	 * @var DatabaseConnection
	 */
	public static $connection;
	
	/**
	 * Disable constructor, this is all static.
	 * TODO:  Revisit this decision.
	 */
	private function __construct() {}
	
	/**
	 * Initializes Coralie ORM with specified settings array.
	 * 
	 * @param array $settings Associative array of settings.
	 * 
	 * @return bool Successful initialization and connection.
	 */
	public static function init(array $settings): bool
	{
		// Override defaults with passed settings
		$settings = array_merge([
				'type' => 'mysql',
				'host' => 'localhost',
				'database' => 'coralie',
				'username' => 'root',
				'password' => "",
				'charset' => 'utf8',
				
				'migrations' => __DIR__ . "/../../migrations"
		], $settings);
		
		// TODO:  Multiple connection types
		static::$connection = new MySqlConnection($settings);
		
		static::runMigrations($settings['migrations']);
		
		// TODO:  Validate successful connection/migrations
		return true;
	}
	
	/**
	 * Runs migrations from the directory specified in Coralie::init().
	 * 
	 * @param string $directory Path to the migrations directory.
	 * @param bool $up (optional) Whether migrations are being run forward
	 * 		  (default:  true).
	 */
	protected static function runMigrations(
			string $directory,
			bool $up = true): void
	{
		// TODO:  Non-automatic migrations
		
		// Uses "IF NOT EXISTS"
		static::$connection->table('coralie_migrations')->createTable([
				new Column(
						'id',
						ColumnTypes::INTEGER,
						-1,
						['AUTO_INCREMENT'],
						true),
				
				new Column(
						'name',
						ColumnTypes::VARCHAR)
		])->execute();
		
		foreach (glob(rtrim(
				$directory,
				'\\/') . "/*.php") as $filename)
		{
			$migrationName = explode(
					'_',
					basename(
							$filename,
							".php"),
					2)[1];
			
			// Check if migration has already been run
			$alreadyRun = count(static::$connection->table('coralie_migrations')
						->select('name')->where('name', $migrationName)
						->limit(1)->execute());
			
			$className = Utils\Str::toCamelCase($migrationName);
			include $filename;
			
			if (!class_exists($className)) continue; // TODO:  Throw exception
			
			$shouldModify = !$alreadyRun || !$up;
			
			// Run up/down method, modifying if it hasn't been run or is down.
			(new $className())->runMigration(
					$up,
					$shouldModify);
			
			if ($shouldModify)
				static::$connection->table('coralie_migrations')->insert([
						'name' => $migrationName
				])->execute();
		}
		
	}
	
}