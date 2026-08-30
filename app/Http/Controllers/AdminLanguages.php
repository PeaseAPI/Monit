<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 多语言文案编辑
 * 规格书 §6.3.5 / §16.7 / 附B：AdminLanguages
 *
 * 语言文件为 lang/{code}.json 单文件（键值对），本控制器提供键值网格编辑。
 */
class AdminLanguages extends Controller
{
    private Filesystem $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function index()
    {
        $languages = [];
        foreach ($this->availableLocales() as $code) {
            $strings = $this->loadStrings($code);
            $languages[$code] = [
                'count' => count($strings),
                'is_default' => $code === config('app.locale'),
                'mtime' => $this->files->lastModified(lang_path($code.'.json')),
            ];
        }

        return view('admin.languages.index', compact('languages'))->with('adminNav', 'languages');
    }

    public function edit(string $code)
    {
        abort_unless(in_array($code, $this->availableLocales(), true), 404);

        return view('admin.languages.edit', [
            'code' => $code,
            'strings' => $this->loadStrings($code),
        ])->with('adminNav', 'languages');
    }

    /**
     * 保存键值编辑：values[key] = 新文案；仅更新已存在键，忽略新增键外的空值
     */
    public function update(Request $request, string $code): RedirectResponse
    {
        abort_unless(in_array($code, $this->availableLocales(), true), 404);

        $validated = $request->validate([
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'string', 'max:4096'],
        ]);

        $strings = $this->loadStrings($code);
        $changed = 0;

        foreach ($validated['values'] as $key => $value) {
            if (array_key_exists($key, $strings) && $value !== null && $value !== $strings[$key]) {
                $strings[$key] = $value;
                $changed++;
            }
        }

        $this->files->put(
            lang_path($code.'.json'),
            json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
        );

        return redirect()->route('admin.languages.edit', $code)
                        ->with('success', __('msg.language_updated', ['count' => $changed]));
    }

        public function create()
    {
        return view('admin.languages.create')->with('adminNav', 'languages');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
            'name' => ['required', 'string', 'max:256'],
        ]);

        $code = $validated['code'];
        $path = lang_path($code . '.json');

        if (! $this->files->exists($path)) {
            $this->files->put($path, '{}');
        }

        return redirect()->route('admin.languages.index')
                        ->with('success', __('msg.language_created', ['name' => $validated['name']]));
    }

    private function availableLocales(): array
    {
        $locales = [];
        foreach ($this->files->glob(lang_path('*.json')) as $file) {
            $locales[] = basename($file, '.json');
        }

        return $locales ?: [config('app.locale')];
    }

    private function loadStrings(string $code): array
    {
        $path = lang_path($code.'.json');
        if (! $this->files->exists($path)) {
            return [];
        }

        $decoded = json_decode($this->files->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
