# Production deployment notes

## Web root and Apache

When hosting permits it, configure the domain's document root to Laravel's
`public` directory. The `public/.htaccess` file routes non-file requests through
Laravel's front controller. Do not change the hosting-level document root or
add a project-root rewrite unless the hosting provider supports that setup.

If the document root cannot be changed, follow the hosting provider's supported
public-directory setup rather than exposing the project root.

## Updating an existing deployment

Deploy the complete application, including
`app/Http/Controllers/AuthController.php` with that exact capitalization. From
the Laravel project root, run:

```sh
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan route:clear
php artisan config:cache
php artisan route:list --path=login
```

The login route should list `AuthController@showLogin` for `GET` and
`AuthController@login` for `POST`; logout should list `AuthController@logout`.
Do not cache routes while the root route remains a closure.

Set these values in the hosting environment or production `.env` file:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

Keep the existing production database and session settings intact. Never
publish `.env` or exception details. The deployment should return a generic
error page for unexpected exceptions while recording diagnostics in protected
server logs.
