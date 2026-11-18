# Production environment and credentials

CodeBuild creates an application artifact without a production `.env`, secret
JSON files, or cached Laravel configuration. CodeDeploy reads the environment
from `/etc/blog-app/production.env` on each application host. The file is
outside the deployment directory and must be provisioned before deployment.

## First deployment

1. Restrict access to the host and create `/etc/blog-app` as root, mode `0700`.
2. Create `/etc/blog-app/production.env` as root, mode `0600`, using the current
   production configuration. If the existing application has a working `.env`,
   it can be copied into that location on the host. Verify its settings privately;
   do not print, commit, or put the file in a build artifact.
3. Confirm that `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` is the actual
   default blog URL, and the database and other required service settings are
   correct. Provide the existing production `APP_KEY` initially, so the new
   release can read existing encrypted data and sessions.
4. Rotate the database password and any exposed API or AWS credentials with their
   providers; update this file and any other consumers before retiring old values.
   Prefer an instance role for AWS access over long-lived AWS keys when supported.
5. Plan the rotation of the exposed `APP_KEY` separately. Changing it can break
   sessions and encrypted data. Inventory encrypted data and use a tested key
   transition and recovery plan before replacing it. A copied key remains exposed.
6. Remove historical secrets from Git history and existing build artifacts after
   rotating them. Removing a value from the current branch does not revoke it.

Deploy only after the file exists on every target host. Before stopping Apache,
CodeDeploy checks that the file and a non-empty `APP_KEY` exist. After the code
is installed, it copies the file to the application directory as root:apache,
mode `0640`, and rebuilds Laravel's caches on the host. The cached configuration
can contain secrets and must be protected like the environment file.

Changes to `/etc/blog-app/production.env` do not automatically update the
application's copy or configuration cache. Apply them through a controlled
deployment, or securely update the application copy and rebuild its cache on
each host. Keep backups of the environment and encryption keys in an approved
secret store with restricted access.

This change does not run database migrations or address tenant isolation; those
remain separate release requirements. Never run `migrate:fresh` on production.
