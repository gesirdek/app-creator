<?php

namespace Gesirdek\Meta\PgSQL;

use Gesirdek\Coders\Model\Config;
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
    protected $database;

    /**
     * @var string
     */
    //protected $schema;

    /**
     * @var \Illuminate\Database\PostgresConnection
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
     * @param \Illuminate\Database\PostgresConnection $connection
     */
    public function __construct($database, $schema, $connection, $config)
    {
        $this->database = $database;
        $this->schema =  $schema;
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
            $blueprint = new Blueprint($this->connection->getName(), $this->database, $table, explode(";", $this->fetchTableComments($this->schema, $table)));
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
        $rows = $this->arraify($this->connection->select('SELECT tablename FROM pg_tables WHERE schemaname = \''.$schema.'\''));
        $names = array_column($rows, 'tablename');

        return Arr::flatten($names);
    }

    /**
     * @param string $schema
     *
     * @return array
     */
    public function fetchTableComments($schema, $table)
    {
        $rows = $this->arraify($this->connection->select('SELECT obj_description(\''.$schema.'.'.$table.'\'::regclass)'));
        return $rows[0]['obj_description'];
    }

    /**
     * @param \Gesirdek\Meta\Blueprint $blueprint
     */
    protected function fillColumns(Blueprint $blueprint)
    {
        $rows = $this->arraify($this->connection->select('SELECT * FROM information_schema.columns WHERE table_name = \''.$blueprint->table().'\''));

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
        $rows = $this->arraify($this->connection->select('SELECT f.attnum AS number, f.attname AS name, f.attnum, f.attnotnull AS notnull, pg_catalog.format_type(f.atttypid,f.atttypmod) AS type, 
    CASE 
        WHEN p.contype = \'p\' THEN \'t\'  
        ELSE \'f\'  
    END AS primarykey,  
    CASE  
        WHEN p.contype = \'u\' THEN \'t\'  
        ELSE \'f\'
    END AS uniquekey,
    CASE
        WHEN p.contype = \'f\' THEN g.relname
        ELSE \'-\'
    END AS foreignkey,
    CASE
        WHEN p.contype = \'f\' THEN p.confkey
    END AS foreignkey_fieldnum,
    CASE
        WHEN p.contype = \'f\' THEN g.relname
    END AS foreignkey,
    CASE
        WHEN p.contype = \'f\' THEN p.conkey
    END AS foreignkey_connnum,
    CASE
        WHEN f.atthasdef = \'t\' THEN d.adsrc
    END AS default
FROM pg_attribute f  
    JOIN pg_class c ON c.oid = f.attrelid  
    JOIN pg_type t ON t.oid = f.atttypid  
    LEFT JOIN pg_attrdef d ON d.adrelid = c.oid AND d.adnum = f.attnum  
    LEFT JOIN pg_namespace n ON n.oid = c.relnamespace  
    LEFT JOIN pg_constraint p ON p.conrelid = c.oid AND f.attnum = ANY (p.conkey)  
    LEFT JOIN pg_class AS g ON p.confrelid = g.oid  
WHERE c.relkind = \'r\'::char  
    AND n.nspname = \'public\'  -- Replace with Schema name  
    AND c.relname = \''.$blueprint->table().'\'  -- Replace with table name  
    AND f.attnum > 0 ORDER BY number'));

        $primaryKeys = array();
        $uniqueKeys = array();
        $foreignKeys = array();
        $foreignKeyTables = array();
        $foreignTableColumns = array();
        $morphedByTables = array();
        //$morphToTable = "";
        $tableComments = array();


        $tableComments = explode(';',$this->fetchTableComments($this->schema,$blueprint->table()));
        if(isset($tableComments[1])){
            if($tableComments[1] != null && $tableComments[1] != ''){
                //$morphToTable = explode(',', explode('|', $tableComments[1])[0]);
                $morphedByTables = explode(',', explode('|', $tableComments[1])[1]);
            }
        }

        foreach ($rows as $row){
            if($row['primarykey'] == 't'){
                $primaryKeys[] = $row['name'];
            }

            if($row['uniquekey'] == 't'){
                $uniqueKeys[] = $row['name'];
            }

            if($row['foreignkey'] != '-' && $row['foreignkey'] != null){
                $foreignKeys[] = $row['name'];
                $foreignKeyTables[] = $row['foreignkey'];
                $foreignTableColumns[] =$row['foreignkey_fieldnum'];
            }
        }
        if(count($primaryKeys) > 0){
            $this->fillPrimaryKey($primaryKeys, $blueprint);
        }
        if(count($uniqueKeys) > 0){
            $this->fillIndexes($uniqueKeys, $blueprint);
        }

        if(count($foreignKeys) > 0){
            $this->fillRelations($foreignKeys, $foreignKeyTables, $foreignTableColumns, $blueprint);
        }

        if(count($morphedByTables) > 0){
            $this->fillMorphRelations($morphedByTables, $blueprint);
        }

    }

    /**
     * @param $referenceid, $table
     * @return mixed
     */
    protected function getReference($referenceid, $table)
    {
        $rows = $this->arraify($this->connection->select('SELECT * FROM information_schema.columns WHERE table_name = \''.$table.'\''));
        $counter = 0;
        foreach ($rows as $column) {
            $counter++;
            if($counter == $referenceid){
                return $column['column_name'];
            }
        }

        return "";
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
    protected function fillPrimaryKey($columnNames, Blueprint $blueprint)
    {
        $key = [
            'name' => 'primary',
            'index' => '',
            'columns' => $columnNames,
        ];

        $blueprint->withPrimaryKey(new Fluent($key));
    }

    /**
     * @param string $sql
     * @param \Gesirdek\Meta\Blueprint $blueprint
     */
    protected function fillIndexes($columnNames, Blueprint $blueprint)
    {
        $index = [
            'name' => 'unique',
            'columns' => $columnNames,
            'index' => '',
        ];
        $blueprint->withIndex(new Fluent($index));
    }

    /**
     * @param string $sql
     * @param \Gesirdek\Meta\Blueprint $blueprint
     * @todo: Support named foreign keys
     */
    protected function fillRelations($columnNames, $tableNames, $referenceids, Blueprint $blueprint)
    {
        $combinedTables = array();
        $combinedColumns = array();
        $combinedReferences = array();
        foreach ($tableNames as $index => $tableName){
            if(in_array($tableName,$combinedTables)){
                $arrayindex = array_search($tableName, $combinedTables);
                $combinedColumns[$arrayindex] = $combinedColumns[$arrayindex].','.$columnNames[$index];
                $combinedReferences[$arrayindex] = $combinedReferences[$arrayindex].','.$this->getReference($referenceids[$index], $tableName);
            }else{
                $combinedTables[] = $tableName;
                $combinedColumns[] = ",".$columnNames[$index];
                if(!isset($referenceids[$index])){
                    continue;
                }
                $combinedReferences[] = $this->getReference($referenceids[$index], $tableName);
            }
        }


        foreach ($combinedTables as $index => $combinedTable) {
            if(!isset($combinedReferences[$index])){
                unset($combinedTables[$index]);
                continue;
            }
            $table = $this->resolveForeignTable($combinedTables[$index], $blueprint);

            $relation = [
                'name' => 'foreign',
                'index' => '',
                'columns' => $this->columnize(substr($combinedColumns[$index],1)),
                'references' => $this->columnize($combinedReferences[$index]),
                'on' => $table,
            ];

            $blueprint->withRelation(new Fluent($relation));
        }
    }

    /**
     * @param string $sql
     * @param \Gesirdek\Meta\Blueprint $blueprint
     * @todo: Support named foreign keys
     */
    protected function fillMorphRelations($morphBy, Blueprint $blueprint)
    {

        $refTables = array();
        $refColumns = array();
        foreach ($morphBy as $index => $morphByTable) {
            $refTables[] = explode('.',$morphByTable)[0];
            $refColumns[] = explode('.',$morphByTable)[1];

            $table = $this->resolveForeignTable($refTables[$index], $blueprint);

            $relation = [
                'name' => 'morph',
                'index' => '',
                'columns' => $this->columnize($refColumns[$index]),
                'references' => $this->columnize($refTables[$index]),
                'on' => $table,
            ];

            $blueprint->withRelation(new Fluent($relation));
        }




        //dd($blueprint->relations());
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
            'pgsql',
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
     * @return \Illuminate\Database\PostgresConnection
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
