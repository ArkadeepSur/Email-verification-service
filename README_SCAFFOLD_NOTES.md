Notes after scaffolding

1) Register the provider
------------------------
Open `config/app.php` and add the provider to the `providers` array:

    App\Providers\VerificationServiceProvider::class,

2) Required composer packages
-----------------------------
After installing Composer, run:

    composer install
    composer require laravel/sanctum guzzlehttp/guzzle

3) Database
-----------
Copy `.env.example` to `.env` and update DB settings, then:

    php artisan key:generate
    php artisan migrate

4) Queues
---------
For production/async behavior set `QUEUE_CONNECTION=redis` and install Horizon if needed.

5) Sanctum & SPA
----------------
- Config file `config/sanctum.php` added; set `SANCTUM_STATEFUL_DOMAINS` and `CORS_ALLOWED_ORIGINS` in `.env`.
- For SPA session auth: request `GET /sanctum/csrf-cookie` then POST credentials to `POST /api/auth/login` to establish a session cookie (with credentials included).
- Dev helper: `GET /dev/login` logs in the seeded `admin@example.com` user when `APP_ENV=local`.

6) Next tasks
-------------
- Add controllers for admin panel, billing and user management
- Add tests and CI
- Add sample seeder data for blacklist/webhooks
