<?php

declare(strict_types=1);

namespace AsterMD\Sdk\Tests\Unit\Resource;

use AsterMD\Sdk\Enum\PageType;
use AsterMD\Sdk\Http\Transport;
use AsterMD\Sdk\Http\UrlBuilder;
use AsterMD\Sdk\Resource\SessionEvents;
use AsterMD\Sdk\Tests\Support\MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class SessionEventsTest extends TestCase
{
    private MockHttpClient $http;
    private SessionEvents $resource;

    protected function setUp(): void
    {
        $factory = new Psr17Factory();
        $this->http = new MockHttpClient();
        $this->resource = new SessionEvents(new Transport(
            httpClient: $this->http,
            requestFactory: $factory,
            streamFactory: $factory,
            urlBuilder: new UrlBuilder('api.astermd.com'),
            tokenProvider: static fn (): string => 'jwt',
            onUnauthorized: static fn (): bool => false,
        ));
    }

    public function testPageViewPostsTypeAndPathWithoutMeta(): void
    {
        $this->http->enqueue(201, '{"success":true,"message":"ok","data":{"_id":"6a448ca2a2a4755628c5c54c"},"meta":{}}');

        $response = $this->resource->pageView('sess-uuid', PageType::Cart, '/cart');

        $req = $this->http->lastRequest();
        self::assertSame('POST', $req->getMethod());
        self::assertSame(
            'https://api.astermd.com/v1/sales/session-events/create',
            (string) $req->getUri(),
        );
        self::assertSame('Bearer jwt', $req->getHeaderLine('Authorization'));
        self::assertSame('application/json', $req->getHeaderLine('Content-Type'));
        self::assertJsonStringEqualsJsonString(
            '{"session":"sess-uuid","event":"page_view","page":{"type":"cart","path":"/cart"}}',
            (string) $req->getBody(),
        );
        self::assertSame(201, $response->statusCode());
    }

    public function testPageViewForwardsOptionalPageFieldsAndMeta(): void
    {
        $this->http->enqueue(201, '{"success":true,"message":"ok","data":{},"meta":{}}');

        $this->resource->pageView(
            'sess-uuid',
            PageType::Product,
            '/products/semaglutide',
            [
                'url' => 'https://shop.example.com/products/semaglutide?utm_source=google',
                'title' => 'Semaglutide',
                'referrer' => 'https://www.example.org/',
                'product_id' => '64b2f0c7e3a1d2b3c4e5f6b1',
            ],
            ['ab_variant' => 'b'],
        );

        self::assertJsonStringEqualsJsonString(
            '{"session":"sess-uuid","event":"page_view","page":{"type":"product","path":"/products/semaglutide",'
            . '"url":"https://shop.example.com/products/semaglutide?utm_source=google","title":"Semaglutide",'
            . '"referrer":"https://www.example.org/","product_id":"64b2f0c7e3a1d2b3c4e5f6b1"},"meta":{"ab_variant":"b"}}',
            (string) $this->http->lastRequest()->getBody(),
        );
    }

    public function testPageViewTypeAndPathArgumentsWinOverPageKeys(): void
    {
        $this->http->enqueue(201, '{"success":true,"message":"ok","data":{},"meta":{}}');

        $this->resource->pageView('sess-uuid', PageType::ThankYou, '/thank-you', ['type' => 'home', 'path' => '/']);

        self::assertJsonStringEqualsJsonString(
            '{"session":"sess-uuid","event":"page_view","page":{"type":"thank_you","path":"/thank-you"}}',
            (string) $this->http->lastRequest()->getBody(),
        );
    }

    public function testBeginIdentityVerificationPostsEventWithoutPage(): void
    {
        $this->http->enqueue(201, '{"success":true,"message":"ok","data":{},"meta":{}}');

        $this->resource->beginIdentityVerification('sess-uuid');

        $req = $this->http->lastRequest();
        self::assertSame('POST', $req->getMethod());
        self::assertSame(
            'https://api.astermd.com/v1/sales/session-events/create',
            (string) $req->getUri(),
        );
        self::assertJsonStringEqualsJsonString(
            '{"session":"sess-uuid","event":"begin_identity_verification"}',
            (string) $req->getBody(),
        );
    }

    public function testBeginIdentityVerificationForwardsMetaAsObject(): void
    {
        $this->http->enqueue(201, '{"success":true,"message":"ok","data":{},"meta":{}}');

        $this->resource->beginIdentityVerification('sess-uuid', ['method' => 'ssn']);

        self::assertJsonStringEqualsJsonString(
            '{"session":"sess-uuid","event":"begin_identity_verification","meta":{"method":"ssn"}}',
            (string) $this->http->lastRequest()->getBody(),
        );
    }

    public function testListSendsQueryParameters(): void
    {
        $this->http->enqueue(200, '{"success":true,"message":"ok","data":[],"meta":{}}');

        $this->resource->list(['page' => 2, 'limit' => 10, 'search' => 'sess-uuid']);

        $req = $this->http->lastRequest();
        self::assertSame('GET', $req->getMethod());
        self::assertSame(
            'https://api.astermd.com/v1/sales/session-events/list?page=2&limit=10&search=sess-uuid',
            (string) $req->getUri(),
        );
        self::assertSame('Bearer jwt', $req->getHeaderLine('Authorization'));
    }

    public function testViewGetsById(): void
    {
        $this->http->enqueue(200, '{"success":true,"message":"ok","data":{},"meta":{}}');

        $this->resource->view('6a448ca2a2a4755628c5c54c');

        $req = $this->http->lastRequest();
        self::assertSame('GET', $req->getMethod());
        self::assertSame(
            'https://api.astermd.com/v1/sales/session-events/view/6a448ca2a2a4755628c5c54c',
            (string) $req->getUri(),
        );
    }
}
