#!/bin/bash
set -euo pipefail

app_dir=/var/www/html/blog.rv-tampa.com
env_file=/etc/blog-app/production.env

"$app_dir/scripts/check_production_env.sh"
mkdir -p "$app_dir/bootstrap/cache" "$app_dir/storage/app/public" \
    "$app_dir/storage/framework/cache/data" "$app_dir/storage/framework/sessions" \
    "$app_dir/storage/framework/views" "$app_dir/storage/logs"
chown -R apache:apache "$app_dir"
chmod -R 755 "$app_dir/storage"

# Install only on the application host; the source remains outside the release.
install -o root -g apache -m 0640 "$env_file" "$app_dir/.env"
cd "$app_dir"
sudo -u apache php artisan config:clear
sudo -u apache php artisan optimize
systemctl start httpd
