<?php

namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use ReflectionClass;

/**
 * Breadcrumb trail (RANCANGAN_FINAL_WEBI-SPACE_v2.md §1.6) — WAJIB di semua
 * halaman sejak sidebar dihapus (Fase 2 Langkah 5). Generate OTOMATIS dari
 * route + config('navigation.*') + parameter route (bukan input manual per
 * halaman) supaya ~40 halaman yang sudah ada tidak perlu disentuh satu-satu,
 * dan halaman baru ke depan otomatis dapat breadcrumb yang benar tanpa kerja
 * ekstra — lihat docblock trail() untuk urutan resolusinya.
 */
class Breadcrumbs
{
    private const DASHBOARD_ROUTES = ['admin.dashboard', 'eksplorasi.dashboard', 'eksekusi.dashboard'];

    /**
     * Resolver label "detail" per nama route — HANYA untuk halaman yang
     * generic #[Title] Livewire-nya kurang informatif dibanding nama entitas
     * sungguhan (mis. "Detail Proyek" vs nama proyek beneran). Sengaja
     * SATU daftar terpusat di sini, bukan disebar ke tiap file halaman.
     * Route yang tidak terdaftar di sini tetap dapat crumb otomatis lewat
     * fallback #[Title] reflection di leafLabel(). Method (bukan class
     * const) karena PHP tidak izinkan closure sebagai const expression.
     *
     * @return array<string, callable(Route): string>
     */
    private static function entityLabelResolvers(): array
    {
        return [
            'eksplorasi.unit.show' => fn (Route $route) => $route->parameter('unit')->title,
            'eksplorasi.checkpoint.show' => fn (Route $route) => 'Checkpoint Modul '.$route->parameter('checkpoint')->module->order_number,
            'eksplorasi.forum.show' => fn (Route $route) => $route->parameter('thread')->title,
            'eksekusi.projects.show' => fn (Route $route) => $route->parameter('project')->title,
            'eksekusi.projects.kanban' => fn (Route $route) => 'Kanban: '.$route->parameter('project')->title,
            'eksekusi.projects.roadmap' => fn (Route $route) => 'Roadmap: '.$route->parameter('project')->title,
            'eksekusi.projects.gantt' => fn (Route $route) => 'Gantt: '.$route->parameter('project')->title,
            'eksekusi.projects.kalender' => fn (Route $route) => 'Kalender: '.$route->parameter('project')->title,
            'eksekusi.projects.forum' => fn (Route $route) => 'Forum: '.$route->parameter('project')->title,
            'eksekusi.projects.anggota' => fn (Route $route) => 'Anggota: '.$route->parameter('project')->title,
            // Fase 7 Batch 1b: 'eksekusi.tasks.show' removed from this list
            // on purpose — that route is now a pure redirect (to the Kanban
            // tab + task detail panel), it never renders a page of its own
            // for a breadcrumb to resolve against. 'eksekusi.ideas.approve'
            // removed the same way (2026-07-17, Project Idea/Project
            // independence revision) — approving an idea is now a
            // wire:click action on the Index card itself, not its own route.
            'admin.users.edit' => fn (Route $route) => 'Edit: '.$route->parameter('user')->name,
            'admin.webi.show' => fn (Route $route) => $route->parameter('user')->name,
        ];
    }

    /**
     * @return array<int, array{label: string, url: ?string}>
     */
    public static function trail(): array
    {
        $route = request()->route();
        $user = Auth::user();

        if (! $route || ! $user || ! $route->getName()) {
            return [];
        }

        $routeName = $route->getName();
        $isDashboard = in_array($routeName, self::DASHBOARD_ROUTES, true);

        $crumbs = [
            ['label' => 'Beranda', 'url' => $isDashboard ? null : url('/dashboard')],
        ];

        if ($isDashboard) {
            return $crumbs;
        }

        $navItems = config('navigation.'.$user->role, []);
        $matchedItem = NavigationMatcher::currentItem($navItems);

        if ($matchedItem && $matchedItem['route'] === $routeName) {
            // Halaman menu itu sendiri (mis. Peta Kurikulum) — crumb terakhir, tanpa link.
            $crumbs[] = ['label' => $matchedItem['label'], 'url' => null];

            return $crumbs;
        }

        if ($matchedItem) {
            // Halaman detail di bawah item menu (mis. Unit di bawah Peta
            // Kurikulum) — item menu jadi crumb tengah yang bisa diklik.
            $crumbs[] = ['label' => $matchedItem['label'], 'url' => route($matchedItem['route'])];
        }

        $crumbs[] = ['label' => self::leafLabel($route, $routeName), 'url' => null];

        return $crumbs;
    }

    private static function leafLabel(Route $route, string $routeName): string
    {
        $resolvers = self::entityLabelResolvers();

        if (isset($resolvers[$routeName])) {
            return $resolvers[$routeName]($route);
        }

        $controllerClass = $route->getAction('controller');

        if (is_string($controllerClass) && class_exists($controllerClass)) {
            $titleAttribute = (new ReflectionClass($controllerClass))->getAttributes(Title::class)[0] ?? null;

            if ($titleAttribute) {
                return $titleAttribute->newInstance()->content;
            }
        }

        return ucwords(str_replace(['.', '-', '_'], ' ', $routeName));
    }
}
