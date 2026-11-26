<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\LegalPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolicyController extends Controller
{
    public function index(): View
    {
        $policies = LegalPolicy::orderBy('title')->get();
        return view('admin.policies.index', compact('policies'));
    }

    public function edit(LegalPolicy $policy): View
    {
        return view('admin.policies.edit', compact('policy'));
    }

    public function update(Request $request, LegalPolicy $policy): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'content_am' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $beforePublished = $policy->is_published;

        $policy->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'content_am' => $validated['content_am'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ]);

        AdminAuditLog::record('policy.updated', 'LegalPolicy', $policy->id, [
            'slug' => $policy->slug,
            'title' => $policy->title,
            'before_published' => $beforePublished,
            'after_published' => $policy->is_published,
        ]);

        return redirect()->route('admin.policies.index')->with('success', "Legal policy '{$policy->title}' updated successfully.");
    }
}
