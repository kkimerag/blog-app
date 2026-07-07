<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('path')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $defaultSiteId = (string) Str::uuid();
        $defaultDomain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        DB::table('blog_sites')->insert([
            'id' => $defaultSiteId,
            'name' => config('app.name', 'Default Blog'),
            'domain' => Str::lower($defaultDomain),
            'path' => null,
            'is_default' => true,
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['canvas_posts', 'canvas_tags', 'canvas_topics', 'canvas_users'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('site_id')->nullable()->after('id')->index();
            });

            DB::table($tableName)->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
        }
    }

    public function down(): void
    {
        foreach (['canvas_posts', 'canvas_tags', 'canvas_topics', 'canvas_users'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'site_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("{$tableName}_site_id_index");
                $table->dropColumn('site_id');
            });
        }

        Schema::dropIfExists('blog_sites');
    }
};
