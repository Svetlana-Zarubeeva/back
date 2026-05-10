<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';
    
    protected $fillable = [
        'legal_entity_id',
        'address_full',
        'postal_code',
        'region',
        'city',
        'street',
        'house',
        'flat',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'id' => 'int',
        'legal_entity_id' => 'int',
        'address_full' => 'string',
        'postal_code' => 'string',
        'region' => 'string',
        'city' => 'string',
        'street' => 'string',
        'house' => 'string',
        'flat' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить юридическое лицо, которому принадлежит адрес
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }
}