<?php

namespace App\Providers;

use App\Models\BlogSite;
use Canvas\Models\Post;
use Canvas\Models\Tag;
use Canvas\Models\Topic;
use Canvas\Models\User as CanvasUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if( ($this->app->environment('production')) || ($this->app->environment('test')) ) {
            $this->app['request']->server->set('HTTPS','on');
            URL::forceScheme('https');
        }

        foreach ([Post::class, Tag::class, Topic::class, CanvasUser::class] as $model) {
            $model::addGlobalScope('blog_site', function ($query) {
                $siteId = $this->resolvedBlogSiteId();
                $model = $query->getModel();

                if ($siteId && Schema::hasColumn($model->getTable(), 'site_id')) {
                    $query->where($model->getTable().'.site_id', $siteId);
                }
            });

            $model::creating(function ($record) {
                if ($record->site_id || ! Schema::hasColumn($record->getTable(), 'site_id')) {
                    return;
                }

                $record->site_id = $this->currentBlogSiteId();
            });
        }
    }

    private function resolvedBlogSiteId(): ?string
    {
        $request = request();
        $site = $request->attributes->get('blog_site');

        return $site instanceof BlogSite ? $site->id : null;
    }

    private function currentBlogSiteId(): ?string
    {
        if (! Schema::hasTable('blog_sites')) {
            return null;
        }

        if ($siteId = $this->resolvedBlogSiteId()) {
            return $siteId;
        }

        return BlogSite::query()->where('is_default', true)->value('id');
    }
}
