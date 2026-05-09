<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Management extends Model
{
    use HasFactory;

    protected $table = 'management';
    
    protected $fillable = [
        'legal_entity_id',
        'name',
        'post',
        'start_date'
    ];

    protected $casts = [
        'id' => 'int',
        'legal_entity_id' => 'int',
        'name' => 'string',
        'post' => 'string',
        'start_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'start_date',
        'created_at',
        'updated_at'
    ];

    /**
     * Получить юридическое лицо, которому принадлежит информация о руководстве
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }
}