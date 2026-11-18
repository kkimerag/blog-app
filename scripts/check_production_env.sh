#!/bin/bash
set -euo pipefail

env_file=/etc/blog-app/production.env

if [[ ! -f "$env_file" || ! -s "$env_file" ]]; then
    echo "Missing production environment file: $env_file" >&2
    exit 1
fi

# Check presence without printing any secret. Do not generate a new key here.
if ! grep -Eq '^APP_KEY=.+$' "$env_file"; then
    echo "Production environment must contain a non-empty APP_KEY." >&2
    exit 1
fi
