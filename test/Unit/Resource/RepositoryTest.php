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
    public function testConstructorRejectsInvalidOwner(string $owner)
    {
        $faker = $this->faker();

        $name = $faker->slug();

        $this->expectException(\InvalidArgumentException::class);

        new Resource\Repository(
            $owner,
            $name
        );
    }

    public function providerInvalidOwner(): \Generator
    {
        $faker = $this->faker();

        $values = [
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

        foreach ($values as $key => $value) {
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
    public function testConstructorRejectsInvalidName(string $name)
    {
        $faker = $this->faker();

        $owner = $faker->userName();

        $this->expectException(\InvalidArgumentException::class);

        new Resource\Repository(
            $owner,
            $name
        );
    }

    public function providerInvalidName(): \Generator
    {
        $values = [
            'blank' => '  ',
            'empty' => '',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider providerValidName
     *
     * @param string $owner
     */
    public function testConstructorSetsValues(string $owner)
    {
        $faker = $this->faker();

        $name = $faker->slug();

        $repository = new Resource\Repository(
            $owner,
            $name
        );

        $this->assertSame($owner, $repository->owner());
        $this->assertSame($name, $repository->name());
    }

    public function providerValidName(): \Generator
    {
        $faker = $this->faker();

        $values = [
            'digit' => $faker->randomDigitNotNull,
            'letter' => $faker->randomLetter,
            'word' => $faker->word,
            'word-with-numbers' => \sprintf(
                '%s%d',
                $faker->word,
                $faker->randomNumber()
            ),
            'words-separated-by-hyphen' => \implode(
                '-',
                $faker->words()
            ),
            'words-with-numbers-separated-by-hyphens' => \implode(
                '-',
                \array_merge($faker->words(), [
                    $faker->randomNumber(),
                    $faker->randomNumber(),
                ])
            ),
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }
}
