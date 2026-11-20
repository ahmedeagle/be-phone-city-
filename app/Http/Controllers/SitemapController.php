<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $urls = collect();
        foreach (['ar', 'en'] as $lang) {
            $urls->push(['loc' => "$base/$lang/", 'lastmod' => now()->toDateString(), 'priority' => '1.0']);
            Category::query()->select('slug', 'updated_at')->whereNotNull('slug')->eachById(fn ($item) => $urls->push(['loc' => "$base/$lang/categorySingle/{$item->slug}/products", 'lastmod' => optional($item->updated_at)->toDateString(), 'priority' => '0.8']));
            Product::query()->select('slug', 'updated_at')->whereNotNull('slug')->where('quantity', '>', 0)->eachById(fn ($item) => $urls->push(['loc' => "$base/$lang/singleproduct/{$item->slug}", 'lastmod' => optional($item->updated_at)->toDateString(), 'priority' => '0.7']));
            Blog::query()->published()->select('slug', 'updated_at')->eachById(fn ($item) => $urls->push(['loc' => "$base/$lang/blog/{$item->slug}", 'lastmod' => optional($item->updated_at)->toDateString(), 'priority' => '0.6']));
        }
        $xml = view('sitemap', ['urls' => $urls]);
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
