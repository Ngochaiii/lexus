# Triển khai lên VPS — Lexus Thăng Long

Một lần `migrate --seed` + `catalog:images` là ra **bản đầy đủ**: 7 dòng xe,
16 phiên bản (giá theo trang chủ Car-project), 36 màu có ảnh xoay 360°, ảnh
chi tiết/mâm/nội thất, 11 trang tĩnh, 8 bài viết SEO/GEO, banner, menu, form
thu lead. Toàn bộ ảnh nằm sẵn trong `database/seeders/media/` (~50 MB) — máy
chủ **không** cần truy cập Car-project hay lexus.com.

Đã kiểm chứng 24/09/2026: cài mới trên database trống + thư mục ảnh trống →
36 trang đều trả 200, 471 ảnh được tham chiếu đều có file.

## 1. Yêu cầu máy chủ

- PHP **8.3+** với các extension: `pdo_mysql`, `mbstring`, `intl`, `gd`
  (**có WebP** — `catalog:images` cần `imagewebp`), `fileinfo`, `zip`, `curl`.
- MariaDB 10.6+ (hoặc MySQL 8), Nginx, Composer 2.
- Supervisor (chạy queue worker gửi mail báo lead).

Không cần Node/npm (CSS/JS viết tay, không build) và không cần `cwebp`
(chỉ các script nhập ảnh trong `database/seeders/media/*.py` dùng, chạy ở máy dev).

## 2. Cài đặt

```bash
git clone <repo> /var/www/lexus && cd /var/www/lexus
composer install --no-dev --optimize-autoloader
cp .env.production.example .env        # rồi sửa: APP_URL, DB_*, MAIL_*, ADMIN_*, LEAD_NOTIFY_EMAILS
php artisan key:generate
php artisan migrate --force --seed     # dữ liệu + ảnh gốc vào storage/app/public
php artisan catalog:images             # sinh ảnh responsive (srcset) — ~1–3 phút
php artisan storage:link
php artisan filament:optimize
php artisan optimize                    # cache config/route/view/event
chown -R www-data:www-data storage bootstrap/cache
```

**`APP_URL` phải đúng domain thật** (https://…): canonical, sitemap,
robots.txt, llms.txt và JSON-LD đều lấy gốc từ đây.

**Tài khoản admin:** đặt `ADMIN_EMAIL` + `ADMIN_PASSWORD` trong `.env` trước
khi seed. Bỏ trống `ADMIN_PASSWORD` thì seeder (APP_ENV=production) sinh mật
khẩu ngẫu nhiên và in ra **một lần** trên màn hình — lưu lại ngay. Không bao
giờ dùng mật khẩu `password` trên VPS.

## 3. Nginx

```nginx
server {
    server_name lexusthanglong.vn www.lexusthanglong.vn;
    root /var/www/lexus/public;
    index index.php;
    client_max_body_size 20M;

    # Nén chữ (HTML, CSS, JS, JSON-LD, sitemap). Mặc định nginx chỉ nén
    # text/html — thiếu dòng gzip_types thì CSS/JS đi nguyên cục.
    gzip on;
    gzip_vary on;
    gzip_comp_level 5;
    gzip_min_length 1024;
    gzip_types text/css application/javascript text/javascript application/json
               application/ld+json application/xml text/xml text/plain image/svg+xml;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    # CSS/JS luôn gắn ?v=<thời điểm sửa> → cache 1 năm, đổi file là đổi link.
    location ~* \.(css|js)$ {
        expires 1y; add_header Cache-Control "public, max-age=31536000, immutable";
        try_files $uri /index.php?$query_string;
    }
    # Ảnh, font: 30 ngày (ảnh seed có thể được thay cùng tên → không immutable).
    location ~* \.(webp|jpg|jpeg|png|svg|ico|woff2?)$ {
        expires 30d; add_header Cache-Control "public, max-age=2592000";
        try_files $uri /index.php?$query_string;
    }
}
```

Kết quả đo Lighthouse ở local (24/09/2026, qua proxy giả lập đúng cấu hình
nginx trên): Performance 99–100 · Accessibility 100 · Best Practices 100 ·
SEO 100 cho các trang chính, cả mobile lẫn desktop. Lên VPS đo lại bằng
https://pagespeed.web.dev (xem mục 5).

Không có file `public/robots.txt` tĩnh — robots.txt, llms.txt, sitemap.xml
sinh động qua route. Đừng tạo lại file tĩnh (sẽ che route).

Bật HTTPS: `certbot --nginx -d lexusthanglong.vn -d www.lexusthanglong.vn`.

### Cài bằng aaPanel (đã chạy thật 24/09/2026 cho es350h-lexusthanglong.com)

Ba chỗ aaPanel hay làm hỏng Laravel — kiểm tra đủ cả ba:

1. **Running directory = `/public`** và **tắt Anti-XSS (open_basedir)**. Đổi
   Running directory xong aaPanel tự tạo `public/.user.ini` giới hạn PHP chỉ
   đọc được `public/` → Laravel không đọc được `vendor/` → lỗi 500, không có
   `laravel.log`. Sửa: tắt công tắc, hoặc `chattr -i public/.user.ini; rm
   public/.user.ini` rồi `/etc/init.d/php-fpm-83 reload`.
2. **URL rewrite = `laravel5`**. Thiếu thì chỉ `/` chạy, mọi trang khác 404
   của nginx. File: `/www/server/panel/vhost/rewrite/<domain>.conf`, nội dung
   `location / { try_files $uri $uri/ /index.php$is_args$query_string; }`.
3. **Khối cache js/css mặc định của aaPanel** (`location ~ .*\.(js|css)?$`)
   không có `try_files` → `/livewire-xxxx/livewire.min.js` (Laravel sinh ra,
   không phải file thật) bị 404 → trang đăng nhập admin hiện nhưng không bấm
   được. Thay cả hai khối mặc định bằng hai khối `location ~* \.(css|js)$` và
   `location ~* \.(webp|…)$` ở trên — **giữ dòng `try_files`**.

Chạy sau Cloudflare: SSL **Full (strict)** + Origin Certificate dán vào tab
SSL của site; tắt Rocket Loader. Domain phụ (lexus-es.com, thuhalexus.com)
chỉ cần DNS A `@`/`www` → `192.0.2.1` Proxied + Redirect Rule 301
`concat("https://es350h-lexusthanglong.com", http.request.uri.path)`.
Sau mỗi lần sửa cấu hình, Cloudflare → Caching → **Purge Everything**.

## 4. Queue worker (mail báo lead + viết bài Gemini)

**Viết bài bằng Gemini** (Admin → Bài viết → "Sinh bài viết bằng Gemini") chạy
trong queue: một bài có nghiên cứu từ khoá mất 1–3 phút, lúc Gemini quá tải có
thể 5–6 phút — quá giới hạn 100 giây của Cloudflare nên không chạy trong request.
Trang soạn bài tự hỏi lại kết quả 4 giây/lần và điền bài khi xong. Cần trong `.env`:
`GEMINI_API_KEY`, `GEMINI_MAX_OUTPUT_TOKENS=16384`, `GEMINI_TIMEOUT=240`.
`GEMINI_GOOGLE_SEARCH=true` (cần bật Billing ở Google AI Studio) cho phép Gemini
xem các trang đang xếp hạng khi nghiên cứu từ khoá. Nên chạy **2 tiến trình**
worker (Supervisor numprocs=2) để mail báo lead không phải chờ bài viết xong.

Mail thông báo khách để lại số và webhook chạy nền (`QUEUE_CONNECTION=database`).
Thiếu worker thì lead vẫn lưu, nhưng **không có mail báo**. Người nhận mail khai ở
`LEAD_NOTIFY_EMAILS` trong `.env` **trước khi** chạy `db:seed`; đổi về sau thì sửa
`.env` rồi chạy `php artisan db:seed --class="Database\Seeders\LexusSiteSeeder" --force`
(lệnh này cũng đặt lại nội dung trang tĩnh/bài viết về bản gốc).

`/etc/supervisor/conf.d/lexus-queue.conf`:

```ini
[program:lexus-queue]
command=php /var/www/lexus/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
redirect_stderr=true
stdout_logfile=/var/www/lexus/storage/logs/queue.log
```

`supervisorctl reread && supervisorctl update`

## 5. Kiểm tra sau khi lên

- [ ] Trang chủ hiện 16 thẻ phiên bản; bấm "Nhận báo giá" → popup ghi "Phiên bản: …"
- [ ] Gửi thử một lead → Admin → Liên hệ có cột Phiên bản; hộp mail nhận thông báo
- [ ] `/robots.txt` có `Sitemap: https://<domain>/sitemap.xml`
- [ ] `/sitemap.xml` và `/llms.txt` dùng đúng domain
- [ ] Trang xe: trình xem màu xoay 360°, tab "Chi tiết" có ảnh
- [ ] Google Search Console: thêm domain, gửi sitemap
- [ ] Nén + cache đã bật: `curl -sI -H "Accept-Encoding: gzip" https://<domain>/assets/style.css`
      phải có `content-encoding: gzip` và `cache-control: …immutable`
- [ ] https://pagespeed.web.dev cho `/`, `/san-pham/rx`, `/bang-gia`, `/tin-tuc`:
      mục tiêu Performance ≥ 90 mobile, Accessibility/Best Practices/SEO 100
- [ ] Admin → Cài đặt: ảnh chia sẻ (đã seed ảnh mặt tiền), điền GTM/Pixel nếu dùng —
      khi bật đo lường, chính sách quyền riêng tư đã có đoạn nói về cookie của Google/Meta

## 6. Cập nhật về sau

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize && php artisan filament:optimize
supervisorctl restart lexus-queue
```

**Không chạy lại `db:seed` trên site đang chạy** trừ khi muốn đưa nội dung về
bản gốc: seeder ghi đè trang tĩnh, bài viết và dữ liệu xe bằng nội dung trong
code (sửa trong admin sẽ mất). Ảnh upload thêm trong admin không bị ảnh hưởng.
Upload ảnh mới trong admin xong thì chạy `php artisan catalog:images`.
