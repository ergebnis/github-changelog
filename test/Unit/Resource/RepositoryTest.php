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
     * @dataProvider providerInvalidOwner
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

    public function providerInvalidOwner(): \Generator
    {
        foreach ($this->invalidOwners() as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider providerInvalidName
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

    public function providerInvalidName(): \Generator
    {
        foreach ($this->invalidNames() as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider providerValidOwnerAndName
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
     * @dataProvider providerInvalidString
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

    public function providerInvalidString(): \Generator
    {
        foreach ($this->invalidOwners() as $keyOne => $owner) {
            foreach ($this->invalidNames() as $keyTwo => $name) {
                $key = \sprintf(
                    '%s/%s',
                    $keyOne,
                    $keyTwo
                );

                yield $key => [
                    \sprintf(
                        '%s/%s',
                        $owner,
                        $name
                    ),
                ];
            }
        }

        foreach ($this->validOwners() as $key => $owner) {
            yield $key => [
                $owner,
            ];
        }

        foreach ($this->validNames()  as $key => $name) {
            yield $key => [
                $name,
            ];
        }
    }

    /**
     * @dataProvider providerValidOwnerAndName
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

    public function providerValidOwnerAndName(): \Generator
    {
        foreach ($this->validOwners() as $keyOne => $owner) {
            foreach ($this->validNames() as $keyTwo => $name) {
                $key = \sprintf(
                    '%s/%s',
                    $keyOne,
                    $keyTwo
                );

                yield $key => [
                    $owner,
                    $name,
                ];
            }
        }
    }

    /**
     * @return string[]
     */
    private function invalidOwners(): array
    {
        $faker = $this->faker();

        $values = [
            'blank' => '  ',
            'empty' => '',
            'starts-with-hyphen' => \sprintf(
                '-%s',
                $faker->word
            ),
            'ends-with-hyphen' => \sprintf(
                '%s-',
                $faker->word
            ),
            'has-multiple-successive-hyphens' => \sprintf(
                '%s--%s',
                $faker->word,
                $faker->word
            ),
            'has-underscores' => \sprintf(
                '%s_%s',
                $faker->word,
                $faker->word
            ),
            'has-special-characters' => \implode('', $faker->randomElements([
                '.',
                '_',
                ':',
                'ä',
                'ü',
                'ö',
                'ß',
                '🤓',
            ])),
        ];

        return $values;
    }

    /**
     * @return string[]
     */
    private function validOwners(): array
    {
        $faker = $this->faker();

        return [
            'digit' => $faker->randomDigitNotNull,
            'letter' => $faker->randomLetter,
            'word' => $faker->word,
            'word-with-numbers' => \sprintf(
                '%s%d',
                $faker->word,
                $faker->numberBetween(1)
            ),
            'words-separated-by-hyphen' => \implode(
                '-',
                $faker->words()
            ),
            'words-with-numbers-separated-by-hyphens' => \implode(
                '-',
                \array_merge($faker->words(), [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
        ];
    }

    /**
     * @return string[]
     */
    private function invalidNames(): array
    {
        return [
            'blank' => '  ',
            'empty' => '',
            'has-special-characters' => \implode('', $this->faker()->randomElements([
                '/',
                '\\',
                ':',
                'ä',
                'ü',
                'ö',
                'ß',
                '🤓',
            ])),
        ];
    }

    private function validNames(): array
    {
        $faker = $this->faker();

        return [
            'digit' => $faker->randomDigitNotNull,
            'letter' => $faker->randomLetter,
            'word' => $faker->word,
            'hyphen' => '-',
            'hyphens' => '---',
            'underscore' => '_',
            'underscores' => '___',
            'word-with-numbers' => \sprintf(
                '%s%d',
                $faker->word,
                $faker->numberBetween(1)
            ),
            'words-separated-by-hyphen' => \implode(
                '-',
                $faker->words()
            ),
            'words-separated-by-underscore' => \implode(
                '-',
                $faker->words()
            ),
            'words-with-numbers-separated-by-hyphens' => \implode(
                '-',
                \array_merge($faker->words(), [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
            'words-with-numbers-separated-by-underscores' => \implode(
                '_',
                \array_merge($faker->words(), [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
        ];
    }
}
