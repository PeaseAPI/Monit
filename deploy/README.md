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

## 部署后必查清单

1. `php artisan storage:link`（公开磁盘软链）
2. `php artisan migrate --force`（数据库结构）
3. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
4. Cron 条目：`* * * * * cd /var/www/monit && php artisan schedule:run >> /dev/null 2>&1`
5. 队列Worker（启用队列时）：`php artisan queue:work --tries=3`
