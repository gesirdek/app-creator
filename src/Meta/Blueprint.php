<?php

/**
 * Created by Cristian.
 * Date: 18/09/16 08:19 PM.
 */

namespace Gesirdek\Meta;

use Illuminate\Support\Fluent;

class Blueprint
{
    /**
     * @var string
     */
    protected $connection;

    /**
     * @var string
     */
    protected $schema;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $morphToMany;

    /**
     * @var \Illuminate\Support\Fluent[]
     */
    protected $columns = [];

    /**
     * @var \Illuminate\Support\Fluent[]
     */
    protected $indexes = [];

    /**
     * @var \Illuminate\Support\Fluent[]
     */
    protected $unique = [];

    /**
     * @var \Illuminate\Support\Fluent[]
     */
    protected $relations = [];

    /**
     * @var \Illuminate\Support\Fluent
     */
    protected $primaryKey;

    /**
     * Blueprint constructor.
     *
     * @param string $connection
     * @param string $schema
     * @param string $table
     */
    public function __construct($connection, $schema, $table, $extras = null)
    {
        $this->connection = $connection;
        $this->schema = $schema;
        $this->table = $table;

        if(count($extras) == 1){
            $this->module = ($extras[0] != "" ? $extras[0] : "app");
            $this->morphToMany = "none";
        }elseif(count($extras) == 2){
            $this->module = $extras[0];
            $this->morphToMany = $extras[1];
        }else{
            $this->module = "app";
            $this->morphToMany = "none";
        }
    }

    /**
     * @return string
     */
    public function schema()
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getModuleStudlyCase()
    {
        return studly_case($this->module);
    }

    /**
     * @return string
     */
    public function qualifiedTable()
    {
        return $this->schema().'.'.$this->table();
    }

    /**
     * @param \Illuminate\Support\Fluent $column
     *
     * @return $this
     */
    public function withColumn(Fluent $column)
    {
        $this->columns[$column->name] = $column;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Fluent[]
     */
    public function columns()
    {
        return $this->columns;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasColumn($name)
    {
        return array_key_exists($name, $this->columns);
    }

    /**
     * @param string $name
     *
     * @return \Illuminate\Support\Fluent
     */
    public function column($name)
    {
        if (! $this->hasColumn($name)) {
            throw new \InvalidArgumentException("Column [$name] does not belong to table [{$this->qualifiedTable()}]");
        }

        return $this->columns[$name];
    }

    /**
     * @param \Illuminate\Support\Fluent $index
     *
     * @return $this
     */
    public function withIndex(Fluent $index)
    {
        $this->indexes[] = $index;

        if ($index->name == 'unique') {
            $this->unique[] = $index;
        }

        return $this;
    }

    /**
     * @return \Illuminate\Support\Fluent[]
     */
    public function indexes()
    {
        return $this->indexes;
    }

    /**
     * @param \Illuminate\Support\Fluent $index
     *
     * @return $this
     */
    public function withRelation(Fluent $index)
    {
        $this->relations[] = $index;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Fluent[]
     */
    public function relations()
    {
        return $this->relations;
    }

    /**
     * @param \Illuminate\Support\Fluent $primaryKey
     *
     * @return $this
     */
    public function withPrimaryKey(Fluent $primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Fluent
     */
    public function primaryKey()
    {
        if ($this->primaryKey) {
            return $this->primaryKey;
        }

        if (! empty($this->unique)) {
            return current($this->unique);
        }

        $nullPrimaryKey = new Fluent();

        return $nullPrimaryKey;
    }

    /**
     * @return bool
     */
    public function hasCompositePrimaryKey()
    {
        return count($this->primaryKey->columns) > 1;
    }

    /**
     * @return string
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * @param string $database
     * @param string $table
     *
     * @return bool
     */
    public function is($database, $table)
    {
        return $database == $this->schema() && $table == $this->table();
    }

    /**
     * @param \Gesirdek\Meta\Blueprint $table
     *
     * @return array
     */
    public function references(Blueprint $table)
    {
        $references = [];

        foreach ($this->relations() as $relation) {
            list($foreignDatabase, $foreignTable) = array_values($relation->on);
            if ($table->is($foreignDatabase, $foreignTable)) {
                $references[] = $relation;
            }
        }

        return $references;
    }

    /**
     * @param \Illuminate\Support\Fluent $constraint
     *
     * @return bool
     */
    public function isUniqueKey(Fluent $constraint)
    {
        $is = false;

        foreach ($this->unique as $index) {
            foreach ($index->columns as $column) {
                $is &= in_array($column, $constraint->columns);
            }
            if ($is) {
                return true;
            }
        }

        return $is;
    }
}
