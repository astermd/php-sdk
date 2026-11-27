<?php

declare(strict_types=1);

namespace AsterMD\Sdk\Tests\Unit\Enum;

use AsterMD\Sdk\Enum\PageType;
use PHPUnit\Framework\TestCase;

final class PageTypeTest extends TestCase
{
    public function testBackingValuesMatchApiContract(): void
    {
        self::assertSame(
            ['home', 'product', 'cart', 'intake', 'checkout', 'upsell', 'thank_you', 'cms', 'other'],
            array_map(static fn (PageType $type): string => $type->value, PageType::cases()),
        );
    }
}
