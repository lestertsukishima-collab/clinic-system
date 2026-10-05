<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialization',
        'phone',
    ];

    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) ($this->user?->name ?? ''));
        $name = preg_replace('/^(?:Dr\.?\s*)+/i', '', $name) ?? $name;

        return $name === '' ? 'Doctor' : "Dr. {$name}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
