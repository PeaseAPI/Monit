<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

/**
 * .env 安全读写（后台「支付网关密钥」设置组专用）
 *
 * 关联：上游 AdminSettings::update(payment_gateways)；下游 .env / config(services.php)
 *
 * 安全设计：
 * - 键白名单：仅允许 ^[A-Z][A-Z0-9_]*$，防止把换行/等号注入键位伪造任意 env 键（如 APP_KEY）
 * - 值转义：含空白/引号/井号/换行的值用双引号包裹并转义 \ 与 "，换行写为 \n 字面量
 *   （phpdotenv 双引号语义），杜绝通过值注入追加新 env 行
 * - 多行旧值：解析式定位 KEY 的行区间（双引号未闭合时继续吞行），整体替换，不残留半截旧值
 * - 原子写：先写同目录临时文件再 rename，避免写一半崩溃损坏 .env
 * - 文件权限：新建时 0640（含密钥，避免同机其他用户读取）
 */
class EnvWriter
{
    public function __construct(
        protected ?string $envPath = null,
    ) {}

    public function path(): string
    {
        return $this->envPath ?? base_path('.env');
    }

    /**
     * 读取一个 env 键的当前原始值（去除引号与转义）；不存在返回 null
     */
    public function read(string $key): ?string
    {
        $this->assertValidKey($key);

        foreach ($this->entries() as $entryKey => $entry) {
            if ($entryKey === $key) {
                return $entry['value'];
            }
        }

        return null;
    }

    /**
     * 批量读取（键 => 值；不存在的键不含在结果中）
     *
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function readMany(array $keys): array
    {
        $entries = $this->entries();
        $result = [];

        foreach ($keys as $key) {
            if (isset($entries[$key])) {
                $result[$key] = $entries[$key]['value'];
            }
        }

        return $result;
    }

    /**
     * 写入（值非空）或删除（空值）一个 env 键
     */
    public function write(string $key, ?string $value): void
    {
        $this->assertValidKey($key);

        $path = $this->path();
        $content = file_exists($path) ? (string) file_get_contents($path) : '';

        $lines = $content === '' ? [] : explode("\n", $content);

        // 定位既有键的行区间 [start, end]（多行双引号值整体视为一条）
        $span = $this->findSpan($lines, $key);

        $replacement = ($value === null || $value === '')
            ? []  // 空值 → 删除该键
            : [$key.'='.$this->encodeValue($value)];

        if ($span === null) {
            if ($replacement !== []) {
                // 追加到文件尾部：先收敛尾部连续空行为恰好一个空行分隔
                while ($lines !== [] && end($lines) === '') {
                    array_pop($lines);
                }
                if ($lines !== []) {
                    $lines[] = '';
                }
                foreach ($replacement as $line) {
                    $lines[] = $line;
                }
            }
        } else {
            array_splice($lines, $span[0], $span[1] - $span[0] + 1, $replacement);
        }

        $newContent = implode("\n", $lines);
        if ($newContent !== '' && ! str_ends_with($newContent, "\n")) {
            $newContent .= "\n";
        }

        $this->atomicPut($path, $newContent);
    }

    /**
     * 值编码：安全写入 .env 一行
     */
    protected function encodeValue(string $value): string
    {
        // 换行统一转 \n 字面量（phpdotenv 双引号语义），保证一行一条记录
        $value = str_replace(["\r\n", "\r", "\n"], '\\n', $value);

        if ($value !== '' && preg_match('/[\s"\'#\\\\]/', $value)) {
            $value = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    /**
     * 解析 .env 为 key => ['value' => 还原值] 映射（只关心 KEY= 行，注释/空行跳过）
     *
     * @return array<string, array{value: string}>
     */
    protected function entries(): array
    {
        $path = $this->path();

        if (! file_exists($path)) {
            return [];
        }

        $lines = explode("\n", (string) file_get_contents($path));
        $entries = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $m)) {
                $key = $m[1];
                $raw = $m[2];

                // 双引号值未闭合 → 继续吞行（多行值）
                if (str_starts_with($raw, '"')) {
                    while ($i + 1 < $count && ! $this->quotesClosed($raw)) {
                        $i++;
                        $raw .= "\n".$lines[$i];
                    }
                }

                $entries[$key] = ['value' => $this->decodeValue($raw)];
            }

            $i++;
        }

        return $entries;
    }

    /**
     * 双引号值是否闭合（未转义引号数为偶数即平衡；忽略 \" 转义）
     */
    protected function quotesClosed(string $raw): bool
    {
        $closed = true;
        $len = strlen($raw);

        for ($j = 0; $j < $len; $j++) {
            if ($raw[$j] === '\\') {
                $j++; // 跳过被转义字符

                continue;
            }
            if ($raw[$j] === '"') {
                $closed = ! $closed;
            }
        }

        return $closed;
    }

    /**
     * 还原 .env 值语义（双引号转义解析 / 单引号原样 / 无引号截断内联注释）
     */
    protected function decodeValue(string $raw): string
    {
        $raw = trim($raw);

        if (strlen($raw) >= 2 && $raw[0] === '"' && str_ends_with($raw, '"')) {
            return str_replace(['\\"', '\\\\', '\\n'], ['"', '\\', "\n"], substr($raw, 1, -1));
        }

        if (strlen($raw) >= 2 && $raw[0] === "'" && str_ends_with($raw, "'")) {
            return substr($raw, 1, -1);
        }

        // 无引号值：行内 " #" 起注释（phpdotenv 语义）
        $hash = strpos($raw, ' #');

        return $hash === false ? $raw : rtrim(substr($raw, 0, $hash));
    }

    /**
     * 在行数组中定位 KEY= 记录的行区间（含多行双引号值）；未找到返回 null
     *
     * @param  list<string>  $lines
     * @return array{0: int, 1: int}|null
     */
    protected function findSpan(array $lines, string $key): ?array
    {
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            if (preg_match('/^('.preg_quote($key, '/').')=(.*)$/', $lines[$i], $m)) {
                $start = $i;
                $raw = $m[2];

                if (str_starts_with($raw, '"')) {
                    while ($i + 1 < $count && ! $this->quotesClosed($raw)) {
                        $i++;
                        $raw .= "\n".$lines[$i];
                    }
                }

                return [$start, $i];
            }
        }

        return null;
    }

    protected function assertValidKey(string $key): void
    {
        if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
            throw new InvalidArgumentException("Illegal .env key: {$key}");
        }
    }

    /**
     * 原子写：临时文件 + rename，避免部分写入损坏 .env
     */
    protected function atomicPut(string $path, string $content): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            throw new RuntimeException("Directory does not exist: {$dir}");
        }

        $tmp = $path.'.tmp.'.bin2hex(random_bytes(4));

        if (file_put_contents($tmp, $content) === false) {
            throw new RuntimeException("Failed to write {$tmp}");
        }

        @chmod($tmp, 0640);

        if (! @rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException("Failed to replace {$path}");
        }
    }
}
