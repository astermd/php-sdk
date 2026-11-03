<?php

declare(strict_types=1);

namespace AsterMD\Sdk\Tests\Unit\Resource;

use AsterMD\Sdk\Exception\NotFoundException;
use AsterMD\Sdk\Exception\ValidationException;
use AsterMD\Sdk\Http\Transport;
use AsterMD\Sdk\Http\UrlBuilder;
use AsterMD\Sdk\Resource\Coupons;
use AsterMD\Sdk\Tests\Support\MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class CouponsTest extends TestCase
{
    private const OK = '{"success":true,"message":"ok","data":{"discount_cents":1980},"meta":{}}';

    private MockHttpClient $http;
    private Coupons $resource;

    protected function setUp(): void
    {
        $factory = new Psr17Factory();
        $this->http = new MockHttpClient();
        $this->resource = new Coupons(new Transport(
            httpClient: $this->http,
            requestFactory: $factory,
            streamFactory: $factory,
            urlBuilder: new UrlBuilder('api.astermd.com'),
            tokenProvider: static fn (): string => 'jwt',
            onUnauthorized: static fn (): bool => false,
        ));
    }

    public function testValidateSendsCodeAndItemsOnly(): void
    {
        $this->http->enqueue(200, self::OK);

        $response = $this->resource->validate('WELCOME20', [
            ['plan_id' => 'plan1', 'quantity' => 1],
            ['product_id' => 'p1', 'variant_id' => 'v1', 'quantity' => 2],
        ]);

        $req = $this->http->lastRequest();
        self::assertSame('POST', $req->getMethod());
        self::assertSame('https://api.astermd.com/v1/sales/coupons/validate', (string) $req->getUri());
        self::assertSame('Bearer jwt', $req->getHeaderLine('Authorization'));
        self::assertSame('application/json', $req->getHeaderLine('Content-Type'));
        self::assertJsonStringEqualsJsonString(
            '{"code":"WELCOME20","items":[{"plan_id":"plan1","quantity":1},{"product_id":"p1","variant_id":"v1","quantity":2}]}',
            (string) $req->getBody(),
        );
        self::assertSame(['discount_cents' => 1980], $response->data());
    }

    public function testValidateForwardsShopperIdentifiersWhenProvided(): void
    {
        $this->http->enqueue(200, self::OK);

        $this->resource->validate(
            'WELCOME20',
            [['plan_id' => 'plan1', 'quantity' => 1]],
            patientId: 'pat1',
            email: 'jane.doe@example.com',
            opportunityId: 'opp1',
        );

        self::assertJsonStringEqualsJsonString(
            '{"code":"WELCOME20","items":[{"plan_id":"plan1","quantity":1}],'
                . '"patient_id":"pat1","email":"jane.doe@example.com","opportunity_id":"opp1"}',
            (string) $this->http->lastRequest()->getBody(),
        );
    }

    public function testValidateMapsUnknownCodeToNotFound(): void
    {
        $this->http->enqueue(404, '{"success":false,"message":"There is no coupon with this code"}');

        $this->expectException(NotFoundException::class);
        $this->resource->validate('NOPE', [['plan_id' => 'plan1', 'quantity' => 1]]);
    }

    public function testValidateMapsCouponRefusalToValidationException(): void
    {
        $this->http->enqueue(422, '{"success":false,"message":"The coupon has expired"}');

        $this->expectException(ValidationException::class);
        $this->resource->validate('OLD', [['plan_id' => 'plan1', 'quantity' => 1]]);
    }
}
