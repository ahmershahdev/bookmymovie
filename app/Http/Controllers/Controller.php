<?php

namespace App\Http\Controllers;

use App\Support\Breadcrumbs;
use App\Support\LayoutData;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

abstract class Controller
{
    /**
     * Renders a React page. $seo feeds the server-rendered <head> (title,
     * description, Open Graph, JSON-LD) on the first load; the title and
     * description also travel as a prop so client-side visits update them.
     *
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $seo
     * @param  list<array{label: string, url: ?string}>|null  $breadcrumbs
     */
    protected function page(string $component, array $props = [], array $seo = [], ?array $breadcrumbs = null): Response
    {
        $crumbs = $breadcrumbs ?? Breadcrumbs::for(request());
        $settings = LayoutData::settings();

        $title = $seo['title'] ?? ($settings['default_meta_title'] ?? ($settings['site_name'] ?? 'BookMyMovie').' | Cinema Tickets, Showtimes & Seats');
        $description = $seo['description'] ?? ($settings['default_meta_description'] ?? 'Book cinema tickets online with live seat maps and honest prices.');

        return Inertia::render($component, [
            ...$props,
            'meta' => [
                'title' => Seo::title(html_entity_decode((string) $title, ENT_QUOTES)),
                'description' => Seo::description(html_entity_decode((string) $description, ENT_QUOTES)),
                'breadcrumbs' => $crumbs,
            ],
        ])->withViewData(['seo' => $seo, 'breadcrumbs' => $crumbs]);
    }
}
