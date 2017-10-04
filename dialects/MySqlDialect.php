<?php

namespace Coralie\Dialects;

use Coralie\Dialect;

/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * MySQL dialect implementation.
 */

class MySqlDialect extends Dialect
{
	protected $identifierQuotes = '`';
}