<?php

declare(strict_types=1);

namespace AsterMD\Sdk\Resource;

use AsterMD\Sdk\Response;

/**
 * Coupon checks for a storefront cart on the `sales` service.
 *
 * Use this resource just before creating the payment: it tells the storefront
 * whether a coupon code the shopper typed can be used on the current cart, and
 * what it takes off. AsterMD prices the cart itself from its catalogue — callers
 * send product, variant or plan identifiers and quantities, never amounts.
 *
 * The call is authenticated with the channel's API user credential and only
 * applies to a channel whose payment provider is a storefront gateway. Nothing is
 * counted against the coupon by a check; redemption is recorded later, when the
 * order is synced.
 *
 * @phpstan-type CouponCartItem array{plan_id?: string, product_id?: string, variant_id?: string, quantity: int}
 */
final class Coupons extends AbstractResource
{
    /**
     * Checks a coupon code against a cart without redeeming it.
     *
     * Call this when the shopper applies a code at checkout, before the payment
     * is created. The server validates the coupon's status, validity window,
     * patient, products, budget, scope and redemption limits, and prices the cart.
     * Name the shopper with one of `$patientId`, `$email` or `$opportunityId`
     * (when several are sent the server uses them in that order); a shopper who is
     * not a patient yet only fits a coupon open to anyone. The check has no
     * side effects. Inspect `$response->data()` for the coupon terms, the price
     * before and after the discount, and the discounted lines.
     *
     * @param string              $code          the code the shopper typed (not case sensitive)
     * @param list<CouponCartItem> $items         the cart, main item first, 1 to 6 lines. Each line is either a
     *                                            `plan_id` or a `product_id` with `variant_id` (never both), plus
     *                                            a `quantity` from 1 to 999
     * @param string|null         $patientId     the AsterMD patient id of the shopper, when already a patient;
     *                                            sent only when provided
     * @param string|null         $email         the shopper's email, used when `$patientId` is not sent;
     *                                            sent only when provided
     * @param string|null         $opportunityId the shopper's opportunity id, used when neither `$patientId` nor
     *                                            `$email` is sent; sent only when provided
     *
     * @return Response the coupon check envelope; `data()` contains `coupon_id`, `name`, `discount_type`, `value`,
     *                  `tiers`, `default_scope`, `gross_amount_cents`, `discount_cents`, `amount_cents`,
     *                  `currency` and the discounted `items`
     *
     * @throws \AsterMD\Sdk\Exception\NotFoundException   if no coupon exists with this code
     * @throws \AsterMD\Sdk\Exception\ValidationException if the coupon cannot be used on this cart or the cart cannot be priced
     * @throws \AsterMD\Sdk\Exception\ApiException        on any other non-2xx response from the server
     * @throws \AsterMD\Sdk\Exception\TransportException  on a network-level failure
     */
    public function validate(
        string $code,
        array $items,
        ?string $patientId = null,
        ?string $email = null,
        ?string $opportunityId = null,
    ): Response {
        $body = ['code' => $code, 'items' => $items];

        if ($patientId !== null) {
            $body['patient_id'] = $patientId;
        }
        if ($email !== null) {
            $body['email'] = $email;
        }
        if ($opportunityId !== null) {
            $body['opportunity_id'] = $opportunityId;
        }

        return $this->transport->send('sales', 'POST', '/coupons/validate', body: $body);
    }
}
