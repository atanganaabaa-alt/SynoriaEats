<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'tastes',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tastes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $updates
     */
    public function mergeTastes(array $updates): self
    {
        $current = is_array($this->tastes) ? $this->tastes : [];

        foreach ($updates as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            if ($value === null || $value === '' || $value === 'null') {
                unset($current[$key]);

                continue;
            }

            $current[$key] = is_string($value) ? trim($value) : $value;
        }

        $this->tastes = $current;

        return $this;
    }
}
