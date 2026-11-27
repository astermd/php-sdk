<?php

declare(strict_types=1);

namespace AsterMD\Sdk\Resource;

use AsterMD\Sdk\Enum\PageType;
use AsterMD\Sdk\Response;

/**
 * Records and reads a session's browsing events on the `sales` service.
 *
 * Session events are rows of an append-only log tied to a session UUID. Most
 * journey events are recorded for you as a side effect of other calls (cart,
 * intake, and checkout operations); this resource records the two that only your
 * storefront can observe:
 *
 *  - **Page views** — call {@see self::pageView()} on every page the visitor
 *    opens. The latest one becomes the session's `last_page`, which shows where a
 *    visitor who drops off left. A page view counts as activity for abandonment
 *    tracking but does not move the session's funnel step.
 *  - **Identity verification start** — call
 *    {@see self::beginIdentityVerification()} when the visitor starts verifying
 *    their identity.
 *
 * ```php
 * use AsterMD\Sdk\Enum\PageType;
 *
 * $client->sessionEvents()->pageView($session, PageType::Product, '/products/semaglutide', [
 *     'title'      => 'Semaglutide — Weight Loss',
 *     'product_id' => '64b2f0c7e3a1d2b3c4e5f6b1',
 * ]);
 * ```
 *
 * `list()` and `view()` read the recorded events back.
 */
final class SessionEvents extends AbstractResource
{
    /**
     * Records a `page_view` event: the visitor opened a page of your storefront.
     *
     * Call this on every page load, server-side. The server stores the page as the
     * session's `last_page` (with `last_page_at`), counts it as activity for
     * abandonment tracking, and leaves the session's funnel step unchanged. The
     * SDK builds the `page` object from `$type`, `$path`, and the optional
     * `$page` fields; `$type` and `$path` win over the same keys in `$page`.
     * Inspect `$response->data()` for the recorded event, whose `meta` holds the
     * page as `page_type`, `page_path`, `page_url`, `page_title`, `referrer`, and
     * `product_id`.
     *
     * @param string                                                                     $session the session UUID to record the event against
     * @param PageType                                                                   $type    the kind of page viewed
     * @param string                                                                     $path    the page's path, without the query string (e.g. `/cart`)
     * @param array{url?: string, title?: string, referrer?: string, product_id?: string} $page    optional page details: the full `url`, the document
     *                                                                                            `title`, the `referrer` the visitor came from, and the
     *                                                                                            `product_id` (24-character hex ID) on product pages
     * @param array<string, mixed>                                                       $meta    optional event payload, stored with the event as sent;
     *                                                                                            omitted from the request when empty
     *
     * @return Response the created-event envelope (HTTP 201); `data()` contains the new session-event record
     *
     * @throws \AsterMD\Sdk\Exception\ValidationException if the session UUID or a page field is malformed
     * @throws \AsterMD\Sdk\Exception\ApiException        on a non-2xx response from the server
     * @throws \AsterMD\Sdk\Exception\TransportException  on a network-level failure
     */
    public function pageView(string $session, PageType $type, string $path, array $page = [], array $meta = []): Response
    {
        $body = [
            'session' => $session,
            'event' => 'page_view',
            'page' => ['type' => $type->value, 'path' => $path] + $page,
        ];

        return $this->create($body, $meta);
    }

    /**
     * Records a `begin_identity_verification` event: the visitor started verifying their identity.
     *
     * Call this once, when the visitor opens the identity-verification step of
     * your flow. Pass any context you want kept with the event in `$meta` (for
     * example the verification method chosen); the server stores it as sent.
     * Inspect `$response->data()` for the recorded event.
     *
     * @param string               $session the session UUID to record the event against
     * @param array<string, mixed> $meta    optional event payload (e.g. `['method' => 'ssn']`), stored with
     *                                      the event as sent; omitted from the request when empty
     *
     * @return Response the created-event envelope (HTTP 201); `data()` contains the new session-event record
     *
     * @throws \AsterMD\Sdk\Exception\ValidationException if the session UUID is malformed
     * @throws \AsterMD\Sdk\Exception\ApiException        on a non-2xx response from the server
     * @throws \AsterMD\Sdk\Exception\TransportException  on a network-level failure
     */
    public function beginIdentityVerification(string $session, array $meta = []): Response
    {
        return $this->create(['session' => $session, 'event' => 'begin_identity_verification'], $meta);
    }

    /**
     * Lists recorded session events with optional search, sorting, and pagination.
     *
     * Returns the events visible to your API credential. `search` matches on the session UUID, so `['search' => $session]` narrows
     * the list to one visitor's journey. Inspect `$response->meta()` for
     * pagination metadata.
     *
     * @param array<string, scalar> $query filter and pagination parameters: `page`, `limit`, `search`,
     *                                     `sort_by`, and `sort_order` (`asc`|`desc`)
     *
     * @return Response the paginated event list; `data()` contains the session-event records and `meta()` holds pagination info
     *
     * @throws \AsterMD\Sdk\Exception\ApiException       on a non-2xx response from the server
     * @throws \AsterMD\Sdk\Exception\TransportException on a network-level failure
     */
    public function list(array $query = []): Response
    {
        return $this->transport->send('sales', 'GET', '/session-events/list', query: $query);
    }

    /**
     * Fetches a single session event by its ID.
     *
     * Use the `_id` returned by {@see self::pageView()}, {@see self::beginIdentityVerification()},
     * or {@see self::list()}. Inspect `$response->data()` for the event record:
     * `session`, `event`, `meta`, `channel_id`, and `created_at`.
     *
     * @param string $id the 24-character hex ID of the session event
     *
     * @return Response the event envelope; `data()` contains the session-event record
     *
     * @throws \AsterMD\Sdk\Exception\NotFoundException  if no session event exists for the given ID
     * @throws \AsterMD\Sdk\Exception\ApiException       on a non-2xx response from the server
     * @throws \AsterMD\Sdk\Exception\TransportException on a network-level failure
     */
    public function view(string $id): Response
    {
        return $this->transport->send('sales', 'GET', '/session-events/view/{id}', pathParams: ['id' => $id]);
    }

    /**
     * Sends a session event; `meta` is dropped when empty because PHP encodes `[]` as a JSON array, not an object.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $meta
     */
    private function create(array $body, array $meta): Response
    {
        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return $this->transport->send('sales', 'POST', '/session-events/create', body: $body);
    }
}
