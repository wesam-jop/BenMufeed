<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Sales\RestaurantTable\StoreRestaurantTableRequest;
use App\Http\Requests\Admin\Sales\RestaurantTable\UpdateRestaurantTableRequest;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantTableController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RestaurantTable::class);

        $restaurantTables = $this->forBranch(RestaurantTable::query(), $request)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (RestaurantTable $table) => $this->transform($table));

        $settings = SiteSetting::current();
        $qrLogoPath = $settings->table_qr_logo ?: $settings->logo;

        return Inertia::render('admin/restaurant-tables/index', [
            'restaurantTable' => $restaurantTables,
            'unifiedQrUrl' => SiteSetting::tableOrderQrUrl(),
            'tableAccessCode' => SiteSetting::tableAccessCode(),
            'businessName' => $settings->business_name ?? config('app.name'),
            'logoUrl' => $qrLogoPath ? Storage::url($qrLogoPath) : null,
            'hasCustomQrLogo' => filled($settings->table_qr_logo),
        ]);
    }

    public function uploadQrLogo(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', RestaurantTable::class);

        $request->validate([
            'logo' => ['required', 'image', 'max:4096'],
        ]);

        $settings = SiteSetting::current();

        if ($settings->table_qr_logo) {
            Storage::disk('public')->delete($settings->table_qr_logo);
        }

        $path = $request->file('logo')->store('site/qr', 'public');
        $settings->forceFill(['table_qr_logo' => $path])->save();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.qr_logo_saved'),
        ]);
    }

    public function removeQrLogo(): RedirectResponse
    {
        $this->authorize('viewAny', RestaurantTable::class);

        $settings = SiteSetting::current();

        if ($settings->table_qr_logo) {
            Storage::disk('public')->delete($settings->table_qr_logo);
            $settings->forceFill(['table_qr_logo' => null])->save();
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.qr_logo_deleted'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', RestaurantTable::class);

        return Inertia::render('admin/restaurant-tables/create');
    }

    public function store(StoreRestaurantTableRequest $request): RedirectResponse
    {
        $this->authorize('create', RestaurantTable::class);

        $data = $request->validated();
        $data['branch_id'] = $this->branchId($request) ?? $request->user()->branch_id;

        RestaurantTable::create($data);

        return to_route('admin.restaurant-tables.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.table_created')]);
    }

    public function show(RestaurantTable $restaurantTable): Response
    {
        $this->authorize('view', $restaurantTable);

        return Inertia::render('admin/restaurant-tables/show', [
            'restaurantTable' => $this->transform($restaurantTable),
            'unifiedQrUrl' => SiteSetting::tableOrderQrUrl(),
            'tableAccessCode' => SiteSetting::tableAccessCode(),
        ]);
    }

    public function edit(RestaurantTable $restaurantTable): Response
    {
        $this->authorize('update', $restaurantTable);

        return Inertia::render('admin/restaurant-tables/edit', [
            'restaurantTable' => $restaurantTable,
        ]);
    }

    public function update(UpdateRestaurantTableRequest $request, RestaurantTable $restaurantTable): RedirectResponse
    {
        $this->authorize('update', $restaurantTable);

        $restaurantTable->update($request->validated());

        return to_route('admin.restaurant-tables.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.table_updated')]);
    }

    public function destroy(RestaurantTable $restaurantTable): RedirectResponse
    {
        $this->authorize('delete', $restaurantTable);

        $restaurantTable->delete();

        return to_route('admin.restaurant-tables.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.table_deleted')]);
    }

    public function regenerateCode(RestaurantTable $restaurantTable): RedirectResponse
    {
        $this->authorize('update', $restaurantTable);

        $code = SiteSetting::regenerateTableAccessCode();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.access_code_regenerated', ['code' => $code]),
        ]);
    }

    public function regenerateSharedCode(): RedirectResponse
    {
        $this->authorize('viewAny', RestaurantTable::class);

        $code = SiteSetting::regenerateTableAccessCode();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.access_code_regenerated', ['code' => $code]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(RestaurantTable $table): array
    {
        return [
            'id' => $table->id,
            'name' => $table->name,
            'capacity' => $table->capacity,
            'zone' => $table->zone,
            'status' => $table->status instanceof \BackedEnum ? $table->status->value : (string) $table->status,
            'access_code' => $table->access_code,
            'qr_token' => $table->qr_token,
            'sort_order' => $table->sort_order,
            'legacy_qr_url' => $table->qr_token ? url('/order/'.$table->qr_token) : null,
        ];
    }
}
