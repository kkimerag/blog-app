<?php

namespace App\Console\Commands;

use App\Models\BlogSite;
use Canvas\Models\User as CanvasUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateBlogSite extends Command
{
    protected $signature = 'blog-site:create
        {domain : Domain for the business blog, such as example.com}
        {name : Business/blog name}
        {email : Canvas admin email}
        {--admin-name= : Canvas admin display name}
        {--username= : Canvas admin username}
        {--password= : Canvas admin password. Generated when omitted}
        {--default : Mark this site as the default localhost fallback}';

    protected $description = 'Create a tenant blog site and Canvas admin user.';

    public function handle(): int
    {
        $domain = $this->normalizeDomain($this->argument('domain'));
        $name = trim($this->argument('name'));
        $email = Str::lower(trim($this->argument('email')));
        $adminName = trim($this->option('admin-name') ?: "{$name} Admin");
        $username = trim($this->option('username') ?: Str::slug($name.' admin'));
        $password = $this->option('password') ?: Str::password(20);

        if ($domain === '') {
            $this->error('The domain is invalid.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('The admin email is invalid.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($domain, $name, $email, $adminName, $username, $password) {
            if ($this->option('default')) {
                BlogSite::query()->update(['is_default' => false]);
            }

            $site = BlogSite::query()->firstOrCreate(
                ['domain' => $domain],
                [
                    'name' => $name,
                    'settings' => [],
                    'is_default' => (bool) $this->option('default'),
                ],
            );

            if (! $site->wasRecentlyCreated) {
                $site->fill([
                    'name' => $name,
                    'is_default' => $this->option('default') ? true : $site->is_default,
                ])->save();
            }

            $existingUser = CanvasUser::query()->where('email', $email)->first();

            if ($existingUser && $existingUser->site_id !== $site->id) {
                $this->error("A Canvas user with {$email} already exists on a different site.");

                return self::FAILURE;
            }

            $user = $existingUser ?: CanvasUser::query()->create([
                'id' => (string) Str::uuid(),
                'site_id' => $site->id,
                'name' => $adminName,
                'email' => $email,
                'username' => $username,
                'password' => Hash::make($password),
                'role' => CanvasUser::ADMIN,
            ]);

            $this->info($site->wasRecentlyCreated ? 'Blog site created.' : 'Blog site updated.');
            $this->line("Site: {$site->name}");
            $this->line("Domain: {$site->domain}");
            $this->line("Site ID: {$site->id}");
            $this->line("Admin: {$user->email}");

            if (! $existingUser && ! $this->option('password')) {
                $this->line("Generated password: {$password}");
            }

            return self::SUCCESS;
        });
    }

    private function normalizeDomain(string $value): string
    {
        $value = Str::lower(trim($value));
        $host = parse_url(Str::startsWith($value, ['http://', 'https://']) ? $value : "https://{$value}", PHP_URL_HOST);

        return trim($host ?: $value);
    }
}
