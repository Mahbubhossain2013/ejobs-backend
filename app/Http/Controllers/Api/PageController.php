<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    protected array $fallbackTitles = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'contact' => 'Contact Us',
        'about' => 'About Us',
        'faq' => 'FAQ',
    ];

    public function show(string $slug): JsonResponse
    {
        $page = StaticPage::getBySlug($slug);

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $lang = request()->header('Accept-Language', 'en');
        $isBn = str_starts_with($lang, 'bn');

        return response()->json([
            'status' => true,
            'data' => [
                'slug' => $page->slug,
                'title' => $isBn ? $page->title_bn : $page->title_en,
                'content' => $isBn ? $page->content_bn : $page->content_en,
            ],
        ]);
    }
}
