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
use Localheinz\GitHub\ChangeLog\Exception\PullRequestNotFound;
use PHPUnit\Framework;
use Refinery29\Test\Util\TestHelper;

final class PullRequestNotFoundTest extends Framework\TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $this->assertFinal(PullRequestNotFound::class);
    }

    public function testExtendsRuntimeException()
    {
        $this->assertExtends(\RuntimeException::class, PullRequestNotFound::class);
    }

    public function testImplementsExceptionInterface()
    {
        $this->assertImplements(ExceptionInterface::class, PullRequestNotFound::class);
    }

    public function testFromOwnerRepositoryAndReferenceCreatesException()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $number = $faker->randomNumber();

        $exception = PullRequestNotFound::fromOwnerNameAndNumber(
            $owner,
            $name,
            $number
        );

        $this->assertInstanceOf(PullRequestNotFound::class, $exception);

        $message = \sprintf(
            'Could not find pull request "%s" in "%s/%s".',
            $number,
            $owner,
            $name
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }
}
