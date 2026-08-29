<?php

/**
 * 插件：💰 Affiliate（规格书 §14.3 #3）
 * 推荐返佣：推荐链接 + Cookie 跟踪 + 佣金计算 + 提现管理。
 * 核心能力（referral_key / ?ref= 跟踪 / 提现）随包内置，本插件激活后开启前台入口与佣金结算。
 */

return [
    'id' => 'affiliate',
    'title' => '💰 Affiliate',
    'description' => '联盟返佣计划：激活后开启 /referrals 推荐入口与佣金提现；停用即关闭入口。佣金比例与 Cookie 有效期可配置。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'commission_percentage' => [
            'type' => 'text',
            'label' => '佣金比例（%，0-100）',
            'default' => '20',
        ],
        'cookie_days' => [
            'type' => 'text',
            'label' => '推荐 Cookie 有效期（天）',
            'default' => '30',
        ],
        'min_withdrawal' => [
            'type' => 'text',
            'label' => '最低提现金额',
            'default' => '50',
        ],
    ],
];
