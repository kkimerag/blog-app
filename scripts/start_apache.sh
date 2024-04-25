#!/bin/bash

# Stop Apache
sudo systemctl start httpd

# Optimization
sudo php /var/www/html/blog.rv-tampa.com/artisan optimize

# Set Laravel Permissions
sudo chown -R apache:apache /var/www/html/blog.rv-tampa.com
sudo chmod -R 755 /var/www/html/blog.rv-tampa.com/storage
