<?php

namespace App\Http\Middleware;

use App\Support\Catalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bắt các đường dẫn cũ đã có luật chuyển hướng trong bảng `redirects` và
 * trả 301/302/410. Chạy sớm, trước khi Laravel kịp ném 404.
 *
 * App gắn vào nhóm `web`:
 *   ->withMiddleware(fn ($m) => $m->web(prepend: [HandleRedirects::class]))
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');

        $redirect = Catalog::model('redirect')::query()
            ->where('from_path', $path)
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        // Đếm lượt để biết luật nào còn sống — cột `hits` hiện trong admin.
        $redirect->increment('hits');

        if ((int) $redirect->status_code === 410) {
            return response('Gone', 410);
        }

        // Giữ nguyên query string của đường dẫn cũ.
        $target = $redirect->to_path;
        if ($query = $request->getQueryString()) {
            $target .= (str_contains($target, '?') ? '&' : '?').$query;
        }

        return redirect($target, (int) $redirect->status_code);
    }
}
