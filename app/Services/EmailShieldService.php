<?php

namespace App\Services;

/**
 * Email Shield 插件（规格 §14.3-6 / §14.9）：邮箱混淆输出防爬虫
 *
 * 将明文邮箱逐字符随机编码为十进制/十六进制 HTML 实体，
 * 浏览器渲染结果与原文一致，但爬虫无法直接从 HTML 源码中提取。
 */
class EmailShieldService
{
    /**
     * 混淆单个邮箱地址
     */
    public function obfuscate(string $email): string
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return e($email);
        }

        $encoded = '';
        foreach (str_split($email) as $char) {
            $encoded .= random_int(0, 1) === 0
                ? '&#'.ord($char).';'
                : '&#x'.dechex(ord($char)).';';
        }

        return $encoded;
    }

    /**
     * 生成可点击的 mailto 混淆链接（包含计划功能门禁：email_shield_is_enabled）
     */
    public function link(?string $email, ?string $label = null): string
    {
        if (! $email || ! $this->isEnabled()) {
            return e($email ?? '');
        }

        $label = $label ?? $email;

        return '<a href="mailto:'.$this->obfuscate($email).'" rel="nofollow noopener">'.$this->obfuscate($label).'</a>';
    }

    /**
     * 功能门禁：settings.analytics 或插件配置启用时生效（规格 §10.2）
     */
    public function isEnabled(): bool
    {
        try {
            $settings = \App\Models\Setting::getGroup('plugins');

            return (bool) ($settings['email_shield_is_enabled'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }
}
