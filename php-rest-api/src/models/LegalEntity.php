<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class LegalEntity extends Model
{
    use HasFactory;

    protected $table = 'legal_entities';
    
    protected $fillable = [
        'inn',
        'ogrn',
        'kpp',
        'short_name',
        'full_name',
        'registration_date',
        'status',
        'okved_id'
    ];

    protected $casts = [
        'id' => 'int',
        'inn' => 'string',
        'ogrn' => 'string',
        'kpp' => 'string',
        'short_name' => 'string',
        'full_name' => 'string',
        'registration_date' => 'date',
        'status' => 'string',
        'okved_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'registration_date',
        'created_at',
        'updated_at'
    ];

    /**
     * Получить адрес юридического лица
     */
    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Получить контактную информацию юридического лица
     */
    public function contactInfo(): HasOne
    {
        return $this->hasOne(ContactInfo::class);
    }

    /**
     * Получить финансовую информацию юридического лица
     */
    public function finance(): HasOne
    {
        return $this->hasOne(Finance::class);
    }

    /**
     * Получить информацию о руководстве юридического лица
     */
    public function management(): HasOne
    {
        return $this->hasOne(Management::class);
    }

    /**
     * Получить всех учредителей юридического лица
     */
    public function founders(): HasMany
    {
        return $this->hasMany(Founder::class);
    }

    /**
     * Получить тип ОКВЭД для юридического лица
     */
    public function okved(): HasOne
    {
        return $this->hasOne(OkvedItCode::class, 'okved_code', 'okved_id');
    }
}