<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('storefront.sitemap', 3600, fn (): string => $this->buildXml());

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    protected function buildXml(): string
    {
        $urls = collect();

        $siteUpdated = SiteSetting::query()->value('updated_at');
        $homeLastmod = $siteUpdated ? Carbon::parse($siteUpdated)->toAtomString() : null;

        $urls->push($this->entry(url('/'), 'daily', '1.0', $homeLastmod));
        $urls->push($this->entry(url('/products'), 'daily', '0.9', $homeLastmod));
        $urls->push($this->entry(url('/categories'), 'weekly', '0.85', $homeLastmod));
        $urls->push($this->entry(url('/about'), 'monthly', '0.5', $homeLastmod));
        $urls->push($this->entry(url('/shipping'), 'monthly', '0.5', $homeLastmod));
        $urls->push($this->entry(url('/contact'), 'monthly', '0.5', $homeLastmod));

        Category::query()->orderBy('id')->each(function (Category $category) use ($urls) {
            $urls->push($this->entry(
                url('/categories/'.$category->slug),
                'weekly',
                '0.8',
                $category->updated_at?->toAtomString(),
            ));
        });

        Product::query()
            ->where('status', ProductStatus::Published)
            ->orderBy('id')
            ->each(function (Product $product) use ($urls) {
                $urls->push($this->entry(
                    url('/products/'.$product->slug),
                    'weekly',
                    '0.7',
                    $product->updated_at?->toAtomString(),
                ));
            });

        return view('sitemap', ['urls' => $urls])->render();
    }

    /**
     * @return array{loc: string, changefreq: string, priority: string, lastmod?: string}
     */
    protected function entry(string $loc, string $changefreq, string $priority, ?string $lastmod = null): array
    {
        $row = [
            'loc' => $loc,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];

        if ($lastmod) {
            $row['lastmod'] = $lastmod;
        }

        return $row;
    }
}
