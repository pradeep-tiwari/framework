<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Pdo;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;

class Model
{
    protected $table;
    protected $data = [];
    protected $connection;

    public function __construct(string $table, Pdo $connection = null)
    {
        $this->table = $table;
        $this->table = $table;
        $this->data = new \stdClass();
        $this->connection = $connection ?? app('db');
    }

    public function __set($column, $value)
    {
        if(!method_exists($this, $column)) {
            $this->data->$column = $value;
        }
    }

    public function __get($column)
    {
        if(method_exists($this, $column)) {
            return $this->{$column}();
        }

        return $this->data->$column ?? null;
    }

    public function setConnection(Pdo $connection)
    {
        $this->connection = $connection;
    }

    public function hasOne(string $model, string $foreignKey) 
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->id);
    }

    public function hasMany(string $model, string $foreignKey) 
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->id);
    }

    public function belongsTo(string $model, string $foreignKey)
    {
        $model = $this->connection->model($model);
        return $model->query()->where('id', '=', $this->data->{$foreignKey}); 
    }

    public function pivot(string $model, string $pivot, string $foreignKey, string $associateKey)
    {
        $model = $this->connection->model($model);
        return $model
                    ->query()
                    ->select(["$model->table.*"])
                    ->join($pivot, "$model->table.id", "$pivot.$associateKey")
                    ->where("$pivot.$foreignKey", '=', $this->id);
    }

    public function find(int $id)
    {
        $this->data = $this->connection->table($this->table)->where('id', '=', $id)->fetchOne();

        if(!$this->data) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        return $this;
    }

    public function save()
    {
        if(isset($this->data->id)) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    public function delete()
    {
        if(! isset($this->data->id)) {
            return false;
        }

        $this->connection->table($this->table)->delete(['id', $this->data->id]);
    }

    public function query()
    {
        return new Query($this->table, $this->connection);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    private function insert()
    {
        $data = \get_object_vars($this->data);
        $this->connection->table($this->table)->insert($data);
    }

    private function update()
    {
        $where = 'id = ' . (int) $this->data->id;
        $data = \get_object_vars($this->data);
        unset($data['id']);
        $this->connection->table($this->table)->update(['id', $this->data->id], $data);
    }
}