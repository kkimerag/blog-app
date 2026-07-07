<?php

namespace App\Http\Middleware;

use App\Models\BlogSite;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveBlogSite
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('blog_sites')) {
            return $next($request);
        }

        $host = Str::lower($request->getHost());
        $site = BlogSite::query()->where('domain', $host)->first();

        if (! $site && Str::startsWith($host, 'www.')) {
            $site = BlogSite::query()->where('domain', Str::after($host, 'www.'))->first();
        }

        if (! $site && in_array($host, ['localhost', '127.0.0.1'], true)) {
            $site = BlogSite::query()->where('is_default', true)->first();
        }

        abort_unless($site, 404);

        $request->attributes->set('blog_site', $site);
        app()->instance(BlogSite::class, $site);

        return $next($request);
    }
}
