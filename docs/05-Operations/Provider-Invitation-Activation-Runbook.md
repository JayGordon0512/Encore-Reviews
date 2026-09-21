# Provider invitation activation runbook

This runbook is intentionally gated. It must not be used to activate production
or replay historical bookings without a separately approved change.

## Authority and states

- A consented, successfully completed paid booking or genuine free registration
  creates `ELIGIBLE_PENDING_ATTENDANCE`.
- Only provider attendance/admission evidence creates `VERIFIED_ELIGIBLE`.
- TicketPal remains authoritative for booking, payment, performance, admission,
  refunds, cancellations, exchanges, transfers and consent.
- Encore remains authoritative for reviewer identity, eligibility, schedules,
  invitations, delivery, submission, moderation and reputation.

The eligibility request contains booking, show and performance references;
reviewer name/email; admission quantity; consent purpose/version/time; and
event, idempotency and correlation metadata. It does not contain show title or
time, card data, addresses, marketing data or unrelated customer data.
Catalogue import supplies event metadata.

## Staging activation sequence

1. Provision a staging-only credential scoped to
   `review-eligibility:write`, `review-withdrawal:write` and
   `review-eligibility-lifecycle:write`.
2. Enable provider ingress in Encore staging. Keep provider invitation issuing
   disabled.
3. Send one synthetic eligibility and reconcile a 1:1 chain: TicketPal outbox,
   accepted ingress, contact/eligibility and one `attendance_pending` schedule.
4. Verify duplicate idempotency, withdrawal, full/partial refund, cancellation,
   exchange, performance reschedule/cancellation and attendance confirmation.
5. Confirm attendance produces a verified schedule suppressed with
   `invitation_issuing_disabled`.
6. Preview `encore:invitations:release-held-provider`; use `--commit` only for
   approved test recipients after enabling staging issuing.
7. Run the due scheduler and verify queue, Mailgun acceptance, signed feedback,
   retry and dead-letter state.
8. Reconcile aggregate counts 1:1 and retain the evidence for activation review.

The legacy `/api/ticketpal/invitations` route is hard-disabled by default. It
can only be restored temporarily with `ENCORE_TICKETPAL_LEGACY_INVITATION_ENABLED`
for tested compatibility; Release 1 must use provider eligibility.

Rollback order is TicketPal delivery off first, then Encore invitation issuing
off. Existing schedules and delivery evidence are preserved.
