<?php
/**
 * Map deals.name → file in /asset (matches your saved filenames).
 */
function rn_deal_image_src(?string $dealName): ?string
{
    if ($dealName === null || $dealName === '') {
        return null;
    }
    $norm = strtolower(trim(preg_replace("/[''`]/u", '', preg_replace('/\s+/', ' ', $dealName))));
    $map = [
        'family feast' => 'familyfeast.jpeg',
        'couples night' => 'couplesnight.jpeg',
        'lunch special' => 'lunchspecial.jpeg',
        'bbq platter' => 'bbqplater.jpeg',
        'bbqplatter' => 'bbqplater.jpeg',
        'vegetarian delight' => 'vegeteriandelight.jpeg',
        'party pack' => 'partypack.jpeg',
        'drinks' => 'drinks.jpeg',
    ];
    $file = $map[$norm] ?? null;
    if ($file === null) {
        $file = preg_replace('/[^a-z0-9]+/i', '', $norm) . '.jpeg';
    }
    $full = __DIR__ . '/asset/' . $file;
    return is_file($full) ? 'asset/' . $file : null;
}
