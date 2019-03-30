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

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Util;

use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\GitHub\ChangeLog\Util\GitInterface;
use Localheinz\GitHub\ChangeLog\Util\RepositoryResolver;
use Localheinz\GitHub\ChangeLog\Util\RepositoryResolverInterface;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 */
final class RepositoryResolverTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsRepositoryResolverInterface(): void
    {
        $this->assertClassImplementsInterface(RepositoryResolverInterface::class, RepositoryResolver::class);
    }

    public function testResolveThrowsRuntimeExceptionIfUnableToDetermineRemoteUrls(): void
    {
        $git = $this->createMock(GitInterface::class);

        $git
            ->expects(self::once())
            ->method('remoteUrls')
            ->willThrowException(new Exception\RuntimeException());

        $resolver = new RepositoryResolver($git);

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve repository using git meta data.');

        $resolver->resolve();
    }

    public function testResolveThrowsRuntimeExceptionIfNoRemoteUrlsHaveBeenFound(): void
    {
        $remoteUrls = [];

        $git = $this->createMock(GitInterface::class);

        $git
            ->expects(self::once())
            ->method('remoteUrls')
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git);

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Could not find any remote URLs.');

        $resolver->resolve();
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidRemoteUrl
     *
     * @param string $remoteUrl
     */
    public function testResolveThrowsRuntimeExceptionIfNoValidRemoteUrlsCouldBeFound(string $remoteUrl): void
    {
        $faker = $this->faker();

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words;

        $remoteUrls = \array_combine(
            $remoteNames,
            \array_fill(
                0,
                \count($remoteNames),
                $remoteUrl
            )
        );

        $git = $this->createMock(GitInterface::class);

        $git
            ->expects(self::once())
            ->method('remoteUrls')
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git);

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Could not find a valid remote URL.');

        $resolver->resolve();
    }

    public function testResolveWithoutFromRemoteNamesReturnsRepositoryUsingFirstFoundValidRemoteUrl(): void
    {
        $faker = $this->faker();

        $owner = $faker->unique()->word;
        $name = $faker->unique()->word;
        $remoteUrl = $this->remoteUrlFromOwnerAndName(
            $owner,
            $name
        );

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words;

        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(static function () use ($faker) {
                return $faker->sentence();
            }, $remoteNames)
        );

        $remoteName = $faker->randomElement($remoteNames);

        $remoteUrls[$remoteName] = $remoteUrl;

        $git = $this->createMock(GitInterface::class);

        $git
            ->expects(self::once())
            ->method('remoteUrls')
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git);

        $repository = $resolver->resolve();

        self::assertInstanceOf(Resource\RepositoryInterface::class, $repository);
        self::assertSame($owner, $repository->owner());
        self::assertSame($name, $repository->name());
    }

    public function testResolveWithFromRemoteNamesThrowsRuntimeExceptionIfNoValidRemoteUrlsCanBeConsidered(): void
    {
        $faker = $this->faker();

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words;

        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(function () use ($faker) {
                $owner = $faker->unique()->word;
                $name = $faker->unique()->word;

                return $this->remoteUrlFromOwnerAndName(
                    $owner,
                    $name
                );
            }, $remoteNames)
        );

        /** @var string[] $fromRemoteNames */
        $fromRemoteNames = $faker->unique()->words;

        $git = $this->createMock(GitInterface::class);

        $git
            ->expects(self::once())
            ->method('remoteUrls')
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git);

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Could not find a valid remote URL for remotes "%s".',
            \implode('", "', $fromRemoteNames)
        ));

        $resolver->resolve(...$fromRemoteNames);
    }

    public function testResolveWithFromRemoteNamesReturnsRepositoryUsingFirstFoundValidRemoteUrlIfItCanBeConsidered(): void
    {
        $faker = $this->faker();

        $owner = $faker->unique()->word;
        $name = $faker->unique()->word;
        $remoteUrl = $this->remoteUrlFromOwnerAndName(
            $owner,
            $name
        );

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words(10);
        $fromRemoteNames = $faker->randomElements($remoteNames, 3);

        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(function () use ($faker) {
                $owner = $faker->unique()->word;
                $name = $faker->unique()->word;

                return $this->remoteUrlFromOwnerAndName(
                    $owner,
                    $name
                );
            }, $remoteNames)
        );

        $remoteName = \reset($fromRemoteNames);

        $remoteUrls[$remoteName] = $remoteUrl;

        $git = $this->createMock(GitInterface::class);

        $git
            ->expects(self::once())
            ->method('remoteUrls')
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git);

        $repository = $resolver->resolve(...$fromRemoteNames);

        self::assertInstanceOf(Resource\RepositoryInterface::class, $repository);
        self::assertSame($owner, $repository->owner());
        self::assertSame($name, $repository->name());
    }

    private function remoteUrlFromOwnerAndName(string $owner, string $name): string
    {
        return \sprintf(
            'git@github.com:%s/%s.git',
            $owner,
            $name
        );
    }
}
