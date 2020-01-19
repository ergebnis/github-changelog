<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2020 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Exception;

use Ergebnis\GitHub\Changelog\Exception\ExceptionInterface;
use Ergebnis\GitHub\Changelog\Exception\RuntimeException;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Exception\RuntimeException
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
