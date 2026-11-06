<?php

return [
    'owner_name' => env('STORE_OWNER_NAME'),
    'phone' => env('STORE_PHONE'),
    'whatsapp' => env('STORE_WHATSAPP'),
    'copyright' => env('STORE_COPYRIGHT', 'Bikrify'),
    'announcements' => array_values(array_filter(array_map(
        'trim',
        explode('|', env('STORE_ANNOUNCEMENTS', 'Cash on delivery available|Thoughtful everyday picks')),
    ))),
    'services' => [
        ['icon' => 'cash', 'label' => 'Cash on delivery'],
        ['icon' => 'delivery', 'label' => 'Quick, easy checkout'],
        ['icon' => 'heart', 'label' => 'Picked with care'],
    ],
    'offer' => [
        'title' => env('STORE_OFFER_TITLE'),
        'description' => env('STORE_OFFER_DESCRIPTION'),
        'badge' => env('STORE_OFFER_BADGE', 'SPECIAL OFFER'),
        'code' => env('STORE_OFFER_CODE'),
    ],
];
