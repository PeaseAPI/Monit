<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 税费管理
 * 规格书 §6.3.3：AdminTaxes
 */
class AdminTaxes extends Controller
{
    public function index()
    {
        $taxes = Tax::orderBy('tax_id')->get();

                return view('admin.taxes.index', compact('taxes'))->with('adminNav', 'taxes');
    }

    public function create()
    {
                return view('admin.taxes.create')->with('adminNav', 'taxes');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string'],
            'value' => ['required', 'numeric'],
            'value_type' => ['required', 'in:percentage,fixed'],
            'type' => ['required', 'in:inclusive,exclusive'],
            'billing_type' => ['required', 'in:personal,business'],
            'countries' => ['nullable', 'json'],
        ]);

        Tax::create($validated);

        return redirect()->route('admin.taxes.index')
                        ->with('success', __('msg.tax_created', ['name' => $validated['name']]));
    }

    public function edit(int $taxId)
    {
        $tax = Tax::findOrFail($taxId);

                return view('admin.taxes.edit', compact('tax'))->with('adminNav', 'taxes');
    }

    public function update(Request $request, int $taxId): RedirectResponse
    {
        $tax = Tax::findOrFail($taxId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string'],
            'value' => ['required', 'numeric'],
            'value_type' => ['required', 'in:percentage,fixed'],
            'type' => ['required', 'in:inclusive,exclusive'],
            'billing_type' => ['required', 'in:personal,business'],
            'countries' => ['nullable', 'json'],
        ]);

        $tax->update($validated);

        return redirect()->route('admin.taxes.index')
                        ->with('success', __('msg.tax_updated'));
    }

    public function destroy(int $taxId): RedirectResponse
    {
        Tax::find($taxId)->delete();

        return redirect()->route('admin.taxes.index')
                        ->with('success', __('msg.tax_deleted'));
    }
}
