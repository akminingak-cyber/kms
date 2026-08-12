<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use Modules\Media\Contracts\DeliveryPolicy;
use Modules\Media\Contracts\OriginAddressing;

/**
 * The DASH MPD for a live publication (ISO/IEC 23009-1, `isoff-live` profile).
 *
 * Unlike HLS, a live MPD is **complete**: `SegmentTemplate` with a fixed
 * duration describes every segment that will ever exist by arithmetic, so
 * nothing has to be appended as the packager publishes. That is the whole
 * reason this can be generated from control-plane data and golden-tested,
 * where the HLS media playlists cannot.
 *
 * VOD (`type="static"`) is deliberately absent. It needs a real
 * `mediaPresentationDuration` and a `SegmentTimeline` derived from what was
 * actually encoded, neither of which exists before the VOD pipeline (Phase 5).
 * An MPD written now with a guessed duration would be exactly the
 * plausible-looking placeholder `CLAUDE.md` §1.1 forbids.
 *
 * Built with DOM rather than string concatenation: an MPD is XML, a manifest is
 * a client contract, and a stray unescaped character in a language tag or a
 * label would produce a document that some players parse and others reject.
 */
final readonly class DashManifestWriter
{
    private const NS = 'urn:mpeg:dash:schema:mpd:2011';

    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public function write(
        Ladder $ladder,
        PackagingProfile $profile,
        DeliveryPolicy $policy,
        OriginAddressing $origin,
        DateTimeImmutable $availableFrom,
        DateTimeImmutable $publishedAt,
    ): string {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $mpd = $document->createElementNS(self::NS, 'MPD');
        $document->appendChild($mpd);

        $mpd->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', self::XSI);
        $mpd->setAttribute('profiles', 'urn:mpeg:dash:profile:isoff-live:2011');
        $mpd->setAttribute('type', 'dynamic');
        $mpd->setAttribute('availabilityStartTime', $this->instant($availableFrom));
        $mpd->setAttribute('publishTime', $this->instant($publishedAt));
        /*
         * How often a player must re-fetch the MPD. With a fixed-duration
         * template nothing in the document changes between segments, so this
         * only needs to be short enough that a player notices a *structural*
         * change — a new period, or a rung disappearing.
         */
        $mpd->setAttribute('minimumUpdatePeriod', $this->duration($profile->segmentDurationMs * $profile->playlistWindowSegments));
        $mpd->setAttribute('timeShiftBufferDepth', $this->duration($profile->timeShiftBufferSeconds * 1000));
        // How far behind the live edge a player should sit. Too small and every
        // player requests segments that do not exist yet and gets 404s.
        $mpd->setAttribute('suggestedPresentationDelay', $this->duration($profile->suggestedPresentationDelayMs));
        $mpd->setAttribute('minBufferTime', $this->duration($profile->segmentDurationMs));

        $period = $this->child($document, $mpd, 'Period');
        $period->setAttribute('id', 'p0');
        $period->setAttribute('start', 'PT0S');

        $this->appendVideo($document, $period, $ladder, $profile, $policy, $origin);

        foreach ($this->audioByLanguage($ladder) as $language => $renditions) {
            $this->appendAudio($document, $period, (string) $language, $renditions, $profile, $origin);
        }

        return (string) $document->saveXML();
    }

    /** @param list<Rung> $rungs */
    private function appendVideo(
        DOMDocument $document,
        DOMElement $period,
        Ladder $ladder,
        PackagingProfile $profile,
        DeliveryPolicy $policy,
        OriginAddressing $origin,
    ): void {
        $set = $this->child($document, $period, 'AdaptationSet');
        $set->setAttribute('id', '1');
        $set->setAttribute('contentType', 'video');
        $set->setAttribute('mimeType', 'video/mp4');
        // Both of these are assertions about the *encode*, not requests to the
        // player, and both are enforced by the ladder and packaging invariants:
        // segments start on an IDR frame and are aligned across renditions.
        $set->setAttribute('segmentAlignment', 'true');
        $set->setAttribute('startWithSAP', '1');

        $this->appendSegmentTemplate($document, $set, $profile, $origin);

        foreach ($ladder->rungsUpTo($policy->maxHeight) as $rung) {
            $representation = $this->child($document, $set, 'Representation');
            $representation->setAttribute('id', $rung->label);
            $representation->setAttribute('codecs', $rung->codecString());
            $representation->setAttribute('width', (string) $rung->width);
            $representation->setAttribute('height', (string) $rung->height);
            $representation->setAttribute('frameRate', $rung->frameRate->toString());
            $representation->setAttribute('bandwidth', (string) $rung->peakBitsPerSecond());
        }
    }

    /** @param list<AudioRendition> $renditions */
    private function appendAudio(
        DOMDocument $document,
        DOMElement $period,
        string $language,
        array $renditions,
        PackagingProfile $profile,
        OriginAddressing $origin,
    ): void {
        $set = $this->child($document, $period, 'AdaptationSet');
        // Deterministic and stable across regenerations: derived from the
        // language rather than from iteration order.
        $set->setAttribute('id', 'a-'.$language);
        $set->setAttribute('contentType', 'audio');
        $set->setAttribute('mimeType', 'audio/mp4');
        $set->setAttribute('lang', $language);
        $set->setAttribute('segmentAlignment', 'true');
        $set->setAttribute('startWithSAP', '1');

        $role = $this->child($document, $set, 'Role');
        $role->setAttribute('schemeIdUri', 'urn:mpeg:dash:role:2011');
        $role->setAttribute('value', $this->dashRole($renditions[0]->role));

        $this->appendSegmentTemplate($document, $set, $profile, $origin);

        foreach ($renditions as $rendition) {
            $representation = $this->child($document, $set, 'Representation');
            $representation->setAttribute('id', $rendition->label);
            $representation->setAttribute('codecs', $rendition->codecString());
            $representation->setAttribute('audioSamplingRate', (string) $rendition->sampleRateHz);
            $representation->setAttribute('bandwidth', (string) ($rendition->bitrateKbps * 1000));

            $channels = $this->child($document, $representation, 'AudioChannelConfiguration');
            $channels->setAttribute('schemeIdUri', 'urn:mpeg:dash:23003:3:audio_channel_configuration:2011');
            $channels->setAttribute('value', (string) $rendition->channels);
        }
    }

    private function appendSegmentTemplate(
        DOMDocument $document,
        DOMElement $parent,
        PackagingProfile $profile,
        OriginAddressing $origin,
    ): void {
        $template = $this->child($document, $parent, 'SegmentTemplate');
        $template->setAttribute('timescale', (string) $profile->timescale);
        $template->setAttribute('duration', (string) $profile->segmentDurationInTimescale());
        $template->setAttribute('startNumber', '1');
        $template->setAttribute('initialization', $origin->initialisationTemplateRelative());
        $template->setAttribute('media', $origin->segmentTemplateRelative($profile->segmentSuffix()));
    }

    /**
     * Grouped by language, then by label, so the document is byte-identical
     * across regenerations of the same ladder.
     *
     * @return array<string,list<AudioRendition>>
     */
    private function audioByLanguage(Ladder $ladder): array
    {
        $grouped = [];

        foreach ($ladder->audio as $rendition) {
            $grouped[$rendition->language][] = $rendition;
        }

        ksort($grouped);

        foreach ($grouped as $language => $renditions) {
            usort($renditions, static fn (AudioRendition $a, AudioRendition $b): int => strcmp($a->label, $b->label));
            $grouped[$language] = $renditions;
        }

        return $grouped;
    }

    private function dashRole(string $role): string
    {
        return match ($role) {
            'description' => 'description',
            'commentary' => 'commentary',
            // A dub is the main audio for its language, not a supplementary
            // track; labelling it 'alternate' would hide it from players that
            // only offer main tracks in the language menu.
            default => 'main',
        };
    }

    private function child(DOMDocument $document, DOMElement $parent, string $name): DOMElement
    {
        $element = $document->createElementNS(self::NS, $name);
        $parent->appendChild($element);

        return $element;
    }

    private function instant(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /** ISO 8601 duration, which is what every duration attribute in an MPD takes. */
    private function duration(int $milliseconds): string
    {
        return $milliseconds % 1000 === 0
            ? 'PT'.intdiv($milliseconds, 1000).'S'
            : 'PT'.number_format($milliseconds / 1000, 3, '.', '').'S';
    }
}
