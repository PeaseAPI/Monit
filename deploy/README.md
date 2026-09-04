# Monit 部署配置目录

本目录提供生产部署所需的 Web 服务器规则，**无需自行编写伪静态**。

## 文件清单

| 文件 | 用途 |
|------|------|
| `nginx/monit.conf` | Nginx 完整站点配置（HTTPS + 伪静态 + 静态缓存 + Gzip + 像素端点优化） |
| `apache/monit.conf` | Apache 虚拟主机（mod_rewrite + FPM + 缓存策略） |
| 项目根 `docker-compose.yml` | Docker Compose 一键部署（自带 nginx） |
| `public/.htaccess` | Laravel 标准伪静态（Apache `AllowOverride All` 后自动生效） |

## Nginx 快速接入

```bash
sudo cp deploy/nginx/monit.conf /etc/nginx/sites-available/monit.conf
sudo ln -s /etc/nginx/sites-available/monit.conf /etc/nginx/sites-enabled/
# 编辑 server_name / root / FPM socket 后：
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d your-domain.com
```

## Apache 快速接入

```bash
sudo a2enmod rewrite headers expires ssl proxy_fcgi
sudo cp deploy/apache/monit.conf /etc/apache2/sites-available/monit.conf
sudo a2ensite monit
sudo apachectl configtest && sudo systemctl reload apache2
```

## 首次部署：网页安装向导（推荐）

自 v1.1 起，**无需手动执行任何初始化命令**：代码上传 + 目录权限就绪后，直接浏览器访问站点域名，会自动 302 跳转到 `/install` 五步向导（环境检测 → 目录权限 → 数据库配置（MySQL 连接测试 + 自动建库 + 自动生成 `APP_KEY` + 迁移） → 站点与管理员 → 完成）。向导只写入核心数据（free/pro 套餐 + 平台设置），**不会**创建演示账号；完成后写入 `storage/installed.lock`，向导自动失效。

前置条件只有两条（SSH 执行一次）：

```bash
# 1) 依赖安装（首次）：composer install --no-dev --optimize-autoloader
# 2) 目录权限（PHP-FPM 运行用户，宝塔为 www）：
chown -R www:www storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

向导完成后再执行下方清单第 5、6、7 步（storage:link / 三缓存 / cron）即可。

### 为什么向导必须能免 Session 运行

未安装时 `sessions` 表、`APP_KEY` 均未就绪，任何走标准 web 中间件组的请求（含向导自身）都会 500——因此 `/install` 挂在**无中间件路由组**（`routes/install.php`），并由全局最前的 `EnsureInstalled` 中间件负责「未安装 → 302 /install」。线上若仍出现数据库 500，先检查 `storage/installed.lock` 是否存在（删除即回到向导）。

## 部署后必查清单（CLI 方式，与网页向导二选一）

> 若已用网页向导完成安装，第 1-4 步可跳过，只执行 5-8 步。

1. `cp .env.example .env && php artisan key:generate`（生成并写入 `APP_KEY`，缺失会导致首页 500：`MissingAppKeyException - No application encryption key has been specified`；同时确认 `APP_ENV=production`、`APP_DEBUG=false`，避免线上暴露堆栈）
2. 目录权限（PHP-FPM 运行用户，宝塔为 `www`）：`chown -R www:www storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache`（storage 需写缓存/会话/日志，bootstrap/cache 需写配置缓存）
3. 准备 MySQL 数据库：`mysql -e "CREATE DATABASE monit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"` 并授权业务账户（也可直接走网页向导，由向导自动建库）；随后 `php artisan migrate --force` 建表
4. `php artisan db:seed --force`（写入 free/pro 套餐 + 平台设置，缺套餐前台无法正常工作；**默认不含任何演示账号**——本地需要演示数据时另跑 `php artisan db:seed --class=DemoDataSeeder --force`）
5. `php artisan storage:link`（公开磁盘软链）
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`（⚠️ 必须在 1-4 步全部完成后执行——config 缓存会把当时的 `APP_KEY` / `DB_*` 值固化，之后改 `.env` 不生效；每次修改 `.env` 后需先 `php artisan config:clear` 再重建缓存）
7. Cron 条目：`* * * * * cd /var/www/monit && php artisan schedule:run >> /dev/null 2>&1`
8. **GeoIP 库文件（重要！缺失时统计的国家/大洲维度全部显示"未知"）**：`sudo -u www php artisan geoip:update`——自动下载 db-ip 免费国家库（~5MB，免注册）到 `storage/app/geoip/country.mmdb`；调度器每月 1 日 02:00 自动更新。状态可在 后台 → 设置 → 健康检查 查看。
9. 队列Worker（启用队列时）：`php artisan queue:work --tries=3`（修改 `.env` / 重建缓存后需重启 Worker）

## 常见 500 排查（生产实录）

| 报错 | 根因 | 修复 |
|------|------|------|
| `MissingAppKeyException` | `.env` 不存在或无 `APP_KEY`（`.env` 被 gitignore，服务器无此文件） | `php artisan key:generate`（或直接走网页向导） |
| `SQLSTATE[HY000] [1045] Access denied for user` | MySQL 用户名/密码错误或未授权 | 核对 `.env` 的 `DB_USERNAME`/`DB_PASSWORD`；`GRANT ALL ON monit.* TO 'monit'@'%'` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL 未运行或 host/port 错误 | 确认 MySQL 运行中、`DB_HOST`（容器/远程场景用主机名而非 127.0.0.1）、防火墙放行 3306 |
| `SQLSTATE[HY000] [1049] Unknown database` | 目标库不存在 | 网页向导会自动建库；CLI 方式先手动 `CREATE DATABASE monit ... utf8mb4` |

> 经验：**artisan 命令用站点用户执行**（`sudo -u www php artisan migrate --force`），或每次执行后重新 chown——root 创建/改动过的文件属主是 root，www 只读。
