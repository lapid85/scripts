<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $platform_id 
 * @property int $site_id 
 * @property string $name 
 * @property int $status 
 * @property string $value 
 * @property string $remark 
 * @property int $sort 
 * @property int $created 
 * @property int $updated 
 */
class SiteConfig extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'site_configs';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'platform_id' => 'integer', 'site_id' => 'integer', 'status' => 'integer', 'sort' => 'integer', 'created' => 'integer', 'updated' => 'integer'];
}
