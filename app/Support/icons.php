<?php
declare(strict_types=1);

/**
 * Набор inline-иконок (Lucide-подобные). Один источник правды для всего UI.
 */
function icon(string $name, string $class = ''): string
{
    static $paths = [
        'home'        => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
        'catalog'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'trainer'     => '<path d="M14.5 4.5 19.5 9.5"/><path d="m7 21-4-4L14 6l4 4Z"/><path d="M17 3 21 7"/><path d="m2.5 21.5 2-.5"/>',
        'trophy'      => '<path d="M8 4h8v4a4 4 0 0 1-8 0Z"/><path d="M16 5h3v1a3 3 0 0 1-3 3M8 5H5v1a3 3 0 0 0 3 3"/><path d="M10 12.5V16h4v-3.5M8 20h8M9 16h6"/>',
        'chat'        => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2Z"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'flame'       => '<path d="M12 3s5 4 5 9a5 5 0 0 1-10 0c0-1.5.6-2.7 1.3-3.6C8.9 10 10 11 10 11s-.5-3 2-8Z"/>',
        'star'        => '<path d="m12 3 2.6 5.5 6 .8-4.3 4.2 1 6-5.3-2.9L6.7 19.5l1-6L3.4 9.3l6-.8Z"/>',
        'bolt'        => '<path d="M13 2 4 14h6l-1 8 9-12h-6Z"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'       => '<path d="M20 6 9 17l-5-5"/>',
        'x'           => '<path d="M18 6 6 18M6 6l12 12"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'chevron-right'=> '<path d="m9 6 6 6-6 6"/>',
        'chevron-down'=> '<path d="m6 9 6 6 6-6"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'edit'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash'       => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>',
        'users'       => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 21a6.5 6.5 0 0 1 13 0"/><path d="M16 5a3.5 3.5 0 0 1 0 7M22 21a6 6 0 0 0-4-5.6"/>',
        'shield'      => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6Z"/>',
        'key'         => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 8.2-8.2M18 6l2 2M15 9l2 2"/>',
        'fingerprint' => '<path d="M12 10a2 2 0 0 1 2 2c0 3-1 5-1 5"/><path d="M8 12a4 4 0 0 1 8 0c0 4-1 6-1 6"/><path d="M5 13a7 7 0 0 1 14-1"/><path d="M9 20s1-2 1-8a2 2 0 0 1 2-2"/>',
        'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'target'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
        'sparkles'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2 2M16 16l2 2M18 6l-2 2M8 16l-2 2"/>',
        'send'        => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'medal'       => '<circle cx="12" cy="15" r="6"/><path d="M12 12v6M9.5 14l2.5 1 2.5-1"/><path d="M8 3h8l-2 6H10Z"/>',
        'chart'       => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    ];

    $body = $paths[$name] ?? '';
    $cls = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<svg' . $cls . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
