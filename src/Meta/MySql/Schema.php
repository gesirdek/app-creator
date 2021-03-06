<?php

namespace Gesirdek\Meta\MySql;

use Gesirdek\Coders\Model\RouteCreator;
use Illuminate\Support\Arr;
use Gesirdek\Meta\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

/**
 * Created by Cristian.
 * Date: 18/09/16 06:50 PM.
 */
class Schema implements \Gesirdek\Meta\Schema
{
    /**
     * @var string
     */
    protected $schema;

    /**
     * @var \Illuminate\Database\MySqlConnection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var \Gesirdek\Meta\Blueprint[]
     */
    protected $tables = [];

    /**
     * @var
     */
    protected $config;

    /**
     * @var array
     */
    protected $moduleNames = [];

    /**
     * Mapper constructor.
     *
     * @param string $schema
     * @param \Illuminate\Database\MySqlConnection $connection
     */
    public function __construct($schema, $connection, $config)
    {
        $this->schema = $schema;
        $this->connection = $connection;
        $this->config = $config;

        $this->load();
    }

    /**
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     * @todo: Use Doctrine instead of raw database queries
     */
    public function manager()
    {
        return $this->connection->getDoctrineSchemaManager();
    }

    /**
     * Loads schema's tables' information from the database.
     */
    protected function load()
    {
        $tables = array_diff($this->fetchTables($this->schema),  $this->config->getKey('except'));
        $extras = [];
        foreach ($tables as $table) {
            $extras = explode(";", $this->fetchTableComments($this->schema, $table));
            $moduleName = "";
            if (is_array($extras) && !empty($extras)) {
                $moduleName = $extras[0];
            } else {
                $moduleName = $extras;
            }

            if ($moduleName != "" && $moduleName != null && in_array($moduleName, $this->moduleNames) == false) {
                $this->moduleNames[] = $moduleName;
            }
        }

        new RouteCreator($this->moduleNames);

        foreach ($tables as $table) {
            $blueprint = new Blueprint($this->connection->getName(), $this->schema, $table, explode(";", $this->fetchTableComments('public', $table)));
            RouteCreator::addContent($blueprint);
            $this->fillColumns($blueprint);
            $this->fillConstraints($blueprint);
            $this->tables[$table] = $blueprint;
        }

        RouteCreator::clearExtras($this->moduleNames);
    }

    /**
     * @param string $schema
     *
     * @return array
     */
    protected function fetchTables($schema)
    {
        $rows = $this->arraify($this->connection->select('SHOW FULL TABLES FROM '.$this->wrap($schema).' WHERE Table_type="BASE TABLE"'));
        $names = array_column($rows, 'Tables_in_'.$schema);

        return Arr::flatten($names);
    }

    /**
     * @param string $schema
     *
     * @return array
     */
    public function fetchTableComments($schema, $table)
    {
        $rows = $this->arraify($this->connection
            ->select('SELECT `TABLE_COMMENT` FROM information_schema.tables  
            WHERE `tables`.`TABLE_SCHEMA` = \''.$schema.'\'
            AND `TABLE_NAME` = \''.$table.'\''));
        if(count($rows))
            return $rows[0]['TABLE_COMMENT'];
        return '';
    }

    /**
     * @param \Gesirdek\Meta\Blueprint $blueprint
     */
    protected function fillColumns(Blueprint $blueprint)
    {
        $rows = $this->arraify($this->connection->select('SHOW FULL COLUMNS FROM '.$this->wrap($blueprint->qualifiedTable())));
        foreach ($rows as $column) {
            $blueprint->withColumn(
                $this->parseColumn($column)
            );
        }
    }

    /**
     * @param array $metadata
     *
     * @return \Illuminate\Support\Fluent
     */
    protected function parseColumn($metadata)
    {
        return (new Column($metadata))->normalize();
    }

    /**
     * @param \Gesirdek\Meta\Blueprint $blueprint
     */
    protected function fillConstraints(Blueprint $blueprint)
    {
        $row = $this->arraify($this->connection->select('SHOW CREATE TABLE '.$this->wrap($blueprint->qualifiedTable())));
        $row = array_change_key_case($row[0]);
        $sql = $row['create table'];
        $sql = str_replace('`', '', $sql);

        $this->fillPrimaryKey($sql, $blueprint);
        $this->fillIndexes($sql, $blueprint);
        $this->fillRelations($sql, $blueprint);
    }

    /**
     * Quick little hack since it is no longer possible to set PDO's fetch mode
     * to PDO::FETCH_ASSOC.
     *
     * @param $data
     * @return mixed
     */
    protected function arraify($data)
    {
        return json_decode(json_encode($data), true);
    }

    /**
     * @param string $sql
     * @param \Gesirdek\Meta\Blueprint $blueprint
     * @todo: Support named primary keys
     */
    protected function fillPrimaryKey($sql, Blueprint $blueprint)
    {
        $pattern = '/\s*(PRIMARY KEY)\s+\(([^\)]+)\)/mi';
        if (preg_match_all($pattern, $sql, $indexes, PREG_SET_ORDER) == false) {
            return;
        }

        $key = [
            'name' => 'primary',
            'index' => '',
            'columns' => $this->columnize($indexes[0][2]),
        ];

        $blueprint->withPrimaryKey(new Fluent($key));
    }

    /**
     * @param string $sql
     * @param \Gesirdek\Meta\Blueprint $blueprint
     */
    protected function fillIndexes($sql, Blueprint $blueprint)
    {
        $pattern = '/\s*(UNIQUE)?\s*(KEY|INDEX)\s+(\w+)\s+\(([^\)]+)\)/mi';
        if (preg_match_all($pattern, $sql, $indexes, PREG_SET_ORDER) == false) {
            return;
        }

        foreach ($indexes as $setup) {
            $index = [
                'name' => strcasecmp($setup[1], 'unique') === 0 ? 'unique' : 'index',
                'columns' => $this->columnize($setup[4]),
                'index' => $setup[3],
            ];
            $blueprint->withIndex(new Fluent($index));
        }
    }

    /**
     * @param string $sql
     * @param \Gesirdek\Meta\Blueprint $blueprint
     * @todo: Support named foreign keys
     */
    protected function fillRelations($sql, Blueprint $blueprint)
    {
        $pattern = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
        preg_match_all($pattern, $sql, $relations, PREG_SET_ORDER);

        foreach ($relations as $setup) {
            $table = $this->resolveForeignTable($setup[2], $blueprint);

            $relation = [
                'name' => 'foreign',
                'index' => '',
                'columns' => $this->columnize($setup[1]),
                'references' => $this->columnize($setup[3]),
                'on' => $table,
            ];

            $blueprint->withRelation(new Fluent($relation));
        }
    }

    /**
     * @param string $columns
     *
     * @return array
     */
    protected function columnize($columns)
    {
        return array_map('trim', explode(',', $columns));
    }

    /**
     * Wrap within backticks.
     *
     * @param string $table
     *
     * @return string
     */
    protected function wrap($table)
    {
        $pieces = explode('.', str_replace('`', '', $table));

        return implode('.', array_map(function ($piece) {
            return "`$piece`";
        }, $pieces));
    }

    /**
     * @param string $table
     * @param \Gesirdek\Meta\Blueprint $blueprint
     *
     * @return array
     */
    protected function resolveForeignTable($table, Blueprint $blueprint)
    {
        $referenced = explode('.', $table);

        if (count($referenced) == 2) {
            return [
                'database' => current($referenced),
                'table' => next($referenced),
            ];
        }

        return [
            'database' => $blueprint->schema(),
            'table' => current($referenced),
        ];
    }

    /**
     * @param \Illuminate\Database\Connection $connection
     *
     * @return array
     */
    public static function schemas(Connection $connection)
    {
        $schemas = $connection->getDoctrineSchemaManager()->listDatabases();

        return array_diff($schemas, [
            'information_schema',
            'sys',
            'mysql',
            'performance_schema',
        ]);
    }

    /**
     * @return string
     */
    public function schema()
    {
        return $this->schema;
    }

    /**
     * @param string $table
     *
     * @return bool
     */
    public function has($table)
    {
        return array_key_exists($table, $this->tables);
    }

    /**
     * @return \Gesirdek\Meta\Blueprint[]
     */
    public function tables()
    {
        return $this->tables;
    }

    /**
     * @param string $table
     *
     * @return \Gesirdek\Meta\Blueprint
     */
    public function table($table)
    {
        if (! $this->has($table)) {
            throw new \InvalidArgumentException("Table [$table] does not belong to schema [{$this->schema}]");
        }

        return $this->tables[$table];
    }

    /**
     * @return \Illuminate\Database\MySqlConnection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * @param \Gesirdek\Meta\Blueprint $table
     *
     * @return array
     */
    public function referencing(Blueprint $table)
    {
        $references = [];

        foreach ($this->tables as $blueprint) {
            foreach ($blueprint->references($table) as $reference) {
                $references[] = [
                    'blueprint' => $blueprint,
                    'reference' => $reference,
                ];
            }
        }

        return $references;
    }
}
