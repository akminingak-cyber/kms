<?php

declare(strict_types=1);

namespace Modules\Delivery\Application;

use Modules\Delivery\Contracts\CdnProvider;
use Modules\Delivery\Contracts\DeliveryPlanner;
use Modules\Delivery\Contracts\DeliveryRequest;
use Modules\Delivery\Contracts\DeliveryTarget;
use Modules\Delivery\Domain\DeliveryToken;
use Modules\Media\Contracts\DeliveryPolicy;
use Modules\Media\Contracts\DeviceProfiles;
use Modules\Media\Contracts\MediaPublications;
use Modules\Media\Contracts\OriginAddressing;
use Modules\Media\Contracts\PublicationView;
use Modules\Shared\Domain\Clock;

/**
 * Turns an authorized playback into an ordered list of places to fetch it from.
 *
 * The list is **ordered and plural from the first release**, even while it
 * always contains one entry, because clients that cannot handle a list will
 * still be in the field years later and would block multi-CDN adoption
 * entirely (ADR-0008). Ordering is by edge priority first, then by the device's
 * own format preference — a television that plays both HLS and DASH is often
 * markedly more reliable on one of them.
 *
 * Everything that varies per viewer is inside a single opaque token parameter,
 * so the path is byte-identical for every viewer of the same content at the
 * same quality class. That is what makes an edge cache useful at all, and the
 * property is asserted by a test rather than left to a comment.
 */
final readonly class EdgeDeliveryPlanner implements DeliveryPlanner
{
    /** @param list<CdnProvider> $edges ordered, highest priority first */
    public function __construct(
        private Clock $clock,
        private MediaPublications $publications,
        private DeviceProfiles $deviceProfiles,
        private DeliveryToken $token,
        private array $edges,
    ) {}

    /** @return list<DeliveryTarget> */
    public function plan(DeliveryRequest $request): array
    {
        $publication = $this->publications->activeFor($request->subjectType, $request->subjectRef);

        // Nothing published. An empty list, never a fabricated URL: the caller
        // turns this into a specific denial, which is answerable, where a URL
        // to an object that does not exist would surface as an unexplained
        // player error with no server-side signal at all.
        if ($publication === null) {
            return [];
        }

        $device = $this->deviceProfiles->for($request->deviceClass);

        $policy = DeliveryPolicy::forCaps($request->qualityCaps)->narrowTo($device?->maxHeight);

        if (! $publication->offersPolicy($policy->slug)) {
            return [];
        }

        $formats = $this->formatsFor($request, $publication, $device?->formats ?? []);

        if ($formats === [] || $this->edges === []) {
            return [];
        }

        $ttl = (int) config('kms.playback.delivery_token_ttl_seconds');
        $expiresAt = $this->clock->now()->modify("+{$ttl} seconds");
        $origin = OriginAddressing::fromPrefix($publication->originPrefix);

        // One token for the whole publication prefix, not one per object: a
        // player fetches thousands of segments and cannot obtain a token for
        // each of them.
        $token = $this->token->issue($publication->originPrefix, $request->sessionUuid, $expiresAt->getTimestamp());

        $targets = [];

        foreach ($this->edges as $edge) {
            foreach ($formats as $format) {
                $targets[] = new DeliveryTarget(
                    format: $format,
                    container: $publication->container,
                    url: $edge->urlFor($origin->manifestPath($policy, $format), $token),
                    priority: count($targets) + 1,
                    edge: $edge->name(),
                    qualityClass: $policy->slug,
                    expiresAt: $expiresAt,
                );
            }
        }

        return $targets;
    }

    /**
     * The formats actually offered: what the device prefers, kept in the
     * device's order, intersected with what was published and with what the
     * client asked for.
     *
     * Client capabilities may only ever **narrow**. An unrecognised or empty
     * request falls back to what the device profile says, never to something
     * wider — a client asserting a capability it does not have would otherwise
     * be served a stream it cannot play.
     *
     * @param  list<string>  $devicePreference
     * @return list<string>
     */
    private function formatsFor(DeliveryRequest $request, PublicationView $publication, array $devicePreference): array
    {
        $requested = array_values(array_filter(
            (array) ($request->capabilities['formats'] ?? []),
            static fn (mixed $format): bool => is_string($format),
        ));

        $offered = array_values(array_filter(
            $devicePreference,
            static fn (string $format): bool => $publication->offersFormat($format),
        ));

        if ($requested === []) {
            return $offered;
        }

        return array_values(array_filter(
            $offered,
            static fn (string $format): bool => in_array($format, $requested, true),
        ));
    }
}
