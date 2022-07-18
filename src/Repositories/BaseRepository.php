<?php

namespace MrGabriCavi\LaravelEloquentRepositories\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Throwable;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @param $model
     * @throws Exception
     */
    public function __construct($model = null)
    {
        $this->setModel($model ?? $this->model());
    }

    /**
     * @param $name
     * @param $arguments
     * @return Builder|Model|void
     */
    public function __call($name, $arguments)
    {
        if (Str::startsWith($name,'searchBy')) {
            $builder = $arguments[1] ?? $this->newQuery();
            $attribute = Str::snake(Str::replaceFirst('searchBy','',$name));
            return $builder->where($attribute, $arguments[0])->get();
        }
        if (Str::startsWith($name,'findBy')) {
            $attribute = Str::snake(Str::replaceFirst('findBy','',$name));
            return $this->newQuery()->firstWhere($attribute, $arguments[0]);
        }
        if (Str::startsWith($name,'findOrFailBy')) {
            $attribute = Str::snake(Str::replaceFirst('findOrFailBy','',$name));
            return $this->newQuery()->where($attribute, $arguments[0])->firstOrFail();
        }
    }

    /**
     * @return Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * @param Model$model
     * @return void
     * @throws Exception
     */
    public function setModel($model)
    {
        if (empty($model)) {
            throw new Exception("Model not initialized");
        }
        $model = App::make($model);
        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getModelTable()
    {
        return $this->model()->getTable();
    }

    /**
     * @return Builder
     */
    public function queryBuilder()
    {
        return $this->model()->newQuery();
    }

    /**
     * @param $id
     * @return Model|null
     */
    public function find($id)
    {
        $q = $this->queryBuilder();
        return $q->firstWhere($this->idField($id),$id);
    }

    /**
     * @param $id
     * @return Model
     */
    public function findOrFail($id)
    {
        $q = $this->queryBuilder();
        return $q->where($this->idField($id),$id)->firstOrFail();
    }

    /**
     * @param $attributes
     * @return Model
     */
    public function store($attributes)
    {
        $model = $this->model()->newInstance($attributes);
        $model->save();
        return $model;
    }

    /**
     * @param $target
     * @param $attributes
     * @return Model
     */
    public function update($target, $attributes)
    {
        if ($target instanceof $this->model()::class) {
            $model = $target;
        } else {
            $model = $this->findOrFail($target);
        }
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    /**
     * @param $target
     * @return null
     * @throws Throwable
     */
    public function delete($target)
    {
        if ($target instanceof $this->model()::class) {
            $model = $target;
        } else {
            $model = $this->findOrFail($target);
        }
        $model->deleteOrFail();
        return null;
    }

    /**
     * @param $filter
     * @param $sort
     * @return void
     */
    public function index($filter = [], $sort = [])
    {
        collect($filter)->map(function ($info){
            $this->model()->where($info[0], $info[1]);
        });
        return $this->model()->get();
    }

    /**
     * @param $filter
     * @param $sort
     * @return void
     */
    public function paginatedIndex($limit = 25, $filter = [], $sort = [])
    {
        collect($filter)->map(function ($info){
            $this->model()->where($info[0], $info[1]);
        });
        return $this->model()->paginate($limit);
    }

    /**
     * @param $id
     * @return string
     */
    protected function idField($id)
    {
        return Str::isUuid($id) ? $this->model()->getUuidKeyName() : $this->model()->getKeyName();
    }
}
