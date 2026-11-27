# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.9] - 2026-11-28

### Fixed

- `checkoutEvents()->create()` takes an optional `CheckoutEvent $event`
  (default `CheckoutVisited`), so `AddedShippingInfo` and `AddedPaymentInfo` are
  sent to `/checkout-events/create` as the API requires. 0.0.8 always sent
  `checkout_visited` from `create()` and documented the detail events on
  `update()`, which answers 404 when the session has no checkout record yet.

## [0.0.8] - 2026-11-27

### Added

- `sessionEvents()` resource (`/v1/sales/session-events/*`):
  `pageView()` records a `page_view` for every page the visitor opens,
  `beginIdentityVerification()` records the start of identity verification, and
  `list()` / `view()` read the event log back.
- `PageType` enum for the kind of page viewed: `home`, `product`, `cart`,
  `intake`, `checkout`, `upsell`, `thank_you`, `cms`, `other`.
- `CheckoutEvent::AddedShippingInfo` and `CheckoutEvent::AddedPaymentInfo`, sent
  through `checkoutEvents()->update()` when the visitor submits shipping or
  payment details (send `payment_method` with the latter).
  `checkoutEvents()->create()` documents the optional `product_id` field.
- `carts()->create()` and `carts()->update()` take optional `cartTotal` (major
  units) and `currency` (ISO 4217; the server uses `USD` when not sent). The cart
  response returns both.
- `sessions()->view()` documents `last_page` (`{type, path, product_id}`) and
  `last_page_at` on each session's read-model.

## [0.0.7] - 2026-11-24

### Added

- `sessions()->create()` documents the optional `affiliate_link_id` field: the
  plain affiliate link ID or the encrypted `_amd` token from an affiliate link
  (with or without the `_amd=` prefix). The server resolves the link and its
  affiliate, and silently drops a value it cannot read. It is ignored by
  `sessions()->update()`. `sessions()->view()` now returns `affiliate_link_id`
  and `affiliate_id` on each session's read-model.
- `opportunities()->create()` and `opportunities()->update()` document the
  optional `attribution` object, including `affiliate_link_id` (same
  semantics); the server fills `attribution.affiliate_id` from the link.
  Pass-through data, this only documents the shape.

## [0.0.4] - 2026-11-03

### Added

- `coupons()->validate()` checks a coupon code against a cart
  (`POST /v1/sales/coupons/validate`) without redeeming it.

### Changed

- `treatments()->sync()` payment `card` sub-object: `bin` is replaced by
  `last_4` (the card's last four digits).

## [0.0.3] - 2026-09-21

### Added

- `checkoutEvents()->update()` documents the optional `payment` field on
  `order_placed`/`order_declined` events (settled payment method, with a
  nested `card` sub-object) — it was already forwarded as pass-through data,
  this only documents the shape.
- `carts()->create()` and `carts()->update()` accept an optional `variant_id`
  on each item.
- `sessions()->create()` and `sessions()->update()` document the optional
  `verification` field (identity/contact verification already performed by
  the caller) — pass-through data, this only documents the shape.

### Changed

- **BREAKING:** `treatments()->sync()` gains `$payment` and `$verification`
  optional array parameters, and `$userAgent` moves earlier in the signature
  and becomes a required, non-empty argument (the API now requires the
  `User-Agent` header on this endpoint). Old signature:
  `sync(string $session, array $orderIds, ?string $utmSource = null, ?string $userAgent = null)`.
  New signature:
  `sync(string $session, array $orderIds, string $userAgent, ?string $utmSource = null, ?array $payment = null, ?array $verification = null)`.

## [0.0.2] - 2026-09-08

### Added

- `intakeSubmissions()->uploadFile()` for single-request uploads of `file`-type
  answers, and the four multipart-upload methods —
  `initiateMultipartUpload()`, `uploadMultipartPart()`,
  `finishMultipartUpload()`, `abortMultipartUpload()` — for files too large for
  one request.
- `intakeSubmissions()->uploadLargeFile()`, which drives the whole multipart
  flow: it streams the file from disk one part at a time, collects each part's
  ETag, finishes the upload, and aborts it if any step fails.
- `AsterMD\Sdk\Http\FileUpload`, an immutable file value object built with
  `FileUpload::fromPath()` or `FileUpload::fromContents()`, accepted by every
  endpoint that takes bytes rather than JSON.

### Changed

- `Transport::send()` accepts an optional `FileUpload` and encodes it as a
  `multipart/form-data` body; it remains the only place that builds requests.
- Debug logging drops the body of a file-upload request entirely. Upload bytes
  are PHI, and rendering them into a cURL command would make the log unusable.

## [0.0.1] - 2026-08-10

First public release.

### Added

- `AsterMDClient` entry point wrapping the AsterMD order-flow API: sessions,
  intake submissions, carts, checkout events, teleforms, patients, opportunities,
  treatments, doctors' networks, channels, and the read-only catalog resources
  (products, categories, lab tests, medications, shipping options).
- `verification()` for address autofill and verification, email verification,
  and identity verification; `geo()` for IP geolocation and geo-blocklist
  checks.
- Native-cURL PSR-18 transport with no Guzzle dependency; any PSR-18 client can
  be injected instead.
- Automatic JWT acquisition, caching, and renewal via `TokenManager`, with
  pluggable `TokenStore` backends (`InMemoryTokenStore`, `FileTokenStore`).
- Redacted debug logging. `debug: true` renders every request as a
  copy-pasteable cURL command with bearer tokens, the OAuth2 client secret, PHI
  verification tokens, and `patients/*` bodies masked. Pass `debugRedact: false`
  to log verbatim.
- `DailyFileLogSink`: one debug log file per day, pruned after
  `debugRetentionDays` (default 7). Supply your own `debugSink` closure to route
  entries elsewhere instead.
- `QueryParamCipher` for encrypted URL query-param tokens.

### Requirements

- PHP 8.4 or newer; 8.5 recommended.
