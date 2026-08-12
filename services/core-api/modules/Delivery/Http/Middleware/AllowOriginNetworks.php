<?php

declare(strict_types=1);

namespace Modules\Delivery\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the internal surface to the networks the origin runs on.
 *
 * The delivery-authorization endpoint answers "is this token valid for this
 * path" without a database read, which is what makes it viable at segment
 * request rates — and also what makes it a free oracle for anyone who can reach
 * it. So reachability is the control, and it is configured rather than assumed.
 *
 * **The allow-list is empty by default and an empty list denies everything.**
 * Absence of a permission is a prohibition (`CLAUDE.md` §1.5). A deployment that
 * has not said where its origin is has not earned an exception.
 */
final class AllowOriginNetworks
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $allowed */
        $allowed = (array) config('kms.delivery.origin_networks', []);
        $remote = (string) $request->ip();

        foreach ($allowed as $cidr) {
            if ($this->matches($remote, (string) $cidr)) {
                return $next($request);
            }
        }

        return ApiProblem::of(ErrorCode::Forbidden)->toResponse(
            (string) ($request->attributes->get('kms.correlation_id') ?? ''),
            '/'.ltrim($request->path(), '/'),
        );
    }

    /**
     * CIDR membership, for IPv4 and IPv6 alike.
     *
     * Compared as packed bytes rather than as integers: an IPv6 address does
     * not fit in a PHP integer, and a check that silently works for one family
     * and not the other is worse than one that works for neither.
     */
    private function matches(string $address, string $cidr): bool
    {
        [$subnet, $bits] = str_contains($cidr, '/')
            ? explode('/', $cidr, 2)
            : [$cidr, null];

        $packedAddress = @inet_pton($address);
        $packedSubnet = @inet_pton($subnet);

        // Mixing families is a configuration error, not a match.
        if ($packedAddress === false || $packedSubnet === false || strlen($packedAddress) !== strlen($packedSubnet)) {
            return false;
        }

        $prefix = $bits === null ? strlen($packedAddress) * 8 : (int) $bits;

        if ($prefix < 0 || $prefix > strlen($packedAddress) * 8) {
            return false;
        }

        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($wholeBytes > 0 && strncmp($packedAddress, $packedSubnet, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remainingBits) & 0xFF;

        return (ord($packedAddress[$wholeBytes]) & $mask) === (ord($packedSubnet[$wholeBytes]) & $mask);
    }
}
