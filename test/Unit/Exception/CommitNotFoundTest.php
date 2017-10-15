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

use Localheinz\GitHub\ChangeLog\Exception\CommitNotFound;
use Localheinz\GitHub\ChangeLog\Exception\ExceptionInterface;
use PHPUnit\Framework;
use Refinery29\Test\Util\TestHelper;

final class CommitNotFoundTest extends Framework\TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $this->assertFinal(CommitNotFound::class);
    }

    public function testExtendsRuntimeException()
    {
        $this->assertExtends(\RuntimeException::class, CommitNotFound::class);
    }

    public function testImplementsExceptionInterface()
    {
        $this->assertImplements(ExceptionInterface::class, CommitNotFound::class);
    }

    public function testFromOwnerRepositoryAndShaCreatesException()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = \implode(
            '-',
            $faker->words()
        );
        $sha = $faker->sha1;

        $exception = CommitNotFound::fromOwnerRepositoryAndReference(
            $owner,
            $repository,
            $sha
        );

        $this->assertInstanceOf(CommitNotFound::class, $exception);

        $message = \sprintf(
            'Could not find commit "%s" in "%s/%s".',
            $sha,
            $owner,
            $repository
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }
}
