<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Exception;

use Localheinz\GitHub\ChangeLog\Exception\ExceptionInterface;
use Localheinz\GitHub\ChangeLog\Exception\InvalidArgumentException;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class InvalidArgumentExceptionTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsInvalidArgumentException()
    {
        $this->assertClassExtends(\InvalidArgumentException::class, InvalidArgumentException::class);
    }

    public function testImplementsExceptionInterface()
    {
        $this->assertClassImplementsInterface(ExceptionInterface::class, InvalidArgumentException::class);
    }
}
