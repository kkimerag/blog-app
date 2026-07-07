<?php

namespace Tests\Feature;

use App\Models\BlogSite;
use Canvas\Models\Post;
use Canvas\Models\User as CanvasUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogSiteTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_posts_are_scoped_to_the_request_domain(): void
    {
        [$siteA, $userA] = $this->createSiteWithUser('Business A', 'business-a.test');
        [$siteB, $userB] = $this->createSiteWithUser('Business B', 'business-b.test');

        $this->createPost($siteA, $userA, 'a-post', 'Business A Post');
        $this->createPost($siteB, $userB, 'b-post', 'Business B Post');

        $response = $this->getJson('http://business-a.test/api/posts');

        $response->assertOk()
            ->assertJsonFragment(['slug' => 'a-post'])
            ->assertJsonMissing(['slug' => 'b-post']);
    }

    public function test_post_slug_lookup_does_not_cross_sites(): void
    {
        [$siteA, $userA] = $this->createSiteWithUser('Business A', 'business-a.test');
        [$siteB, $userB] = $this->createSiteWithUser('Business B', 'business-b.test');

        $this->createPost($siteA, $userA, 'a-post', 'Business A Post');
        $this->createPost($siteB, $userB, 'b-post', 'Business B Post');

        $this->getJson('http://business-a.test/api/posts/b-post')->assertNotFound();
        $this->getJson('http://business-b.test/api/posts/b-post')->assertOk();
    }

    public function test_sitemap_only_contains_current_site_posts(): void
    {
        [$siteA, $userA] = $this->createSiteWithUser('Business A', 'business-a.test');
        [$siteB, $userB] = $this->createSiteWithUser('Business B', 'business-b.test');

        $this->createPost($siteA, $userA, 'a-post', 'Business A Post');
        $this->createPost($siteB, $userB, 'b-post', 'Business B Post');

        $response = $this->get('http://business-a.test/sitemap.xml');

        $response->assertOk();
        $response->assertSee('http://business-a.test/posts/a-post', false);
        $response->assertDontSee('b-post', false);
    }

    public function test_blog_site_create_command_creates_site_and_admin(): void
    {
        $this->artisan('blog-site:create', [
            'domain' => 'https://business-c.test/blog',
            'name' => 'Business C',
            'email' => 'admin@business-c.test',
            '--password' => 'secret-password',
            '--default' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('blog_sites', [
            'domain' => 'business-c.test',
            'name' => 'Business C',
            'is_default' => true,
        ]);

        $site = BlogSite::query()->firstWhere('domain', 'business-c.test');

        $this->assertDatabaseHas('canvas_users', [
            'site_id' => $site->id,
            'email' => 'admin@business-c.test',
            'role' => CanvasUser::ADMIN,
        ]);
    }

    public function test_blog_site_create_command_rejects_email_used_by_another_site(): void
    {
        [$site] = $this->createSiteWithUser('Business A', 'business-a.test');

        CanvasUser::query()->create([
            'id' => (string) Str::uuid(),
            'site_id' => $site->id,
            'name' => 'Existing Admin',
            'email' => 'admin@example.com',
            'username' => 'existing-admin',
            'password' => bcrypt('password'),
            'role' => CanvasUser::ADMIN,
        ]);

        $this->artisan('blog-site:create', [
            'domain' => 'business-b.test',
            'name' => 'Business B',
            'email' => 'admin@example.com',
            '--password' => 'secret-password',
        ])->assertExitCode(1);
    }

    private function createSiteWithUser(string $name, string $domain): array
    {
        $site = BlogSite::query()->create([
            'name' => $name,
            'domain' => $domain,
            'settings' => [],
        ]);

        $user = CanvasUser::query()->create([
            'id' => (string) Str::uuid(),
            'site_id' => $site->id,
            'name' => "{$name} Author",
            'email' => Str::slug($name).'@example.com',
            'username' => Str::slug($name),
            'password' => bcrypt('password'),
            'role' => CanvasUser::ADMIN,
        ]);

        return [$site, $user];
    }

    private function createPost(BlogSite $site, CanvasUser $user, string $slug, string $title): Post
    {
        return Post::query()->create([
            'id' => (string) Str::uuid(),
            'site_id' => $site->id,
            'slug' => $slug,
            'title' => $title,
            'summary' => "{$title} summary",
            'body' => "{$title} body",
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'meta' => [
                'title' => $title,
                'description' => "{$title} description",
            ],
        ]);
    }
}
