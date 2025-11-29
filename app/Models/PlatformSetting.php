<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    // Methods
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function setValue(string $key, $value): void
    {
        $setting = self::where('key', $key)->firstOrNew(['key' => $key]);
        
        $setting->value = is_array($value) ? json_encode($value) : $value;
        $setting->type = self::getValueType($value);
        $setting->save();
    }

    private static function getValueType($value): string
    {
        return match(true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    // Helper Methods for Common Settings
    public static function isPhoneAuthEnabled(): bool
    {
        return self::getValue('phone_auth_enabled', false);
    }

    public static function isSocialAuthEnabled(): bool
    {
        return self::getValue('social_auth_enabled', true);
    }

    public static function getMaxFileSize(string $type): int
    {
        return self::getValue("max_file_size_{$type}", 10240);
    }

    public static function getAllowedMimes(string $type): array
    {
        return self::getValue("allowed_mimes_{$type}", ['jpg', 'png', 'jpeg', 'gif']);
    }
}