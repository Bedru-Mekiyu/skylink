<?php

namespace App\Http\Controllers;

use App\Models\LegalPolicy;
use Illuminate\View\View;

class PolicyController extends Controller
{
    public function show(string $slug): View
    {
        $policy = LegalPolicy::where('slug', $slug)
            ->where('is_published', true)
            ->whereNotNull('content')
            ->firstOrFail();

        return view('pages.legal-policy', compact('policy'));
    }
}
