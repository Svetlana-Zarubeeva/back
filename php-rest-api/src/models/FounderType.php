<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FounderType extends Model
{
    use HasFactory;

    protected $table = 'founder_types';
    
    protected $fillable = [
        'type_code',
        'type_name'
    ];

    protected $casts = [
        'id' => 'int',
        'type_code' => 'string',
        'type_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить всех учредителей заданного типа
     */
    public function founders(): HasMany
    {
        return $this->hasMany(Founder::class, 'founder_type_id', 'id');
    }
}