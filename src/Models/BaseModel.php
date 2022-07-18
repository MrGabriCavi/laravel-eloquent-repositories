<?php

namespace MrGabriCavi\LaravelEloquentRepositories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class BaseModel
 */
abstract class BaseModel extends Model
{
    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * The key name for uuid field.
     *
     * @var string
     */
    protected $uuid_key_name = 'uuid';

    /**
     * @return string
     */
    public function getUuidKeyName()
    {
        return $this->uuid_key_name;
    }

    /**
     * @return string
     */
    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
