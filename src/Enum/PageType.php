<?php

declare(strict_types=1);

namespace AsterMD\Sdk\Enum;

/**
 * The kind of storefront page a visitor viewed, reported with a `page_view` session event.
 *
 * Passed to {@see \AsterMD\Sdk\Resource\SessionEvents::pageView()} to classify the
 * page. The server stores the latest page view on the session as `last_page`
 * (returned by {@see \AsterMD\Sdk\Resource\Sessions::view()}), so you can see
 * where a visitor who dropped off left the funnel.
 *
 * The list is fixed: the API rejects any other value. Classify a page that fits
 * none of the specific kinds as {@see self::Other}.
 */
enum PageType: string
{
    /** The storefront home or landing page. */
    case Home = 'home';

    /** A product detail page. Pass the product's `product_id` with the page view. */
    case Product = 'product';

    /** The cart page. */
    case Cart = 'cart';

    /** A page of the pre-qualifying or intake questionnaire. */
    case Intake = 'intake';

    /** The checkout page. */
    case Checkout = 'checkout';

    /** A post-cart upsell offer page. */
    case Upsell = 'upsell';

    /** The order confirmation (thank-you) page. */
    case ThankYou = 'thank_you';

    /** A content-only page, such as terms of service, the privacy policy, or an about page. */
    case Cms = 'cms';

    /** Any page that fits none of the other kinds. */
    case Other = 'other';
}
