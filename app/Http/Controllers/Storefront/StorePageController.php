<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontSeo;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorePageController extends Controller
{
    use RendersStorefrontPage;

    /**
     * @var array<string, array{title: string, heading: string, body: string}>
     */
    private const PAGES = [
        'about' => [
            'title' => 'من نحن',
            'heading' => 'من نحن',
            'body' => "بن مفيد (Ben Mufeed) كافيتيريا تجمع بين الراحة والضيافة.\n\nنقدّم مشروبات ساخنة وباردة، أراكيل بنكهات متنوعة، وأجواء مثالية لمشاهدة المباريات مع الأصدقاء. مكانك المفضل للجلوس، الاسترخاء، والاستمتاع بكل لحظة.",
        ],
        'shipping' => [
            'title' => 'ساعات العمل',
            'heading' => 'ساعات العمل والموقع',
            'body' => "نرحّب بكم يومياً من الساعة 10 صباحاً حتى 2 بعد منتصف الليل.\n\nيمكنكم حجز طاولة أو طلب مشروباتكم عبر واتساب. نحن جاهزون لاستقبالكم في أي وقت — سواء للجلوس الهادئ أو لمشاهدة المباريات.",
        ],
        'contact' => [
            'title' => 'اتصل بنا',
            'heading' => 'اتصل بنا',
            'body' => "للحجز، الاستفسارات، أو متابعة طلبك — تواصل معنا عبر واتساب من الشريط العلوي أو من صفحة «طلبك» بعد اختيار مشروباتك.\n\nفريق بن مفيد جاهز لاستقبالكم في أي وقت.",
        ],
    ];

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function about(Request $request): Response
    {
        return $this->renderPage($request, 'about');
    }

    public function shipping(Request $request): Response
    {
        return $this->renderPage($request, 'shipping');
    }

    public function contact(Request $request): Response
    {
        return $this->renderPage($request, 'contact');
    }

    protected function renderPage(Request $request, string $page): Response
    {
        $content = self::PAGES[$page] ?? null;

        if ($content === null) {
            throw new NotFoundHttpException;
        }

        $settings = $this->siteSettings->current();
        $seo = StorefrontSeo::forSite($settings);
        $seo = new StorefrontSeo(
            title: $content['title'].' | '.($settings->site_name ?: config('app.name')),
            description: StorefrontSeo::clip($content['body'], 160),
            image: $seo->image,
            url: url('/'.$page),
            type: 'website',
            siteName: $seo->siteName,
            keywords: $seo->keywords,
        );

        return $this->renderStorefront(
            'Store/StaticPage',
            [
                'page' => [
                    'slug' => $page,
                    ...$content,
                ],
            ],
            $seo,
            [],
            [
                ['name' => 'الرئيسية', 'href' => '/'],
                ['name' => $content['heading']],
            ],
        );
    }
}
