<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\TutorProgressReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $generated_by
 * @property-read string $status
 * @property-read CarbonInterface $period_start
 * @property-read CarbonInterface $period_end
 * @property-read array<string, mixed>|null $payload
 * @property-read string|null $error
 * @property-read User $student
 * @property-read User $generator
 */
final class TutorProgressReport extends Model
{
    /** @use HasFactory<TutorProgressReportFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
