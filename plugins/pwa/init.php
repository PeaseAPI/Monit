<?php

/**
 * PWA 启动入口：注册 /pwa/manifest.json 与 /pwa/sw.js 端点（规格书 §14.6）
 */

use Illuminate\Support\Facades\Route;

// 动态 Manifest
Route::get('/pwa/manifest.json', function () {
    if (! \App\Support\PluginManager::isActive('pwa')) {
        abort(404);
    }

    $settings = fn (string $key, mixed $default) => \App\Support\PluginManager::setting('pwa', $key, $default);

    return response()->json([
        'name' => $settings('name', 'Monit Analytics'),
        'short_name' => $settings('short_name', 'Monit'),
        'description' => $settings('description', ''),
        'start_url' => url('/'),
        'display' => 'standalone',
        'background_color' => $settings('background_color', '#0f172a'),
        'theme_color' => $settings('theme_color', '#4f46e5'),
        'icons' => [
            ['src' => url('/favicon.ico'), 'sizes' => 'any', 'type' => 'image/x-icon'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('pwa.manifest');

// Service Worker：CacheFirst 静态 / NetworkFirst 页面
Route::get('/pwa/sw.js', function () {
    if (! \App\Support\PluginManager::isActive('pwa')) {
        abort(404);
    }

    $js = <<<JS
const CACHE = 'monit-pwa-v1';
const PRECACHE = ['/', '/pwa/manifest.json'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== location.origin) return;
    if (url.pathname.startsWith('/pixel') || url.pathname.startsWith('/admin')) return;

    if (/\.(css|js|png|jpg|jpeg|svg|woff2?)$/.test(url.pathname)) {
        // CacheFirst
        event.respondWith(
            caches.match(event.request).then((hit) => hit || fetch(event.request).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((cache) => cache.put(event.request, copy));
                return res;
            }))
        );
    } else if (event.request.mode === 'navigate') {
        // NetworkFirst
        event.respondWith(
            fetch(event.request).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((cache) => cache.put(event.request, copy));
                return res;
            }).catch(() => caches.match(event.request).then((hit) => hit || caches.match('/')))
        );
    }
});
JS;

    return response($js, 200, ['Content-Type' => 'application/javascript', 'Service-Worker-Allowed' => '/']);
})->name('pwa.sw');
