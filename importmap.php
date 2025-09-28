<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    '@ckeditor/ckeditor5-build-classic' => [
        'version' => '44.3.0',
    ],
    '@ckeditor/ckeditor5-alignment/src/alignment' => [
        'version' => '46.0.3',
    ],
    'ckeditor5/src/core.js' => [
        'version' => '46.0.3',
    ],
    'ckeditor5/src/utils.js' => [
        'version' => '46.0.3',
    ],
    'ckeditor5/src/ui.js' => [
        'version' => '46.0.3',
    ],
    'ckeditor5/src/icons.js' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-core' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-utils' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-ui' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-icons' => [
        'version' => '46.0.3',
    ],
    'es-toolkit/compat' => [
        'version' => '1.39.5',
    ],
    '@ckeditor/ckeditor5-engine' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-watchdog' => [
        'version' => '46.0.3',
    ],
    'color-parse' => [
        'version' => '2.0.2',
    ],
    'color-convert' => [
        'version' => '3.1.0',
    ],
    'vanilla-colorful/lib/entrypoints/hex' => [
        'version' => '0.7.2',
    ],
    'color-name' => [
        'version' => '2.0.0',
    ],
    '@ckeditor/ckeditor5-font/src/font' => [
        'version' => '46.0.3',
    ],
    'ckeditor5/src/engine.js' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-source-editing/src/sourceediting' => [
        'version' => '46.0.3',
    ],
    '@ckeditor/ckeditor5-fullscreen/src/fullscreen' => [
        'version' => '46.0.3',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'fos-router' => [
        'version' => '2.4.6',
    ],
    'intl-messageformat' => [
        'version' => '10.7.15',
    ],
    'tslib' => [
        'version' => '2.8.1',
    ],
    '@formatjs/fast-memoize' => [
        'version' => '2.2.6',
    ],
    '@formatjs/icu-messageformat-parser' => [
        'version' => '2.11.1',
    ],
    '@formatjs/icu-skeleton-parser' => [
        'version' => '1.8.13',
    ],
];
