<?php

namespace Coralie\Connections;

use Coralie\DatabaseConnection;

/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * MySQL connection implementation.
 */

class MySqlConnection extends DatabaseConnection
{
    /**
     * @see DatabaseConnection::__construct()
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        
        $this->dialectClass = \Coralie\Dialects\MySqlDialect::class;
    }
}