<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Exception;

use Localheinz\GitHub\ChangeLog\Exception\ExceptionInterface;
use Localheinz\GitHub\ChangeLog\Exception\RuntimeException;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Localheinz\GitHub\ChangeLog\Exception\RuntimeException
 */
final class RuntimeExceptionTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsRuntimeException(): void
    {
        self::assertClassExtends(\RuntimeException::class, RuntimeException::class);
    }

    public function testImplementsExceptionInterface(): void
    {
        self::assertClassImplementsInterface(ExceptionInterface::class, RuntimeException::class);
    }
}
