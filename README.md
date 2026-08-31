# Monit — 自托管网站分析平台

Monit 是一款基于 Laravel 的**自托管、隐私优先的网站统计分析平台**（Self-hosted Web Analytics），按《Monit 完整开发规格书》全量实现，提供访客统计、会话回放、热图、目标转化、出站点击等 Advanced/Lightweight 双模式分析能力，并内置计费、套餐、团队协作与本土化能力（短信验证、22 家支付处理器、阿里云 OSS/腾讯 COS 转存、13 家社交登录）。

## 核心特性

**数据采集与分析**
- `/pixel/{key}` 轻量采集端点：会话 / 页面浏览 / 事件子项（click·scroll·form·resize）/ 出站点击 / 目标转化
- Advanced 模式：访客 → 会话 → 事件三级模型，支持会话回放（gzip 分块存储 + S3/OSS/COS 冷转存）
- Lightweight 模式：单表聚合，低资源场景
- 热图：桌面/平板/手机三档快照 + 归一化点击坐标 + 滚动深度分布
- 统计指标：实时在线、PV/UV、跳出率、停留时长、来源/UTM、地域、设备/OS/浏览器，支持多维护滤（AnalyticsFilters）
- GA/CNZZ 对标扩展（M21）：24 小时时段分析、渠道分组（直接/自然/社交/引荐/广告活动）、入口页/离开页、搜索词（百度/Google/Bing/搜狗/360 等解析）、忠诚度（新老访客 + 访问频次/深度/时长）、热门城市/语言/分辨率

**用户中心**
- 仪表盘 / 实时统计 / 页面浏览 / 访客 / 会话 / 热图 / 回放 / 标注 / 目标
- 网站 CRUD 与批量导入、自定义域名
- 团队协作（邀请 / 网站级授权）、仪表盘视图
- 账户：资料、API Key、偏好、日志、支付、套餐、兑换码、TOTP 两步验证、账户删除

**管理后台（34 个控制器）**
- 系统设置（主站 / 认证 / 社交登录 / 短信验证 / 分析 / 支付 / 计划任务 / 域名 / 存储…）
- 用户 / 网站 / 套餐 / 支付 / 税费 / 兑换码 / 页面 CMS / 博客 / 广播 / 推送 / 语言 / 插件 / 日志（CSV 导出）/ 统计
- 计划任务 7 组 + 3 子任务（套餐过期、未激活清理、回放清理与转存、数据保留、到期提醒、广播、邮件报表）

**认证与安全**
- 邮箱 + 密码登录、邮箱激活、找回密码
- 手机号登录（手机号+密码 / 手机号+短信验证码免密）、短信找回密码、短信绑定手机（4 场景）
- 短信服务商：阿里云 dysmsapi / 腾讯云 TC3 / log 调试；验证码 Cache 存储、60s 节流、防爆破（5 次作废）
- 13 家社交登录（8 海外 + QQ/微信/微博/Gitee/飞书）
- TOTP 两步验证（RFC 6238 纯 PHP 实现）
- Ed25519 离线 License（多域名 + 过期校验）
- 封禁用户会话即时终止（`EnsureUserActive` 中间件）

**计费生态**
- 22 家支付处理器 + 21 条 Webhook 回调
- 套餐（多货币多周期定价）、试用、税费、发票、信用票据、兑换码、推荐返佣、联盟提现
- 落地页货币切换器（CNY/USD/EUR/GBP/JPY）

**插件系统（7 个）**
PWA / 推送通知 / 联盟返佣 / CDN 转存（S3/阿里云 OSS/腾讯 COS）/ 图片优化 / 动态 OG 图 / 邮件防护 —— 统一安装/激活/停用/卸载生命周期 + Admin 设置页


## 技术栈

| 层 | 选型 |
|---|---|
| 后端 | PHP 8.2+ / Laravel 12（Blade + Tailwind CSS v4） |
| 数据库 | MySQL 8（28 张迁移表，兼容 MariaDB/PostgreSQL 常规用法） |
| 缓存/队列 | Redis（会话回放分块、验证码、限流） |
| 采集脚本 | 原生 JS `monit.js`（无第三方依赖） |
| 部署 | Docker / docker-compose / 传统 LNMP |

## 快速开始

```bash
# 1. 依赖已随仓库内置（vendor/ 已提交，composer.lock 锁定版本）
#    离线/内网环境无需 composer install 即可运行；如需更新依赖再执行 composer install

# 2. 环境配置
cp .env.example .env
php artisan key:generate

# 3. 数据库（MySQL：先建库，再改 .env 中 DB_* 配置）
php artisan migrate
php artisan db:seed --class=DemoDataSeeder   # 演示数据（可选）

# 4. 启动
php artisan serve
```

演示账号：`admin@monit.dev` / `password`（管理员），`pro@monit.dev`、`free@monit.dev`（普通用户）。

## Docker 部署

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

详见 `docker-compose.yml` 与 `Dockerfile`。

## 测试

```bash
php artisan test        # 112 tests / 374 assertions 全绿
```

覆盖范围：像素采集、统计聚合、认证（含短信 4 场景）、对象存储多驱动、支付 Webhook、Cron 任务、计划限额、插件、License、封禁守卫、日志下载、货币切换等。

## 文档

- `Monit-完整开发规格书.md` — 完整功能规格（位于仓库上级文档目录）
- `TASKS.md` — M0–M25 分段开发记录与验收清单
- `lang/` — 六语言 1356 键（zh_CN / zh_TW / en / ru / be / ms，键集强一致）
- REST API：`/api-documentation` 页面；`Authorization: Bearer <api_key>` 鉴权，17 类资源端点
- `docs/二次开发指南.md` — 架构/生命周期/扩展点/6 个二开实操场景
- `docs/66audit-功能全解析.md` — 66SEO 审计 SaaS 功能全量解析（M26+ SEO 模块参考）
- `docs/phpRank-功能全解析.md` — phpRank SEO 报告平台功能全量解析（M26+ SEO 模块参考）
- `docs/SEO模块融合方案.md` — M26+ SEO 模块融合蓝图（数据模型/服务/路由/套餐/调度/里程碑）
- `public/docs/` — 产品介绍 / 安装指南 / 使用手册（HTML，线上 `/docs/index.html`）
- `deploy/nginx/monit.conf`、`deploy/apache/monit.conf` — 生产伪静态与缓存规则（无需手写）

## 项目结构（关键目录）

```
app/
├── Console/Commands/        # 安装向导、License 生成等 Artisan 命令
├── Http/Controllers/        # 前台 ~30 + Admin 34 + Api/v1 控制器
├── Http/Middleware/         # Admin / API Key / 维护模式 / 封禁守卫 / 计划限额
├── Models/                  # 28 张表对应模型
├── Services/                # 统计 / 采集 / 回放 / 热图 / 支付 / 短信 / TOTP / License / 插件
└── Support/                 # Settings 动态配置 / Brand 品牌出口 / Currency 多货币
plugins/                     # 7 个内置插件（PWA/推送/联盟/CDN转存/图片优化/OG图/邮件防护）
resources/views/themes/      # 落地页主题（默认 default，后台可切换，模板机制）
public/assets/pixel/monit.js # 采集 SDK（sendBeacon + keepalive）
deploy/                      # Nginx / Apache 生产配置
```

## 许可证

本项目采用 [MIT License](LICENSE) 开源协议 —— 可自由使用、修改、分发与商用，仅需保留版权声明。

---

> Monit 为规格书驱动的完整实现项目（M0–M25 已交付，M26+ SEO 模块规划中），持续迭代中。
