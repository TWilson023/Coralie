<?php

namespace Coralie\Utils;

class Str
{
	/**
	 * Converts a CamelCase string to snake_case.
	 * 
	 * @param string $camelCase String to convert.
	 * 
	 * @return string Converted string.
	 */
	public static function toSnakeCase(string $camelCase)
	{
		return strtolower(preg_replace(
				'/([^A-Z])([A-Z])/',
				'$1_$2',
				$camelCase));
	}
	
	/**
	 * Converts a snake_case string to CamelCase.
	 * 
	 * @param string $snakeCase String to convert.
	 * 
	 * @return string Converted string.
	 */
	public static function toCamelCase(
			string $snakeCase,
			bool $capitalizeFirst = true)
	{
		$camelCase = implode("", array_map(function ($piece) {
			return ucfirst($piece);
		}, explode(
				'_',
				$snakeCase)));
		
		return $capitalizeFirst ? $camelCase : lcfirst($camelCase);
	}
}
