<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'token',
        'type',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Scopes
    public function scopeValid($query)
    {
        return $query->whereNull('verified_at')
                    ->where('expires_at', '>', now());
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function isValid(): bool
    {
        return is_null($this->verified_at) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function verify(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function generateCode(): void
    {
        $this->update([
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'token' => Str::random(32),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public static function createVerification(string $email, string $type): self
    {
        // غیرفعال کردن کدهای قبلی
        self::where('email', $email)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        $verification = self::create([
            'email' => $email,
            'type' => $type,
        ]);

        $verification->generateCode();

        return $verification;
    }
}