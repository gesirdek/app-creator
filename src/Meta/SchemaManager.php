<?php

/**
 * Created by Cristian.
 * Date: 02/10/16 07:37 PM.
 */

namespace Gesirdek\Meta;

use ArrayIterator;
use RuntimeException;
use IteratorAggregate;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\ConnectionInterface;
use Gesirdek\Meta\MySql\Schema as MySqlSchema;
use Gesirdek\Meta\PgSql\Schema as PgSqlSchema;

class SchemaManager implements IteratorAggregate
{
    /**
     * @var array
     */
    protected static $lookup = [
        MySqlConnection::class => MySqlSchema::class,
        PostgresConnection::class => PgSqlSchema::class,
    ];

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Gesirdek\Meta\Schema[]
     */
    protected $schemas = [];

    /**
     * @var
     */
    protected $config;

    /**
     * SchemaManager constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection, $schema = '', $config = '')
    {
        $this->connection = $connection;
        $this->config = $config;
        $this->boot($schema);
    }

    /**
     * Load all schemas from this connection.
     * @parameter string $schemainfo
     */
    public function boot($schemainfo = '')
    {
        if (! $this->hasMapping()) {
            throw new RuntimeException("There is no Schema Mapper registered for [{$this->type()}] connection.");
        }

        $schemas = forward_static_call([$this->getMapper(), 'schemas'], $this->connection, $this->config);

        foreach ($schemas as $schema) {
            if($schemainfo != ''){
                if($schema == $schemainfo){
                    $this->make($schema);
                }
            }else{
                $this->make($schema);
            }
        }
    }

    /**
     * @param string $schema
     *
     * @return \Gesirdek\Meta\Schema
     */
    public function make($schema)
    {
        if (array_key_exists($schema, $this->schemas)) {
            return $this->schemas[$schema];
        }

        return $this->schemas[$schema] = $this->makeMapper($schema);
    }

    /**
     * @param string $schema
     *
     * @return \Gesirdek\Meta\Schema
     */
    protected function makeMapper($schema)
    {
        $mapper = $this->getMapper();

        if (strpos(static::$lookup[$this->type()], '\\PgSql\\') !== false) {
            return new $mapper($schema, 'public', $this->connection, $this->config);
        }

        return new $mapper($schema, $this->connection, $this->config);
    }

    /**
     * @return string
     */
    protected function getMapper()
    {
        return static::$lookup[$this->type()];
    }

    /**
     * @return string
     */
    protected function type()
    {
        return get_class($this->connection);
    }

    /**
     * @return bool
     */
    protected function hasMapping()
    {
        return array_key_exists($this->type(), static::$lookup);
    }

    /**
     * Register a new connection mapper.
     *
     * @param string $connection
     * @param string $mapper
     */
    public static function register($connection, $mapper)
    {
        static::$lookup[$connection] = $mapper;
    }

    /**
     * Get Iterator for schemas.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->schemas);
    }
}
