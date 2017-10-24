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

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Resource;

use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class RepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsRepositoryInterface()
    {
        $this->assertClassImplementsInterface(Resource\RepositoryInterface::class, Resource\Repository::class);
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidRepositoryOwner
     *
     * @param string $owner
     */
    public function testFromOwnerAndNameRejectsInvalidOwner(string $owner)
    {
        $faker = $this->faker();

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
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidRepositoryName
     *
     * @param string $name
     */
    public function testFromOwnerAndNameRejectsInvalidName(string $name)
    {
        $faker = $this->faker();

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
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerValidRepositoryOwnerAndName
     *
     * @param string $owner
     * @param string $name
     */
    public function testFromOwnerAndNameReturnsRepository(string $owner, string $name)
    {
        $repository = Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );

        $this->assertInstanceOf(Resource\RepositoryInterface::class, $repository);
        $this->assertSame($owner, $repository->owner());
        $this->assertSame($name, $repository->name());
    }

    public function testToStringReturnsStringRepresentation()
    {
        $faker = $this->faker();

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

        $this->assertSame($expected, $repository->__toString());
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidRepositoryString
     *
     * @param string $string
     */
    public function testFromStringRejectsInvalidStrings(string $string)
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'String "%s" does not appear to be a valid string.',
            $string
        ));

        Resource\Repository::fromString($string);
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerValidRepositoryOwnerAndName
     *
     * @param string $owner
     * @param string $name
     */
    public function testFromStringReturnsRepository(string $owner, string $name)
    {
        $string = \sprintf(
            '%s/%s',
            $owner,
            $name
        );

        $repository = Resource\Repository::fromString($string);

        $this->assertInstanceOf(Resource\RepositoryInterface::class, $repository);
        $this->assertSame($owner, $repository->owner());
        $this->assertSame($name, $repository->name());
    }
}
