# ADR-0008: CDN and origin behind a delivery port

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture, Streaming

## Context

CDN egress is typically the largest single running cost of an OTT platform, and CDN availability is
the largest single availability risk: a regional CDN failure is an outage for every viewer in that
region, and it is not something we can fix from our side.

No CDN vendor has been selected (**OQ-8**). Vendor signing schemes, purge semantics, log formats and
geo features differ substantially, and CDN contracts are renegotiated as volume grows.

## Options considered

**A. Integrate one CDN directly.** Fastest to build; every playback response, every manifest URL and
every token becomes vendor-shaped. Changing or adding a CDN later touches the playback hot path —
the most dangerous code in the platform to modify under commercial time pressure.

**B. Full multi-CDN with steering from day one.** Correct end state for scale, but it requires two
vendor contracts, real traffic to steer on, and QoE data that will not exist until Phase 10.

**C. One `CdnProvider` port with one adapter now, designed for several adapters and later steering.**

## Decision

**Option C.**

1. All CDN interaction goes through the `CdnProvider` port owned by **D4 Delivery Control**. No other
   context knows a CDN vendor exists.
2. The port covers: signed URL / token generation, purge, origin reference resolution, and log
   retrieval.
3. Playback authorization returns an **abstract delivery target** which Delivery Control renders into
   a concrete signed URL. The authorizer never builds vendor URLs itself.
4. **Origin is ours** (or ours-by-contract) and is the single source of media truth. The CDN is a
   cache, never a system of record — deleting the CDN must lose nothing but performance.
5. Origin sits behind a **shield** tier so a CDN cache-miss storm cannot reach packagers directly.
6. Content addressing is **vendor-neutral and stable**: paths are derived from asset and rendition
   identifiers, not from any vendor's conventions, so a second CDN can be pointed at the same origin
   without re-deriving URLs.
7. Multi-CDN **selection** is deferred to Phase 10, but the response shape supports it from Phase 4:
   a playback response can carry an ordered list of delivery targets rather than one URL. Clients
   must handle the list from day one, even when it always has one entry — otherwise every TV app in
   the field blocks multi-CDN adoption later.
8. **Geo-blocking is never delegated solely to the CDN.** Territory is decided at authorization; edge
   geo controls are defence in depth, not the control.

## Consequences

**Accepted costs**
- One layer of indirection on the hot path — negligible in latency, real in code volume.
- Vendor-specific optimisations must be justified and pushed down into the adapter, not leaked up.
- Clients must implement delivery-target fallback before it is strictly needed.

**Made easier**
- A second CDN becomes an adapter plus configuration, with no change to playback or clients.
- Origin can move, or be duplicated per region, without client-visible change.
- CDN contract negotiation is stronger when switching is technically cheap.

**Revisit when**
- Multi-CDN steering is implemented (Phase 10) and the port needs to express health and cost signals.
