<?php

namespace App\Repositories;

use App\Models\HomeSlider;
use App\Repositories\Contracts\HomeSliderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HomeSliderRepository implements HomeSliderRepositoryInterface
{
    public function activeForHome(): Collection
    {
        return HomeSlider::query()
            ->where('is_active', true)
            ->with(['media'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
