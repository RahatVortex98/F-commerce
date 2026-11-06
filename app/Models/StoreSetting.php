<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'owner_name',
        'phone',
        'whatsapp',
        'copyright_name',
        'announcements',
        'offer_badge',
        'offer_title',
        'offer_description',
        'offer_code',
        'logo_path',
        'promotions',
        'email',
        'instagram_url',
        'tiktok_url',
        'theme_primary',
        'theme_dark',
        'theme_accent',
        'theme_background',
        'theme_surface',
        'theme_text',
        'theme_font',
        'service_highlights',
    ];

    protected function casts(): array
    {
        return ['promotions' => 'array', 'service_highlights' => 'array'];
    }
}
