<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Native media storage
    |--------------------------------------------------------------------------
    |
    | Media is deliberately kept outside Laravel's Storage / Flysystem stack.
    | This project can therefore upload and serve media on a PHP runtime that
    | does not have ext-fileinfo. Paths saved in the database stay relative.
    |
    | On a release-based VPS, point MEDIA_ROOT at a persistent directory such
    | as /var/www/cars/shared/media and expose it at MEDIA_URL with Nginx.
    |
    */

    'root' => env('MEDIA_ROOT') ?: storage_path('app/public'),

    'url' => env('MEDIA_URL', env('PUBLIC_STORAGE_URL', '/storage')),

    'max_image_size_kb' => (int) env('MEDIA_MAX_IMAGE_SIZE_KB', 8192),

    'max_pdf_size_kb' => (int) env('MEDIA_MAX_PDF_SIZE_KB', 20480),

    'max_width' => (int) env('MEDIA_MAX_WIDTH', 8000),

    'max_height' => (int) env('MEDIA_MAX_HEIGHT', 8000),

    'max_pixels' => (int) env('MEDIA_MAX_PIXELS', 40_000_000),

    /*
     * Ảnh được thu nhỏ và mã hoá WebP ngay trong trình duyệt trước khi POST.
     * Đây là mục tiêu tối ưu, không phải giới hạn upload: nếu trình duyệt
     * không mã hoá được hoặc file mới không nhẹ hơn thì uploader giữ file gốc.
     */
    'client_image_max_dimension' => (int) env('MEDIA_CLIENT_IMAGE_MAX_DIMENSION', 1920),

    'client_image_quality' => (int) env('MEDIA_CLIENT_IMAGE_QUALITY', 82),

    /*
     * The browser submits a directory name, so it must be allow-listed on the
     * server. The value is the only kind of file accepted in that directory.
     */
    'upload_directories' => [
        'catalog/hero' => 'image',
        'catalog/variants' => 'image',
        'catalog/options' => 'image',
        'catalog/options/360' => 'image',
        'catalog/specs' => 'image',
        'catalog/sections' => 'image',
        'catalog/seo' => 'image',
        'catalog/posts' => 'image',
        'catalog/banners' => 'image',
        'catalog/settings' => 'image',
        'catalog/tai-lieu' => 'pdf',
    ],
];
