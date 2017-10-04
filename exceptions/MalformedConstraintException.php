<?php
/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Exception thrown when invalid constraints are passed.
 */

namespace Coralie\Exceptions;

class MalformedConstraintException extends \Exception
{

	public function __construct(
			$message = "Malformed query constraints",
			$code = 0,
			\Exception $previous = null)
	{
		parent::__construct(
				$message,
				$code,
				$previous);
	}

}