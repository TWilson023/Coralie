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
     * @see DatabaseConnection::init()
     */
    public function init(array $settings): void
    {
        parent::init($settings);
        
        $this->dialectClass = \Coralie\Dialects\MySqlDialect::class;
    }
}