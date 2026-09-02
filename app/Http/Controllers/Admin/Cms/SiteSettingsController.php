<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Settings;
use App\Http\Requests\Admin\Cms\Site\UpdateSiteSettingsRequest;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('view', Settings::class);

        $settings = SiteSetting::current();

        return Inertia::render('admin/site/index', [
            'settings' => $this->transformSettings($settings),
            'summary' => $this->buildSiteSummary($settings),
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $settings = SiteSetting::current();
        $data = $request->safe()->except([
            'logo',
            'hero_image',
            'about_image',
            'categories_page_image',
            'products_page_image',
            'remove_categories_page_image',
            'remove_products_page_image',
            'clear_map_location',
        ]);

        $data['show_featured_products'] = $request->boolean('show_featured_products');
        $data['contact_form_enabled'] = $request->boolean('contact_form_enabled');

        if ($request->has('about_features')) {
            $data['about_features'] = collect($request->input('about_features', []))
                ->filter(fn ($feature) => filled($feature['title'] ?? null) || filled($feature['description'] ?? null))
                ->values()
                ->all();
        }

        if ($request->has('trust_items')) {
            $data['trust_items'] = collect($request->input('trust_items', []))
                ->filter(fn ($item) => filled($item['title'] ?? null))
                ->values()
                ->all();
        }

        $data['map_placements'] = collect($request->input('map_placements', []))
            ->filter(fn ($value) => in_array($value, ['home', 'about', 'contact'], true))
            ->values()
            ->all();

        if ($request->filled('map_lat') && $request->filled('map_lng')) {
            $data['map_lat'] = (float) $request->input('map_lat');
            $data['map_lng'] = (float) $request->input('map_lng');
        } elseif ($request->boolean('clear_map_location')) {
            $data['map_lat'] = null;
            $data['map_lng'] = null;
        }

        foreach (['logo', 'hero_image', 'about_image', 'categories_page_image', 'products_page_image'] as $field) {
            if ($request->hasFile($field)) {
                if ($settings->{$field}) {
                    Storage::disk('public')->delete($settings->{$field});
                }
                $data[$field] = $request->file($field)->store('site', 'public');
            }
        }

        if ($request->boolean('remove_categories_page_image') && $settings->categories_page_image) {
            Storage::disk('public')->delete($settings->categories_page_image);
            $data['categories_page_image'] = null;
        }

        if ($request->boolean('remove_products_page_image') && $settings->products_page_image) {
            Storage::disk('public')->delete($settings->products_page_image);
            $data['products_page_image'] = null;
        }

        if ($request->boolean('remove_logo') && $settings->logo) {
            Storage::disk('public')->delete($settings->logo);
            $data['logo'] = null;
        }

        if ($request->boolean('remove_hero_image') && $settings->hero_image) {
            Storage::disk('public')->delete($settings->hero_image);
            $data['hero_image'] = null;
        }

        if ($request->boolean('remove_about_image') && $settings->about_image) {
            Storage::disk('public')->delete($settings->about_image);
            $data['about_image'] = null;
        }

        $settings->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.site_settings_saved'),
        ]);
    }

    public function messages(Request $request): Response
    {
        $this->authorize('view', Settings::class);

        $baseQuery = $this->messagesBaseQuery($request);

        $messages = (clone $baseQuery)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $messages->through(fn (ContactMessage $message) => $this->transformMessage($message));

        $allMessages = ContactMessage::query();
        $filteredMessages = (clone $baseQuery);

        return Inertia::render('admin/site/messages', [
            'messages' => $messages,
            'summary' => [
                'total_count' => (clone $allMessages)->count(),
                'unread_count' => (clone $allMessages)->where('is_read', false)->count(),
                'read_count' => (clone $allMessages)->where('is_read', true)->count(),
            ],
            'listSummary' => [
                'count' => (clone $filteredMessages)->count(),
                'unread_count' => (clone $filteredMessages)->where('is_read', false)->count(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
            ],
            'filterOptions' => [
                'statuses' => [
                    ['value' => 'all', 'label' => 'كل الرسائل'],
                    ['value' => 'unread', 'label' => 'غير مقروءة'],
                    ['value' => 'read', 'label' => 'مقروءة'],
                ],
            ],
        ]);
    }

    public function showMessage(ContactMessage $message): Response
    {
        $this->authorize('view', Settings::class);

        if (! $message->is_read) {
            $message->update(['is_read' => true]);
            $message->refresh();
        }

        return Inertia::render('admin/site/show-message', [
            'message' => $this->transformMessage($message),
        ]);
    }

    public function markMessageRead(ContactMessage $message): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $message->update(['is_read' => true]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.message_read'),
        ]);
    }

    public function markMessageUnread(ContactMessage $message): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $message->update(['is_read' => false]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.message_unread'),
        ]);
    }

    public function markAllMessagesRead(): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $updated = ContactMessage::query()->where('is_read', false)->update(['is_read' => true]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $updated > 0
                ? "تم تعليم {$updated} رسالة كمقروءة."
                : 'لا توجد رسائل غير مقروءة.',
        ]);
    }

    public function destroyMessage(ContactMessage $message): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $message->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.message_deleted'),
        ]);
    }

    /** @return array<string, mixed> */
    private function transformSettings(SiteSetting $settings): array
    {
        return [
            ...$settings->toArray(),
            'logo_url' => $settings->logo ? Storage::url($settings->logo) : null,
            'hero_image_url' => $settings->hero_image ? Storage::url($settings->hero_image) : null,
            'about_image_url' => $settings->about_image ? Storage::url($settings->about_image) : null,
            'categories_page_image_url' => $settings->categories_page_image
                ? Storage::url($settings->categories_page_image)
                : null,
            'products_page_image_url' => $settings->products_page_image
                ? Storage::url($settings->products_page_image)
                : null,
            'updated_at' => $settings->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildSiteSummary(SiteSetting $settings): array
    {
        $messagesQuery = ContactMessage::query();

        return [
            'unread_messages' => (clone $messagesQuery)->where('is_read', false)->count(),
            'total_messages' => (clone $messagesQuery)->count(),
            'homepage_sections_count' => count($settings->homepage_sections ?? []),
            'has_logo' => filled($settings->logo),
            'has_hero_image' => filled($settings->hero_image),
            'has_about_image' => filled($settings->about_image),
            'contact_form_enabled' => (bool) $settings->contact_form_enabled,
            'show_featured_products' => (bool) $settings->show_featured_products,
            'updated_at' => $settings->updated_at?->toIso8601String(),
        ];
    }

    private function messagesBaseQuery(Request $request): Builder
    {
        $query = ContactMessage::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        match ($request->input('status', 'all')) {
            'unread' => $query->where('is_read', false),
            'read' => $query->where('is_read', true),
            default => null,
        };

        return $query;
    }

    /** @return array<string, mixed> */
    private function transformMessage(ContactMessage $message): array
    {
        $phone = $message->phone;

        return [
            'id' => $message->id,
            'name' => $message->name,
            'phone' => $phone,
            'email' => $message->email,
            'message' => $message->message,
            'message_preview' => mb_strlen($message->message) > 80
                ? mb_substr($message->message, 0, 80).'...'
                : $message->message,
            'is_read' => (bool) $message->is_read,
            'whatsapp_url' => $phone
                ? 'https://wa.me/'.preg_replace('/\D+/', '', $phone)
                : null,
            'created_at' => $message->created_at?->toIso8601String(),
            'updated_at' => $message->updated_at?->toIso8601String(),
        ];
    }
}
