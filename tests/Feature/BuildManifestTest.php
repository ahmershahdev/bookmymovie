<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * app.blade.php asks Vite for resources/js/pages/{component}.tsx by name. If a
 * build merges a page into a shared chunk (it happened to the seat map when
 * it shared hallLayout.ts with the lazy 3D hall), that page loses its manifest
 * key and answers every visit with a 500.
 */
class BuildManifestTest extends TestCase
{
    public function test_every_inertia_page_has_its_own_manifest_entry(): void
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            $this->markTestSkipped('No production build; run npm run build first.');
        }

        $entries = json_decode((string) file_get_contents($manifest), true);
        $pages = glob(resource_path('js/pages/{,*/}*.tsx'), GLOB_BRACE) ?: [];
        $this->assertNotEmpty($pages);

        $missing = [];
        foreach ($pages as $file) {
            $key = 'resources/js/pages/'.str_replace('\\', '/', substr($file, strlen(resource_path('js/pages/'))));
            if (! isset($entries[$key])) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, 'Pages missing from the Vite manifest: '.implode(', ', $missing));
    }
}
