<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function casConsole(): View
    {
        return view('pages.cas-console');
    }

    public function pendulum(): View
    {
        return view('pages.pendulum');
    }

    public function ballBeam(): View
    {
        return view('pages.ball-beam');
    }

    public function statistics(): View
    {
        return view('pages.statistics');
    }

    public function apiDocs(): View
    {
        return view('pages.api-docs');
    }
}
