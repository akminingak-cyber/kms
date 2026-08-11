<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Client configuration, fetched at application start-up.
 *
 * This endpoint is how a television released three years ago is told to stop
 * attempting something, or to prompt for an upgrade. It is the main lever the
 * platform retains over clients it cannot otherwise change, which is why it is
 * built in Phase 1 rather than added when it is first needed.
 *
 * Publicly cacheable, because nothing here varies per viewer. Anything that did
 * would have to move to a `private` response
 * (docs/api/conventions.md §6).
 */
final class ClientConfigController
{
    public function __invoke(Request $request): JsonResponse
    {
        $platform = $request->attributes->get('kms.client_platform');
        $minimums = (array) config('kms.clients.minimum_versions');

        $minimum = is_string($platform) && isset($minimums[$platform])
            ? (string) $minimums[$platform]
            : null;

        $current = $request->attributes->get('kms.client_version');

        $upgradeRequired = $minimum !== null
            && is_string($current)
            && version_compare($current, $minimum, '<');

        return (new JsonResponse([
            'api' => [
                'client_version' => 'v1',
                // Present from the first release so clients handle a list, and
                // multi-CDN needs no client update later (ADR-0008).
                'features' => [],
            ],
            'minimum_supported_version' => $minimum,
            'upgrade_required' => $upgradeRequired,
            // A message key, never a message: the client localises. A
            // server-supplied English string on a Portuguese television is a bug.
            'upgrade_message_key' => $upgradeRequired ? 'upgrade.required' : null,
            'activation' => [
                'poll_interval_seconds' => (int) config('kms.activation.poll_interval_seconds'),
            ],
            'limits' => [
                'profiles' => (int) config('kms.profiles.max_per_account'),
                'devices' => (int) config('kms.devices.default_limit'),
            ],
        ]))->setPublic()->setMaxAge(300);
    }
}
