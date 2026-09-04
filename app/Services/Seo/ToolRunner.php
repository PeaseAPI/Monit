<?php

namespace App\Services\Seo;

use App\Services\Seo\Tools\DevTools;
use App\Services\Seo\Tools\MinifyTools;
use App\Services\Seo\Tools\NetworkTools;
use App\Services\Seo\Tools\SearchPreviewTools;
use App\Services\Seo\Tools\SeoCheckTools;
use App\Services\Seo\Tools\TextTools;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * SEO 工具中心分发器
 *
 * 工具注册表见 config/seo.php tools 段；
 * 后台可按 slug 停用（settings seo.seo_disabled_tools）
 */
class ToolRunner
{
    /** 工具分类 => 处理器类 */
    protected const HANDLER_MAP = [
        'network' => NetworkTools::class,
        'seo_check' => SeoCheckTools::class,
        'preview' => SearchPreviewTools::class,
        'minify' => MinifyTools::class,
        'text' => TextTools::class,
        'dev' => DevTools::class,
    ];

    protected array $instances = [];

    /**
     * 全部可用工具（后台停用 / requires 未配置的过滤）
     */
    public function catalog(): array
    {
        $disabled = Settings::get('seo.seo_disabled_tools', []);
        // 兼容后台 textarea（每行/逗号分隔 slug）与数组两种存储
        $disabled = is_string($disabled)
            ? array_filter(array_map('trim', preg_split('/[\r\n,]+/', $disabled)))
            : (array) $disabled;

        return collect(config('seo.tools', []))
            ->reject(fn (array $meta, string $slug) => in_array($slug, $disabled, true))
            ->filter(function (array $meta) {
                if (empty($meta['requires'])) {
                    return true;
                }

                $value = Settings::get($meta['requires']);

                return $value === true || $value === 'true' || (is_string($value) && trim($value) !== '');
            })
            ->all();
    }

    public function categoryTools(string $category): array
    {
        return collect($this->catalog())
            ->filter(fn (array $meta) => ($meta['category'] ?? '') === $category)
            ->all();
    }

    /**
     * 执行工具
     *
     * @return array{ok:bool, error?:string, data:array<string,mixed>, text?:string}
     */
    public function run(string $slug, array $input): array
    {
        $catalog = $this->catalog();

        if (! array_key_exists($slug, $catalog)) {
            throw new InvalidArgumentException(__('seo.tool_not_found'));
        }

        $meta = $catalog[$slug];
        $class = self::HANDLER_MAP[$meta['category']] ?? null;

        if ($class === null || ! method_exists($this->instance($class), $meta['handler'])) {
            return ['ok' => false, 'error' => __('seo.tool_not_available'), 'data' => []];
        }

        try {
            return $this->instance($class)->{$meta['handler']}($input);
        } catch (\Throwable $e) {
            Log::error("SEO tool {$slug} error: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return ['ok' => false, 'error' => __('seo.tool_not_available'), 'data' => []];
        }
    }

    protected function instance(string $class): object
    {
        return $this->instances[$class] ??= new $class;
    }
}
