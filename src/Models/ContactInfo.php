<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInfo extends Model
{
    use HasFactory;

    protected $table = 'contact_info';
    
    protected $fillable = [
        'legal_entity_id',
        'phones',
        'emails',
        'websites'
    ];

    protected $casts = [
        'id' => 'int',
        'legal_entity_id' => 'int',
        'phones' => 'array',
        'emails' => 'array',
        'websites' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить юридическое лицо, которому принадлежит контактная информация
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }
}