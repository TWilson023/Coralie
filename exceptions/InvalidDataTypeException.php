<?php
/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Exception thrown when invalid constraints are passed.
 */

namespace Coralie\Exceptions;

class InvalidDataTypeException extends \Exception
{

	public function __construct(
			$message = "Invalid data type specified for column.",
			$code = 0,
			\Exception $previous = null)
	{
		parent::__construct(
				$message,
				$code,
				$previous);
	}

}