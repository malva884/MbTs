<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\SqlServerConnector as BaseSqlServerConnector;
use PDO;

class SqlServerConnector extends BaseSqlServerConnector
{
    /**
     * Create a new database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);
        
        // Add SSL options for ODBC Driver 18 to bypass certificate validation
        if (str_contains($dsn, 'sqlsrv:')) {
            $dsn .= ';Encrypt=no;TrustServerCertificate=1';
        }
        
        // Pass empty options array to avoid invalid PDO attributes
        $options = [];
        
        return $this->createConnection($dsn, $config, $options);
    }
}
