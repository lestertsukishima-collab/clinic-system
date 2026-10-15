<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DoctorUnavailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorUnavailability extends Model
{
    /** @use HasFactory<DoctorUnavailabilityFactory> */
    use HasFactory;

    protected $fillable = ['starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    #[Scope]
    protected function overlapping(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
