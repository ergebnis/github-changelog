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

namespace Ergebnis\GitHub\Changelog\Test\Unit\Util;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Util\GitInterface;
use Ergebnis\GitHub\Changelog\Util\RepositoryResolver;
use Ergebnis\GitHub\Changelog\Util\RepositoryResolverInterface;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;
use Prophecy\PhpUnit;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Util\RepositoryResolver
 */
final class RepositoryResolverTest extends Framework\TestCase
{
    use Helper;
    use PhpUnit\ProphecyTrait;

    public function testImplementsRepositoryResolverInterface(): void
    {
        self::assertClassImplementsInterface(RepositoryResolverInterface::class, RepositoryResolver::class);
    }

    public function testResolveThrowsRuntimeExceptionIfUnableToDetermineRemoteUrls(): void
    {
        $git = $this->prophesize(GitInterface::class);

        $git
            ->remoteUrls()
            ->shouldBeCalled()
            ->willThrow(new Exception\RuntimeException());

        $resolver = new RepositoryResolver($git->reveal());

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve repository using git meta data.');

        $resolver->resolve();
    }

    public function testResolveThrowsRuntimeExceptionIfNoRemoteUrlsHaveBeenFound(): void
    {
        $remoteUrls = [];

        $git = $this->prophesize(GitInterface::class);

        $git
            ->remoteUrls()
            ->shouldBeCalled()
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git->reveal());

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Could not find any remote URLs.');

        $resolver->resolve();
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
     *
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidRemoteUrl
     *
     * @param string $remoteUrl
     */
    public function testResolveThrowsRuntimeExceptionIfNoValidRemoteUrlsCouldBeFound(string $remoteUrl): void
    {
        $faker = self::faker();

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

        $git = $this->prophesize(GitInterface::class);

        $git
            ->remoteUrls()
            ->shouldBeCalled()
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git->reveal());

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Could not find a valid remote URL.');

        $resolver->resolve();
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
     */
    public function testResolveWithoutFromRemoteNamesReturnsRepositoryUsingFirstFoundValidRemoteUrl(): void
    {
        $faker = self::faker();

        $owner = $faker->unique()->word;
        $name = $faker->unique()->word;
        $remoteUrl = $this->remoteUrlFromOwnerAndName(
            $owner,
            $name
        );

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words;

        /** @var string[] $remoteUrls */
        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(static function () use ($faker): string {
                return $faker->sentence();
            }, $remoteNames)
        );

        $remoteName = $faker->randomElement($remoteNames);

        $remoteUrls[$remoteName] = $remoteUrl;

        $git = $this->prophesize(GitInterface::class);

        $git
            ->remoteUrls()
            ->shouldBeCalled()
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git->reveal());

        $repository = $resolver->resolve();

        self::assertSame($owner, $repository->owner());
        self::assertSame($name, $repository->name());
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
     */
    public function testResolveWithFromRemoteNamesThrowsRuntimeExceptionIfNoValidRemoteUrlsCanBeConsidered(): void
    {
        self::markTestSkipped('Is flaky');

        $faker = self::faker();

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words;

        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(function () use ($faker): string {
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

        $git = $this->prophesize(GitInterface::class);

        $git
            ->remoteUrls()
            ->shouldBeCalled()
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git->reveal());

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Could not find a valid remote URL for remotes "%s".',
            \implode('", "', $fromRemoteNames)
        ));

        $resolver->resolve(...$fromRemoteNames);
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
     */
    public function testResolveWithFromRemoteNamesReturnsRepositoryUsingFirstFoundValidRemoteUrlIfItCanBeConsidered(): void
    {
        $faker = self::faker();

        $owner = $faker->unique()->word;
        $name = $faker->unique()->word;
        $remoteUrl = $this->remoteUrlFromOwnerAndName(
            $owner,
            $name
        );

        /** @var string[] $remoteNames */
        $remoteNames = $faker->unique()->words(10);
        $fromRemoteNames = $faker->randomElements($remoteNames, 3);

        /** @var string[] $remoteUrls */
        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(function () use ($faker): string {
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

        $git = $this->prophesize(GitInterface::class);

        $git
            ->remoteUrls()
            ->shouldBeCalled()
            ->willReturn($remoteUrls);

        $resolver = new RepositoryResolver($git->reveal());

        $repository = $resolver->resolve(...$fromRemoteNames);

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
