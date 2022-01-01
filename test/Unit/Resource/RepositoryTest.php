<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2022 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Resource;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Resource\Repository
 */
final class RepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsRepositoryInterface(): void
    {
        self::assertClassImplementsInterface(Resource\RepositoryInterface::class, Resource\Repository::class);
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidRepositoryOwner
     */
    public function testFromOwnerAndNameRejectsInvalidOwner(string $owner): void
    {
        $faker = self::faker();

        $name = $faker->slug();

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Owner "%s" does not appear to be a valid owner.',
            $owner
        ));

        Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidRepositoryName
     */
    public function testFromOwnerAndNameRejectsInvalidName(string $name): void
    {
        $faker = self::faker();

        $owner = $faker->slug();

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Name "%s" does not appear to be a valid name.',
            $name
        ));

        Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerValidRepositoryOwnerAndName
     */
    public function testFromOwnerAndNameReturnsRepository(string $owner, string $name): void
    {
        $repository = Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );

        self::assertSame($owner, $repository->owner());
        self::assertSame($name, $repository->name());
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidRemoteUrl
     */
    public function testFromRemoteUrlRejectsInvalidRemoteUrl(string $remoteUrl): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(\sprintf(
            'Unable to parse remote URL "%s".',
            $remoteUrl
        )));

        Resource\Repository::fromRemoteUrl($remoteUrl);
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerValidRemoteUrlOwnerAndName
     */
    public function testFromRemoteUrlReturnsRepository(string $remoteUrl, string $owner, string $name): void
    {
        $repository = Resource\Repository::fromRemoteUrl($remoteUrl);

        self::assertSame($owner, $repository->owner());
        self::assertSame($name, $repository->name());
    }

    public function testToStringReturnsStringRepresentation(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();

        $repository = Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );

        $expected = \sprintf(
            '%s/%s',
            $owner,
            $name
        );

        self::assertSame($expected, $repository->__toString());
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidRepositoryString
     */
    public function testFromStringRejectsInvalidStrings(string $string): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'String "%s" does not appear to be a valid string.',
            $string
        ));

        Resource\Repository::fromString($string);
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerValidRepositoryOwnerAndName
     */
    public function testFromStringReturnsRepository(string $owner, string $name): void
    {
        $string = \sprintf(
            '%s/%s',
            $owner,
            $name
        );

        $repository = Resource\Repository::fromString($string);

        self::assertSame($owner, $repository->owner());
        self::assertSame($name, $repository->name());
    }
}
