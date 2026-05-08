<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['sk', 'en'], true), 404);

        session(['locale' => $locale]);
        App::setLocale($locale);

        return redirect()->back();
    }
}
