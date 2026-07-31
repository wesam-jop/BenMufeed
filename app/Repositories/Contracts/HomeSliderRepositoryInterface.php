<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface HomeSliderRepositoryInterface
{
    public function activeForHome(): Collection;
}
