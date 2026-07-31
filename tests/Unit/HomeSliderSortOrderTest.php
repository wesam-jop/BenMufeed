<?php

namespace Tests\Unit;

use App\Models\HomeSlider;
use Tests\TestCase;

class HomeSliderSortOrderTest extends TestCase
{
    public function test_sort_order_is_assigned_automatically_on_create(): void
    {
        $first = HomeSlider::query()->create(['is_active' => true]);
        HomeSlider::query()->create(['is_active' => true, 'sort_order' => 5]);

        $third = HomeSlider::query()->create(['is_active' => true]);

        $this->assertSame(1, $first->sort_order);
        $this->assertSame(6, $third->sort_order);
    }
}
