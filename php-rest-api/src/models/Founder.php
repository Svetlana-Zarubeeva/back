<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Founder extends Model
{
    use HasFactory;

    protected $table = 'founders';
    
    protected $fillable = [
        'legal_entity_id',
        'founder_type_id',
        'inn',
        'full_name',
        'is_inaccurate',
        'reason'
    ];

    protected $casts = [
        'id' => 'int',
        'legal_entity_id' => 'int',
        'founder_type_id' => 'int',
        'inn' => 'string',
        'full_name' => 'string',
        'is_inaccurate' => 'bool',
        'reason' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить юридическое лицо, которому принадлежит учредитель
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    /**
     * Получить тип учредителя
     */
    public function founderType(): BelongsTo
    {
        return $this->belongsTo(FounderType::class);
    }
}