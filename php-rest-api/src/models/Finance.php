<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finance extends Model
{
    use HasFactory;

    protected $table = 'finance';
    
    protected $fillable = [
        'legal_entity_id',
        'employee_count',
        'revenue',
        'income',
        'expense',
        'tax_system'
    ];

    protected $casts = [
        'id' => 'int',
        'legal_entity_id' => 'int',
        'employee_count' => 'int',
        'revenue' => 'float',
        'income' => 'float',
        'expense' => 'float',
        'tax_system' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить юридическое лицо, которому принадлежит финансовая информация
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }
}