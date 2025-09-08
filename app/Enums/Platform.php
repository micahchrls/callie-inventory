<?php

namespace App\Enums;

enum Platform: string
{
    case TIKTOK = 'tiktok';
    case SHOPEE = 'shopee';
    case BAZAAR = 'bazaar';
    case OTHERS = 'others';

    /**
     * Get display name for the platform
     */
    public function label(): string
    {
        return match ($this) {
            self::TIKTOK => 'TikTok',
            self::SHOPEE => 'Shopee',
            self::BAZAAR => 'Bazaar',
            self::OTHERS => 'Others',
        };
    }

    /**
     * Get all platform values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all platform labels as array
     */
    public static function labels(): array
    {
        return array_map(fn ($case) => $case->label(), self::cases());
    }

    /**
     * Get platform options for forms (value => label)
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            self::labels()
        );
    }
}
