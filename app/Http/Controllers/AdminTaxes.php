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

    /**
     * 批量导入税费（CSV）
     * 规格书 §6.3.3：/admin/taxes-import
     */
    public function importForm()
    {
        return view('admin.taxes.import')->with('adminNav', 'taxes');
    }

    /**
     * 处理 CSV 导入
     * CSV 格式：name,description,value,value_type,type,billing_type,countries
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // skip header
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) {
                continue;
            }

            Tax::create([
                'name' => $row[0],
                'description' => $row[1] ?? null,
                'value' => (float) $row[2],
                'value_type' => $row[3] ?? 'percentage',
                'type' => $row[4] ?? 'exclusive',
                'billing_type' => $row[5] ?? 'personal',
                'countries' => $row[6] ?? null,
            ]);
            $count++;
        }

        fclose($handle);

        return redirect()->route('admin.taxes.index')
            ->with('success', __('msg.taxes_imported', ['count' => $count]));
    }
}
