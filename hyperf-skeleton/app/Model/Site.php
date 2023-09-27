<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $platform_id 
 * @property string $name 
 * @property string $remark 
 * @property int $status 
 * @property int $sort 
 * @property string $urls 
 * @property string $api 
 * @property string $admin_url 
 * @property string $admin_api 
 * @property int $created 
 * @property int $updated 
 * @property string $code 
 */
class Site extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sites';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'platform_id' => 'integer', 'status' => 'integer', 'sort' => 'integer', 'created' => 'integer', 'updated' => 'integer'];
}
