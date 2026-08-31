<div align="center">

# 📊 Monit

**开源自托管网站分析平台 —— 你的数据，100% 归你所有**

轻量部署 · 隐私优先 · 无第三方追踪 · 六语言原生支持

Laravel 12 · PHP 8.2+ · MySQL · Redis · Docker 一键部署

</div>

---

## 🎯 为什么选择 Monit？

你受够了 Google Analytics 的复杂和封锁、受够了 SaaS 工具按 PV 收费、也担心访客数据躺在别人的服务器上？

Monit 让你用一台自己的服务器，拥有一个**企业级、可商用、可二次开发**的完整分析平台：

| | 云端 SaaS 分析工具 | **Monit** |
|---|---|---|
| 数据归属 | 第三方服务器 | ✅ **100% 自有数据库** |
| 费用 | 按 PV 阶梯收费 | ✅ 一次部署，不限 PV |
| 数据留存 | 受限 / 收费 | ✅ 自主设置保留策略 |
| 定制能力 | 几乎为零 | ✅ 插件系统 + 二开友好 |
| 商用能力 | — | ✅ 内置**套餐计费 + 多支付**，可直接开 SaaS |

## ✨ 核心功能

### 📈 专业级网站分析
- **实时看板**：在线访客、PV/UV、跳出率、停留时长实时刷新
- **全景指标**：来源 / UTM / 渠道分组、地域（国家·城市）、设备 / OS / 浏览器、24 小时时段、入口页 / 离开页、新老访客忠诚度、搜索词解析
- **双引擎模式**：
  - **Advanced** —— 访客 → 会话 → 事件三级模型，完整行为细节
  - **Lightweight** —— 单表聚合，低配服务器也能跑

### 🎬 会话回放 · 🔥 热图 · 🎯 目标
- 逐帧回放访客操作（点击 / 滚动 / 表单 / 缩放），gzip 分块存储 + 对象存储冷转存
- 桌面 / 平板 / 手机三档热图快照，点击坐标归一化 + 滚动深度分布
- 目标转化漏斗、事件追踪、出站点击追踪

### 🤝 团队协作
- 多网站管理、自定义域名、批量导入
- 团队邀请与网站级授权、自定义仪表盘视图

### 💳 完整计费生态（可直接商用开 SaaS）
- **22 家支付处理器**（含本土化支付）+ 21 条 Webhook 回调
- 套餐多货币多周期定价、试用、税费、发票、兑换码
- 推荐返佣 + 联盟提现 + 信用票据

### 🔐 认证与安全（本土化全覆盖）
- 邮箱 + 密码 / 手机号 + 短信验证码免密登录、短信找回密码
- **13 家社交登录**：Google、GitHub 等 8 家海外 + QQ / 微信 / 微博 / Gitee / 飞书
- TOTP 两步验证（RFC 6238 纯 PHP 实现）
- Ed25519 离线 License（多域名 + 过期校验）
- 封禁用户会话即时终止

### 🧩 插件系统（7 个内置，一键启停）
PWA · 桌面推送通知 · 联盟返佣 · CDN 转存（S3 / 阿里云 OSS / 腾讯 COS）· 图片优化 · 动态 OG 图 · 邮件防护

### 🌍 六语言 · 白标就绪
zh_CN / zh_TW / en / ru / be / ms，**1662 个语言键全对齐**；落地页主题可切换，品牌全站可替换。

## 🚀 快速开始（3 分钟）

```bash
# 1️⃣ 克隆 & 配置（依赖已随仓库内置，内网/离线环境免 composer install）
git clone https://github.com/PeaseAPI/Monit.git
cd Monit
cp .env.example .env
php artisan key:generate

# 2️⃣ 建库后配置 .env 中的 DB_*，然后迁移
php artisan migrate
php artisan db:seed --class=DemoDataSeeder   # 可选：演示数据

# 3️⃣ 启动
php artisan serve
```

演示账号：`admin@monit.dev` / `password`（管理员）· `pro@monit.dev` · `free@monit.dev`（均 `password`）

接入网站：后台创建网站后，一行脚本即刻采集（`sendBeacon` + `keepalive`，无第三方依赖、不拖慢页面）：

```html
<script async src="https://your-domain.com/pixel/monit.js"
        data-key="你的网站Key"></script>
```

## 🐳 Docker 部署

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

## 📚 完整文档与部署配置

**线上文档中心**（部署后即可访问，无需额外配置）：

| 页面 | 地址 | 仓库文件 |
|---|---|---|
| 产品介绍 | `https://your-domain.com/docs/index.html` | `public/docs/index.html` |
| 安装指南 | `https://your-domain.com/docs/install.html` | `public/docs/install.html` |
| 使用手册 | `https://your-domain.com/docs/usage.html` | `public/docs/usage.html` |

**生产部署配置（开箱即用，无需手写伪静态）：**

| 文件 | 内容 |
|---|---|
| [`deploy/nginx/monit.conf`](deploy/nginx/monit.conf) | Nginx 完整站点配置：HTTPS 跳转 + Laravel 伪静态 + 静态资源 30 天长缓存 + Gzip + 像素端点 CORS/日志优化 + 安全响应头 |
| [`deploy/apache/monit.conf`](deploy/apache/monit.conf) | Apache 虚拟主机：mod_rewrite 伪静态 + PHP-FPM + 静态缓存 + Gzip + 安全头 |
| [`deploy/README.md`](deploy/README.md) | 一分钟接入命令（certbot 签证书 / a2ensite）+ 部署后必查清单（storage:link、缓存、Cron、队列） |
| `public/.htaccess` | Laravel 标准伪静态（Apache `AllowOverride All` 后自动生效） |

## 📊 REST API

`/api-documentation` 内置交互式文档，`Authorization: Bearer <api_key>` 鉴权，覆盖 17 类资源端点 —— 拿来即用的开放平台。

## 🛠 管理后台

34 个控制器覆盖全站运营：系统设置（主站 / 认证 / 支付 / 存储 / 域名…）、用户 / 网站 / 套餐 / 支付 / 税费 / 兑换码、页面 CMS、博客、广播、推送、插件、日志（CSV 导出）、运营统计；7 组计划任务自动完成套餐过期、数据保留、回放转存、到期提醒、邮件报表等。

## 🧪 质量保障

```bash
php artisan test   # 191 tests / 637 assertions 全绿
```

覆盖：像素采集、统计聚合、认证全场景、支付 Webhook 安全、计划限额、对象存储多驱动、Cron 任务、插件生命周期、License、封禁守卫、路由与语言键完整性等。另配 PHPStan (Larastan) level 5 静态分析。

## 📁 项目结构

```
app/
├── Http/Controllers/     # 前台 ~30 + 管理后台 34 + API v1
├── Http/Middleware/      # Admin / API Key / 维护模式 / 封禁守卫 / 计划限额
├── Models/               # 28 张表对应模型
├── Services/             # 统计 / 采集 / 回放 / 热图 / 支付 / 短信 / TOTP / License / 插件
└── Support/              # 动态配置 / 品牌出口 / 多货币
plugins/                  # 7 个内置插件
resources/views/themes/   # 落地页主题（后台可切换）
public/assets/pixel/      # 采集 SDK（原生 JS，零依赖）
deploy/                   # Nginx / Apache 生产配置（开箱即用）
```

## 📄 许可证

[MIT License](LICENSE) —— 可自由使用、修改、分发与商用，仅需保留版权声明。

<div align="center">

**如果 Monit 对你有帮助，欢迎 Star ⭐ 支持！**

</div>

