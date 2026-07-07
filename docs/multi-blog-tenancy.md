# Multi-Blog Tenancy

This app can serve multiple business blogs from one Laravel deployment. Each request is resolved by hostname to a `blog_sites` record, and Canvas posts, tags, topics, and users are scoped by that site's `site_id`.

## Deploy

Run the normal deployment steps, then run migrations:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize
```

The tenancy migration creates one default site from `APP_URL` and assigns existing Canvas content to that site.

## Create a Business Blog

Create a site and its Canvas admin user:

```bash
php artisan blog-site:create business-a.com "Business A" admin@business-a.com
```

Useful options:

```bash
php artisan blog-site:create business-a.com "Business A" admin@business-a.com --default
php artisan blog-site:create business-a.com "Business A" admin@business-a.com --admin-name="Jane Admin"
php artisan blog-site:create business-a.com "Business A" admin@business-a.com --username=jane-admin
php artisan blog-site:create business-a.com "Business A" admin@business-a.com --password="change-me"
```

If `--password` is omitted, the command prints a generated password once.

## Domain Setup

Point each business domain at the same Laravel app host:

1. Add DNS for the business domain or subdomain.
2. Configure the web server / load balancer to send that host to this Laravel app.
3. Issue SSL for the host.
4. Run `blog-site:create` with the same hostname.

Unknown domains return `404` instead of falling back to another business blog.

## Verify a New Site

After DNS and SSL are live:

```bash
curl -I https://business-a.com/
curl https://business-a.com/sitemap.xml
```

Then verify:

- `https://business-a.com/` loads the public blog UI.
- `https://business-a.com/canvas` allows the site's Canvas admin to log in.
- `https://business-a.com/sitemap.xml` contains only that site's posts.
- Article API responses include a canonical link on the current domain.
- A post slug from another business returns `404` on this domain.

## Local Validation

For local development:

```bash
composer install
npm install --include=dev
npm run build
php artisan migrate:fresh --force
php artisan test
```

The tenant tests cover:

- domain-scoped post lists
- cross-site slug isolation
- per-site sitemap output
- `blog-site:create` success and duplicate-email protection

## Current Limits

- `canvas_users.email` is still globally unique because that comes from Canvas' package migration. The same email cannot currently create separate Canvas users on multiple sites.
- The command-line workflow is implemented. A dedicated internal admin UI for managing `blog_sites` is still a future layer.
- npm audit reports vulnerabilities in the existing frontend dependency stack. Handle dependency upgrades separately from tenancy changes.
