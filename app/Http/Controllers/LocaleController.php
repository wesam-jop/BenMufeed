<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        $locale = Locale::normalize($locale);

        $request->session()->put('locale', $locale);

        return back()
            ->cookie('locale', $locale, 60 * 24 * 365)
            ->with('toast', [
                'type' => 'success',
                'message' => __('locale.switched_'.$locale),
            ]);
    }
}
