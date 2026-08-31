<?php

namespace App\Console\Commands;

use App\Services\LicenseManager;
use Illuminate\Console\Command;

/**
 * License 自签发工具（规格书 §15.2）
 *
 * 用法：
 *   php artisan monit:license-generate                       # 生成本机 license（域名取 APP_URL，有效期 1 年）
 *   php artisan monit:license-generate --domains=a.com,b.com --expires=2027-12-31
 *
 * 密钥对保存在 storage/app/license-keypair.json（仅签发方持有），
 * 其中的公钥应写入 config/monit.php 的 license.public_key 或 MONIT_LICENSE_PUBLIC_KEY 环境变量。
 */
class LicenseGenerateCommand extends Command
{
    protected $signature = 'monit:license-generate
        {--domains= : 授权域名，逗号分隔（默认取 APP_URL host）}
        {--expires= : 有效期 Y-m-d（默认一年后）}
        {--id= : License ID（默认 LIC- 随机）}
        {--features= : 功能开关 JSON 串，如 {"white_label":true}}
        {--out= : 输出路径（默认 storage/app/license.json）}';

    protected $description = 'Generate an Ed25519-signed offline license (self-issuing tool)';

    public function handle(): int
    {
        $keypairPath = storage_path('app/license-keypair.json');

        // 1. 获取或生成密钥对
        if (is_file($keypairPath)) {
            $keypair = json_decode((string) file_get_contents($keypairPath), true);
        } else {
            $seed = sodium_crypto_sign_keypair();
            $keypair = [
                'public_key' => bin2hex(sodium_crypto_sign_publickey($seed)),
                'secret_key' => bin2hex(sodium_crypto_sign_secretkey($seed)),
            ];
            file_put_contents($keypairPath, json_encode($keypair, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            chmod($keypairPath, 0600);
            $this->info('Keypair generated: '.$keypairPath);
            $this->warn('Set config license.public_key = '.$keypair['public_key']);
        }

        if (empty($keypair['secret_key'])) {
            $this->error('Invalid keypair file.');

            return self::FAILURE;
        }

        // 2. 组装 License 数据
        $domains = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($this->option('domains') ?: parse_url(config('app.url'), PHP_URL_HOST))),
        )));

        $license = [
            'license_id' => (string) ($this->option('id') ?: 'LIC-'.strtoupper(bin2hex(random_bytes(4)))),
            'product' => LicenseManager::PRODUCT,
            'domains' => $domains,
            'max_domains' => max(1, count($domains)),
            'expires' => (string) ($this->option('expires') ?: now()->addYear()->format('Y-m-d')),
            'features' => $this->parseFeatures(),
            'issued_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ];

        // 3. Ed25519 签名（对规范 JSON detached 签名）
        $signature = sodium_crypto_sign_detached(
            LicenseManager::canonicalJson($license),
            hex2bin($keypair['secret_key']),
        );
        $license['signature'] = bin2hex($signature);

        // 4. 写出
        $out = (string) ($this->option('out') ?: LicenseManager::licensePath());
        file_put_contents($out, json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $this->info('License written: '.$out);
        $this->line(json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    protected function parseFeatures(): array
    {
        $raw = (string) $this->option('features');

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
