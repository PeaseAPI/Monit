<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 - 自定义域名管理
 * 规格书 §8：/api/domains
 */
class DomainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->user()->domains()->orderByDesc('domain_id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:256'],
            'scheme' => ['nullable', 'in:http,https'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $domain = $this->user()->domains()->create([
            'host' => $validated['host'],
            'scheme' => $validated['scheme'] ?? 'https',
            'is_enabled' => $validated['is_enabled'] ?? true,
            'datetime' => now(),
        ]);

        return response()->json(['message' => __('msg.domain_created'), 'domain' => $domain], 201);
    }

    public function update(Request $request, int $domainId): JsonResponse
    {
        $domain = $this->user()->domains()->where('domain_id', $domainId)->firstOrFail();

        $validated = $request->validate([
            'host' => ['sometimes', 'string', 'max:256'],
            'scheme' => ['sometimes', 'in:http,https'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $domain->update($validated);

        return response()->json(['message' => __('msg.domain_updated'), 'domain' => $domain]);
    }

    public function destroy(Request $request, int $domainId): JsonResponse
    {
        $this->user()->domains()->where('domain_id', $domainId)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.domain_deleted')]);
    }
}
