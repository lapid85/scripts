<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property string $remark 
 * @property int $sort 
 * @property int $status 
 * @property int $created 
 * @property int $updated 
 */
class Platform extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'platforms';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created' => 'integer', 'updated' => 'integer'];
}
