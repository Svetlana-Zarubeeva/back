<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OkvedItCode extends Model
{
    use HasFactory;

    protected $table = 'okved_it_codes';
    
    protected $fillable = [
        'okved_code',
        'description',
        'section'
    ];

    protected $casts = [
        'okved_code' => 'string',
        'description' => 'string',
        'section' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить все юридические лица, использующие данный ОКВЭД
     */
    public function legalEntities(): HasMany
    {
        return $this->hasMany(LegalEntity::class, 'okved_id', 'okved_code');
    }
}