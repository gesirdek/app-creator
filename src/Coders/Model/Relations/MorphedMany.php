<?php
/**
 * Created by PhpStorm.
 * User: Öner Zafer
 * Date: 29.05.2018
 * Time: 13:30
 */


namespace Gesirdek\Coders\Model\Relations;

use Illuminate\Support\Str;
use Gesirdek\Support\Dumper;
use Illuminate\Support\Fluent;
use Gesirdek\Coders\Model\Model;
use Gesirdek\Coders\Model\Relation;
use Illuminate\Database\Eloquent\Collection;

class MorphedMany implements Relation
{
    /**
     * @var \Illuminate\Support\Fluent
     */
    protected $parentCommand;

    /**
     * @var \Illuminate\Support\Fluent
     */
    protected $referenceCommand;

    /**
     * @var \Gesirdek\Coders\Model\Model
     */
    protected $parent;

    /**
     * @var \Gesirdek\Coders\Model\Model
     */
    protected $pivot;

    /**
     * @var \Gesirdek\Coders\Model\Model
     */
    protected $reference;

    /**
     * BelongsToMany constructor.
     *
     * @param \Illuminate\Support\Fluent $parentCommand
     * @param \Illuminate\Support\Fluent $referenceCommand
     * @param \Gesirdek\Coders\Model\Model $parent
     * @param \Gesirdek\Coders\Model\Model $pivot
     * @param \Gesirdek\Coders\Model\Model $reference
     */
    public function __construct(
        Model $parent,
        Model $related
    ) {
        $this->parent = $parent;
        $this->reference = $related;
    }

    /**
     * @return string
     */
    public function hint()
    {
        return '\\'.Collection::class.'|'.$this->reference->getQualifiedUserClassName().'[]';
    }

    /**
     * @return string
     */
    public function name()
    {
        if ($this->parent->shouldPluralizeTableName()) {
            if ($this->parent->usesSnakeAttributes()) {
                return Str::snake(Str::plural(Str::singular($this->reference->getTable(true))));
            }

            return Str::camel(Str::plural(Str::singular($this->reference->getTable(true))));
        }
        if ($this->parent->usesSnakeAttributes()) {
            return Str::snake($this->reference->getTable(true));
        }

        return Str::camel($this->reference->getTable(true));
    }

    /**
     * @return string
     */
    public function body()
    {
        $body = '$this->morphToMany(';

        $body .= $this->reference->getClassName()."::class,'".$this->parent->getBlueprint()->getMorphTable()."');";

        return $body;
    }

    /**
     * @return bool
     */
    protected function needsPivotTable()
    {
        $models = [$this->referenceRecordName(), $this->parentRecordName()];
        sort($models);
        $defaultPivotTable = strtolower(implode('_', $models));

        return $this->pivotTable() != $defaultPivotTable || $this->needsForeignKey();
    }

    /**
     * @return mixed
     */
    protected function pivotTable()
    {
        if ($this->parent->getSchema() != $this->pivot->getSchema()) {
            return $this->pivot->getQualifiedTable();
        }

        return $this->pivot->getTable();
    }

    /**
     * @return bool
     */
    protected function needsForeignKey()
    {
        $defaultForeignKey = $this->parentRecordName().'_id';

        return $this->foreignKey() != $defaultForeignKey || $this->needsOtherKey();
    }

    /**
     * @return string
     */
    protected function foreignKey()
    {
        return $this->parentCommand->columns[0];
    }

    /**
     * @return bool
     */
    protected function needsOtherKey()
    {
        $defaultOtherKey = $this->referenceRecordName().'_id';

        return $this->otherKey() != $defaultOtherKey;
    }

    /**
     * @return string
     */
    protected function otherKey()
    {
        return $this->referenceCommand->columns[0];
    }

    private function getPivotFields()
    {
        return array_diff(array_keys($this->pivot->getProperties()), [
            $this->foreignKey(),
            $this->otherKey(),
            $this->pivot->getCreatedAtField(),
            $this->pivot->getUpdatedAtField(),
            $this->pivot->getDeletedAtField(),
            $this->pivot->getPrimaryKey(),
        ]);
    }

    /**
     * @return string
     */
    protected function parentRecordName()
    {
        // We make sure it is snake case because Eloquent assumes it is.
        return Str::snake($this->parent->getRecordName());
    }

    /**
     * @return string
     */
    protected function referenceRecordName()
    {
        // We make sure it is snake case because Eloquent assumes it is.
        return Str::snake($this->reference->getRecordName());
    }

    /**
     * @param array $fields
     *
     * @return string
     */
    private function parametrize($fields = [])
    {
        return (string) implode(', ', array_map(function ($field) {
            $field = $this->reference->usesPropertyConstants()
                ? $this->pivot->getQualifiedUserClassName().'::'.strtoupper($field)
                : $field;

            return Dumper::export($field);
        }, $fields));
    }
}
