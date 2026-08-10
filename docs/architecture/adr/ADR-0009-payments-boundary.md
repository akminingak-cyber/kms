# ADR-0009: PSP-agnostic payments, no card data on our infrastructure

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture, Security, Commercial

## Context

No payment service provider has been chosen (**OQ-9**), and the choice depends on launch markets
(**OQ-1**), which are also unknown. Payment method preferences are strongly regional: card-dominant
in some markets, wallet-, bank-transfer- or carrier-billing-dominant in others. A second PSP is a
normal consequence of entering a second market.

Separately, if KMS TV's systems ever touch a primary account number (PAN), the platform enters a PCI
DSS scope that would dominate its security, audit and hosting costs.

## Options considered

**A. Adopt one PSP's SDK and subscription engine directly**, including its subscription objects as
our model of subscription state. Fastest to a first charge. It makes the PSP the system of record for
revenue-bearing state, which means a migration later is a data migration of live subscriptions —
one of the most hazardous operations a subscription business can undertake.

**B. PSP handles payments; we own subscription state**, behind a port with the PSP as an adapter.

**C. Build payment processing.** Not considered.

## Decision

**Option B**, with these non-negotiables:

1. **We are the system of record for subscription state.** Products, plans, prices, promotions,
   subscription lifecycle, renewal schedule, entitlement derivation and dunning policy all live in
   KMS TV. The PSP executes money movement.
2. **All PSP interaction is behind the `PaymentGateway` port** (P1 in
   [`../05-integration-boundaries.md`](../05-integration-boundaries.md)). No PSP type appears outside
   its adapter.
3. **No PAN, CVV or full card data ever reaches our infrastructure, in any environment, including
   logs.** Card capture uses provider-hosted fields or the platform's own payment sheet; we store
   only provider tokens and display metadata (brand, last four, expiry). Target: **PCI DSS SAQ A**.
   This is verified by the P1 checklist before the adapter is built.
4. **Every payment operation is idempotent**, keyed by a client- or system-supplied idempotency key,
   so a retry after a timeout cannot double-charge.
5. **Webhooks are signature-verified, stored raw and immutable, acknowledged immediately, processed
   asynchronously and idempotently.** A webhook is an input to our state machine, never the state
   itself; the state machine also reconciles by polling, because webhooks are lost in exactly the
   incidents where they matter most.
6. **Money is never a floating-point number** anywhere in the system. A `Money` value object with
   explicit currency lives in `packages/php-shared-kernel`.
7. Tax, invoicing and revenue recognition requirements are market-dependent and unresolved
   (**OQ-22**); the model keeps tax as a separate, pluggable calculation rather than baking a rate
   into a price.
8. `laravel/cashier` is **not** adopted by default: it assumes a specific PSP and that the PSP owns
   subscription state, which contradicts (1). It may be reconsidered only if the PSP decision makes
   it a clean fit.

## Consequences

**Accepted costs**
- More to build than adopting a PSP's subscription engine: lifecycle state machine, renewal
  scheduling, dunning, proration, reconciliation.
- Reconciliation between our state and the PSP's must be built and monitored, with alerting on
  divergence.

**Made easier**
- Adding or replacing a PSP is an adapter, not a migration of live subscriptions.
- Entitlements derive from state we own, so the playback path never depends on a third party.
- PCI scope stays minimal, which keeps hosting and audit costs proportionate.

**Revisit when**
- Launch markets are confirmed and a PSP is selected — at which point a per-vendor ADR records the
  P1 checklist results.
- App-store billing obligations for iOS/Android sign-ups are clarified (**OQ-10**); mandatory
  in-app purchase would add a second, quite different payment adapter with its own entitlement path.
