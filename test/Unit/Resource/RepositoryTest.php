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

    /**
     * @var string[]
     */
    private $remoteName;

    protected function tearDown()
    {
        if (null === $this->remoteName) {
            return;
        }

        \exec(
            \sprintf(
                'git remote remove %s',
                $this->remoteName
            ),
            $output,
            $returnValue
        );

        if (0 !== $returnValue) {
            throw new \RuntimeException(\sprintf(
                'Unable to remove remote "%s".',
                $this->remoteName
            ));
        }

        unset($this->remoteName);
    }

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

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidRemoteUrl
     *
     * @param string $remoteUrl
     */
    public function testFromRemoteUrlRejectsInvalidRemoteUrl(string $remoteUrl)
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(\sprintf(
            'Unable to parse remote URL "%s".',
            $remoteUrl
        )));

        Resource\Repository::fromRemoteUrl($remoteUrl);
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerValidRemoteUrlOwnerAndName
     *
     * @param string $remoteUrl
     * @param string $owner
     * @param string $name
     */
    public function testFromRemoteUrlReturnsRepository(string $remoteUrl, string $owner, string $name)
    {
        $repository = Resource\Repository::fromRemoteUrl($remoteUrl);

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

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidRemoteName
     *
     * @param string $remoteName
     */
    public function testFromRemoteNameRejectsInvalidRemoteName(string $remoteName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Remote name "%s" appears to be invalid.',
            $remoteName
        ));

        Resource\Repository::fromRemoteName($remoteName);
    }

    public function testFromRemoteNameThrowsRuntimeExceptionIfRemoteDoesNotExist()
    {
        \exec(
            'git remote',
            $remoteNames,
            $returnValue
        );

        if (0 !== $returnValue) {
            $this->markTestSkipped('Unable to determine existing git remotes.');
        }

        $faker = $this->faker();

        do {
            $remoteName = $faker->unique()->word;
        } while (\in_array($remoteName, $remoteNames, true));

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Remote with name "%s" does not exist.',
            $remoteName
        ));

        Resource\Repository::fromRemoteName($remoteName);
    }

    public function testFromRemoteNameReturnsRepository()
    {
        \exec(
            'git remote',
            $remoteNames,
            $returnValue
        );

        if (0 !== $returnValue) {
            $this->markTestSkipped('Unable to determine existing git remotes.');
        }

        $faker = $this->faker();

        do {
            $this->remoteName = $faker->unique()->word;
        } while (\in_array($this->remoteName, $remoteNames, true));

        $owner = 'localheinz';
        $name = 'github-changelog';

        \exec(
            \sprintf(
                'git remote add %s git@github.com:%s/%s.git',
                $this->remoteName,
                $owner,
                $name
            ),
            $output,
            $returnValue
        );

        if (0 !== $returnValue) {
            $this->markTestSkipped(\sprintf(
                'Unable to add remote "%s".',
                $this->remoteName
            ));
        }

        $repository = Resource\Repository::fromRemoteName($this->remoteName);

        $this->assertInstanceOf(Resource\RepositoryInterface::class, $repository);
        $this->assertSame($owner, $repository->owner());
        $this->assertSame($name, $repository->name());
    }

    /**
     * @return string[]
     */
    private function invalidOwners(): array
    {
        $faker = $this->faker();

        return [
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
