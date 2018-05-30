<?php namespace SimpleMySQL;

use PDO;

class Database
{
    /**
     * @var PDO|null
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $defaultOptions = [
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ];

    /**
     * @param $host
     * @param $database
     * @param $user
     * @param $pass
     * @param int $port
     * @param array $options
     */
    public function __construct($host, $database, $username, $password, $port = 3306, $options = [])
    {
        $options = array_merge([], $this->defaultOptions, $options);

        $config = compact('host', 'database', 'username', 'password', 'port');

        $dns = $this->getDsn($config);

        try {
            $this->pdo = new PDO($dns, $username, $password);

            // Select database
            if (! empty($config['database'])) {
                $this->pdo->exec("use `{$database}`;");
            }

            // Set config encoding
            $this->pdo->prepare("set names '{$options['charset']}' collate '{$options['collation']}'")->execute();

            // Config timezone
            if (isset($options['timezone'])) {
                $this->pdo->prepare('set time_zone="' . $options['timezone'] . '"')->execute();
            }
        } catch (\Exception $e) {
            $this->pdo = null;

            throw new \Exception('Database build failed: ' . $e->getMessage());
        }
    }

    /**
     * @param $string
     * @param array $bindings
     * @return array
     */
    public function query($query, $bindings = [])
    {
        $sta = $this->pdo->prepare($query);

        $this->bindValues($sta, $bindings);

        $sta->execute();

        return $sta->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @param $query
     * @param array $bindings
     * @return mixed
     */
    public function queryOne($query, $bindings = [])
    {
        $sta = $this->pdo->prepare($query);

        $this->bindValues($sta, $bindings);

        $sta->execute();

        return $sta->fetch(PDO::FETCH_OBJ);
    }

    /**
     * @param $query
     * @param array $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * @param $query
     * @param array $bindings
     * @return bool
     */
    public function update($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * @param $query
     * @param array $bindings
     * @return bool
     */
    public function delete($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * @param $query
     * @param array $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        $sta = $this->pdo->prepare($query);

        $this->bindValues($sta, $bindings);

        return $sta->execute();
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Create a DSN string from a configuration.
     *
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        $dsn = "mysql:host={$host};dbname={$database}";

        if (isset($port)) {
            $dsn .= ";port={$port}";
        }

        return $dsn;
    }
}