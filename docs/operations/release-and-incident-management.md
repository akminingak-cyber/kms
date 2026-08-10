# Release and Incident Management

## 1. Releasing

### Backend
Continuous delivery through the pipeline in [`ci-cd.md`](ci-cd.md): merge → build once → staging →
human gate → canary → full rollout.

- **The human gate is a judgement about timing, not about quality** — quality is CI's job. Mid-event,
  mid-billing-run and Friday evening are real reasons to wait.
- **Deploys are boring by design.** Small, frequent, reversible. A release that requires a plan is a
  release that will go wrong; the response is to make releases smaller, not to write longer plans.
- Every deploy is recorded and correlated with monitoring, so "what changed?" is answerable in seconds
  rather than by asking around.

### Clients
Release trains per platform ([`ci-cd.md`](ci-cd.md) §5). TV applications require certification and
propagate over months, so:

- The backend **never** requires a client update to keep working.
- A client release candidate is verified on the device matrix before submission.
- Rollback of a shipped TV app is effectively impossible — which makes the pre-submission gate the
  real quality gate for those platforms, and it must be treated with corresponding seriousness.

## 2. Rolling back

| Situation | Action |
|---|---|
| Bad code | Roll back to the previous artifact. Always possible: expand/contract keeps the schema compatible |
| Bad configuration | Revert the configuration; no redeploy |
| Bad feature | Turn off the flag — seconds, no deploy |
| Bad migration | **Forward-fix.** Never roll a migration back against data that has moved on ([`../database/migrations-and-change-management.md`](../database/migrations-and-change-management.md)) |
| Bad client release | Server-side mitigation via `/config` and the device matrix; then a client release |

**Feature flags are the primary rollback mechanism** for anything user-visible. They are faster than
a deploy, more precise, and they allow the fix to be verified before it is re-enabled.

## 3. Incident severity

| Severity | Definition | Response |
|---|---|---|
| **SEV1** | Viewers cannot watch. Playback broadly failing, live channels down, decision plane down | Page immediately; all hands; comms within 30 minutes |
| **SEV2** | Significant degradation or a subset severely affected. One platform failing, one region, payments down | Page; focused response |
| **SEV3** | Limited impact with a workaround. Admin degraded, delayed EPG, non-critical feature broken | Business hours |
| **SEV4** | Minor, no viewer impact | Ticket |
| **SEC** | Any suspected security incident, **regardless of viewer impact** | Immediate escalation to security; follow the security path, not the operational one |

Two rules that decide how incidents actually go:

- **A suspected content key compromise is SEV1 and SEC simultaneously**, whatever the viewer impact
  looks like. The consequence is contractual and reputational rather than operational, and that is
  worse ([`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md)).
- **A rights misconfiguration serving content where it is not licensed is SEV1 even if everything is
  working perfectly.** Nothing is broken; the platform is breaching a contract with every stream.
  This is exactly the case that a purely availability-focused severity scale misses.

## 4. During an incident

1. **Declare early.** Declaring and standing down costs nothing. Not declaring costs time when time
   is the scarce resource.
2. **One incident commander.** They coordinate and decide; they do not debug. Debugging while
   coordinating produces neither.
3. **Mitigate before diagnosing.** Roll back, flip the flag, fail over. Root cause can wait; viewers
   cannot.
4. **Communicate on a fixed cadence**, even when there is nothing new. Silence is read as chaos.
5. **Keep a timeline as you go.** Reconstructing one afterwards produces a fiction that is confidently
   wrong.
6. **Do not change two things at once.** Under pressure this is the most commonly broken rule, and it
   is what turns a one-hour incident into a four-hour one.

### Standard mitigations

| Symptom | First moves |
|---|---|
| Playback failing broadly | Check the decision plane; check origin and CDN health; consider failing over CDN |
| Authorization latency high | Check projection freshness, cache hit rate, replica lag; scale the authorizer profile |
| A channel is down | Check contribution paths; fail over; slate if both are lost |
| Licence failures | Check the licence proxy and the vendor; check key access; **do not disable DRM** |
| Payments failing | Check the PSP; queue for retry; **never grant entitlement on unconfirmed payment** |
| Database saturated | Identify the query; check for a runaway job; shed admin load before viewer load |

The two "never" entries are there because both are tempting in the moment and both convert a
recoverable incident into a contractual or financial one.

## 5. After an incident

- **Blameless review within a week**, while memory is fresh. The output is systemic change, not a
  person who will be more careful.
- Every review answers: what happened, why it was possible, why it was not caught earlier, why it
  took as long as it did to detect and to resolve, and what will change.
- **Actions have owners and dates**, and are tracked to completion. A review whose actions are never
  done is theatre, and the same incident recurs.
- **Near misses get reviews too.** They are free lessons.
- If the response was hampered by a missing runbook, a noisy alert, or an untested procedure, fixing
  that is itself an action item — those are the failures that compound across every future incident.

## 6. Communication

- **Status page** for viewer-facing incidents: plain language, no internal jargon, updated on the
  cadence promised.
- **Support gets specifics** — which platforms, which regions, what to tell viewers, expected
  resolution — before the queue fills, not after.
- **Commercial and rights stakeholders are notified** for anything touching rights compliance,
  licensor obligations, or content availability. Some agreements carry explicit notification
  requirements, and those obligations should be known before an incident, not looked up during one.
- **Regulatory notification timelines** for personal-data incidents are measured in tens of hours from
  awareness ([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md) §8).
  The clock starts at awareness, not at confirmation.

## 7. Readiness

Before launch, and periodically after:

- [ ] A runbook per alert, each walked through at least once
- [ ] Failover procedures rehearsed: origin, CDN, database, contribution paths
- [ ] Backup restore drill completed against the agreed RPO/RTO (**OQ-24**)
- [ ] Escalation matrix current, with names and numbers, and **tested**
- [ ] Communication templates prepared
- [ ] A game-day exercise: an induced failure, handled end to end

The purpose of rehearsal is not confidence. It is discovering which step in the written procedure is
wrong — and there is always one.
