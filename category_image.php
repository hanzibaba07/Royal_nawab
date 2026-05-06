<?php
/**
 * Map menu_categories.name → file in /asset (see filenames there).
 */
function rn_category_image_src(?string $categoryName): ?string
{
    if ($categoryName === null || $categoryName === '') {
        return null;
    }
    $key = strtolower(trim(preg_replace('/\s+/', ' ', $categoryName)));
    $map = [
        'starters' => 'starters.jpeg',
        'curries' => 'curry.jpeg',
        'curry' => 'curry.jpeg',
        'rice' => 'rice.jpeg',
        'bbq' => 'bbq.jpeg',
        'drink' => 'drinks.jpeg',
        'drinks' => 'drinks.jpeg',
        'beverage' => 'drinks.jpeg',
        'beverages' => 'drinks.jpeg',
        'desserts' => 'desserts.jpeg',
        'dessert' => 'desserts.jpeg',
    ];
    $file = $map[$key] ?? null ?: null;
    if ($file === null && str_contains($key, 'drink')) {
        $file = 'drinks.jpeg';
    }
    if ($file === null) {
        $file = preg_replace('/[^a-z0-9]+/i', '', $key) . '.jpeg';
    }
    $full = __DIR__ . '/asset/' . $file;
    return is_file($full) ? 'asset/' . $file : null;
}
