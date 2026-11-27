<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Notes the last public page a visitor looked at, so the cart can offer one way back to it. */
class RememberBrowsedPage
{
    private const KEY = 'last_browsed';

    /** Pages worth returning to. A name is looked up only when the cart shows the link. */
    private const PAGES = [
        'home' => 'Home', 'about' => 'About Us', 'contact' => 'Contact Us', 'portfolio' => 'Projects', 'content.index' => 'News',
        'products' => 'Products', 'products.detail' => null, 'services' => 'Services', 'services.category' => null, 'services.detail' => null,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $route = $request->route()?->getName();

        if ($route && array_key_exists($route, self::PAGES) && $request->isMethod('GET') && $response->getStatusCode() === 200) {
            $request->session()->put(self::KEY, [
                'route' => $route,
                'path' => $request->getRequestUri(),
                'slug' => $request->route('slug') ?? $request->route('subcategory') ?? $request->route('category'),
            ]);
        }

        return $response;
    }

    /** @return array{0: string, 1: string} name and address of the page to go back to */
    public static function target(): array
    {
        $seen = session(self::KEY);
        $name = match ($seen['route'] ?? null) {
            null => null,
            'products.detail' => Product::visible()->where('slug', $seen['slug'])->value('name'),
            'services.detail' => Service::visible()->where('slug', $seen['slug'])->value('title'),
            'services.category' => ServiceCategory::where('slug', $seen['slug'])->where('is_published', true)->value('name'),
            default => __(self::PAGES[$seen['route']] ?? ''),
        };

        // Nothing viewed yet, or the page has since been taken down: the product list is always there.
        return $name ? [$name, url($seen['path'])] : [__('Products'), route('products')];
    }
}
