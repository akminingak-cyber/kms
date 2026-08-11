<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Domain\Clock;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
    }

    public function boot(): void
    {
        $this->registerRateLimiters();
    }

    /**
     * Rate limits are layered: per IP and per identity, with different limits.
     * An attacker rotating IPs is still bounded per account; an attacker
     * enumerating accounts from one IP is still bounded per IP.
     */
    private function registerRateLimiters(): void
    {
        $limits = (array) config('kms.rate_limits');

        $parse = static function (string $spec): array {
            [$max, $minutes] = array_pad(explode(',', $spec, 2), 2, '1');

            return [(int) $max, max(1, (int) $minutes)];
        };

        $byIp = static function (string $name) use ($limits, $parse) {
            [$max, $minutes] = $parse((string) ($limits[$name] ?? '60,1'));

            return static fn (Request $request): Limit => Limit::perMinutes($minutes, $max)
                ->by($name.'|'.$request->ip());
        };

        RateLimiter::for('auth-ip', $byIp('auth_per_ip'));
        RateLimiter::for('register-ip', $byIp('registration_per_ip'));
        RateLimiter::for('password-reset-ip', $byIp('password_reset_per_ip'));
        RateLimiter::for('activation-poll', $byIp('activation_poll'));
        RateLimiter::for('admin', $byIp('admin'));
        RateLimiter::for('playback', $byIp('playback'));

        // Identity-scoped: keyed on the submitted email so credential stuffing
        // against one account is bounded regardless of source address.
        [$authMax, $authMinutes] = $parse((string) ($limits['auth_per_identity'] ?? '5,1'));
        RateLimiter::for('auth-identity', static function (Request $request) use ($authMax, $authMinutes): Limit {
            $identity = strtolower(trim((string) $request->input('email', '')));

            return Limit::perMinutes($authMinutes, $authMax)->by('auth-identity|'.$identity);
        });

        // Authenticated traffic is bounded per account, falling back to IP for
        // anonymous requests so an unauthenticated caller cannot bypass it.
        [$readMax, $readMinutes] = $parse((string) ($limits['read_per_account'] ?? '120,1'));
        RateLimiter::for('client-read', static fn (Request $request): Limit => Limit::perMinutes($readMinutes, $readMax)
            ->by('read|'.($request->attributes->get('kms.account_uuid') ?? $request->ip())));

        [$writeMax, $writeMinutes] = $parse((string) ($limits['write_per_account'] ?? '30,1'));
        RateLimiter::for('client-write', static fn (Request $request): Limit => Limit::perMinutes($writeMinutes, $writeMax)
            ->by('write|'.($request->attributes->get('kms.account_uuid') ?? $request->ip())));
    }
}
