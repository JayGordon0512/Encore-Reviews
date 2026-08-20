# Authority Principle Documentation Inconsistencies

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 1.0

**Date:** 24 July 2026

**Status:** Open governance observations

## Purpose

This document records residual inconsistencies and ambiguities identified during the Product Guardian review. It does not alter the Authority Principle or authorize implementation.

## 1. Verified Attendance Is Strategically Required but Not Yet Operationally Defined

The strategic foundation states that every review originates from verified attendance. Existing implementation documentation describes a valid invitation as review evidence, while the current provider invitation contract allows `attendance_state` to be absent.

**Affected principle:** Verification grants authority.

**Recommendation:** Define approved attendance-evidence classes, evidence owners, confidence, timing, correction, and the exact rule that permits a Verified Review Invitation to be issued.

## 2. “Objective Verification” Requires a Qualification Model

The Operating Principles use “objective verification.” Provider scans, completed-performance state, booking records, manual exception evidence, and venue confirmation do not necessarily provide identical confidence.

**Affected principle:** Trust through explicit verification.

**Recommendation:** Treat objective verification as evidence governed by transparent policy rather than as an assertion of infallibility. Communicate verification strength without weakening the brand statement.

## 3. The Current Platform Charter Describes a Review Platform While the Vision Defines an Intelligence Platform

The Platform Charter accurately governs the implemented review foundation, while the Manifesto, Platform Strategy, and Product Blueprint define the broader Audience Intelligence Platform.

**Affected principle:** Documentation First and source-of-truth clarity.

**Recommendation:** Keep the Charter explicitly scoped to current binding platform invariants and state that it governs the review foundation within the wider product vision. Do not expand current capability claims.

## 4. Invitation and Authority Are Currently Used Interchangeably

The vision treats the Verified Review Invitation as the representation of authority. Future product design may need to distinguish the underlying verified eligibility/authority from its delivery token or message.

**Affected principle:** Authority is a distinct domain concept.

**Recommendation:** Use “Verified Review Invitation” in audience language while evaluating separate internal concepts for attendance evidence, contribution authority, and invitation delivery.

## 5. Future Trusted Contribution Is Broader Than Attendance-Based Review

The brand statement says only people who were there can contribute. ADR-015 allows future trusted contribution types to define their verification mechanism, which may not always be attendance.

**Affected principle:** Brand clarity and future extensibility.

**Recommendation:** Treat the brand statement as the defining promise for audience experience contributions about a performance. Before adding a contribution not based on attendance, decide whether it belongs to a separate non-authoritative participation class or requires a refined brand vocabulary.

## 6. Account Linking Is Not Defined

The Audience Journey offers optional My Encore creation after review submission, but the strategy does not yet define how an anonymous/pseudonymous reviewer is securely linked to an audience account.

**Affected principle:** Identity grants access; historic authority provenance remains unchanged.

**Recommendation:** Define explicit claim, verification, merge, split, recovery, correction, and deletion journeys before audience-account implementation.

## 7. Moderation Authority Remains a Separate Trust Decision

Verified attendance grants contribution authority but does not settle who has final publication authority. Current organisation moderation may create a conflict where the reviewed organisation controls publication.

**Affected principle:** Audience Trust and independent platform position.

**Recommendation:** Resolve final publication policy, organisation flagging, Encore escalation, audience status, and appeal before scaling review volume or derived intelligence.

## 8. Advisory Reviews Require Clear Status

Several advisory reviews were prepared before the Product Blueprint, Audience Journey, and Platform Strategy existed. They have now been reconciled or explicitly marked as superseded, but their advisory status must not be mistaken for implementation approval.

**Affected principle:** Documentation First.

**Recommendation:** Continue to mark older advisory documents as historical or superseded when a current review replaces them, and retain decision history without allowing stale caveats to govern current work.
