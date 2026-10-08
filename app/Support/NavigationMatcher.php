<?php

namespace App\Support;

/**
 * Active-route matching for config('navigation.*') items — extracted from
 * the sidebar (2.2.1c) so BOTH the popup nav menu (Fase 2 Langkah 5) and
 * <x-shell.breadcrumb> use the exact same logic, instead of two copies
 * silently drifting apart. Pure PHP, no view/state dependency beyond the
 * current request.
 */
class NavigationMatcher
{
    public static function isActive(array $item): bool
    {
        if (! $item['enabled'] || ! $item['route']) {
            return false;
        }

        if (request()->routeIs($item['route'])) {
            return true;
        }

        if (preg_match('/^(.+)\.(index|show|create|edit|board|approve)$/', $item['route'], $matches)) {
            if (request()->routeIs($matches[1].'.*')) {
                return true;
            }
        }

        foreach ($item['active_when'] ?? [] as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array>  $navItems
     */
    public static function currentItem(array $navItems): ?array
    {
        foreach ($navItems as $item) {
            if (self::isActive($item)) {
                return $item;
            }
        }

        return null;
    }
}
