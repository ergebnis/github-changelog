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

use Ergebnis\GitHub\Changelog\Util\Git;
use Ergebnis\GitHub\Changelog\Util\GitInterface;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Util\Git
 */
final class GitTest extends Framework\TestCase
{
    use Helper;

    /**
     * @var string[]
     */
    private $remoteUrls = [];

    protected function tearDown(): void
    {
        if (0 === \count($this->remoteUrls)) {
            return;
        }

        foreach ($this->remoteUrls as $remoteName => $remoteUrl) {
            \exec(
                \sprintf(
                    'git remote remove %s',
                    $remoteName
                ),
                $output,
                $returnValue
            );
        }

        $this->remoteUrls = [];
    }

    public function testImplementsGitInterface(): void
    {
        self::assertClassImplementsInterface(GitInterface::class, Git::class);
    }

    public function testRemoteUrlsReturnsRemoteUrls(): void
    {
        \exec(
            'git remote',
            $remoteNames,
            $returnValue
        );

        if (0 !== $returnValue) {
            self::markTestSkipped('Unable to determine existing git remotes.');
        }

        $faker = self::faker();

        while (3 > \count($this->remoteUrls)) {
            do {
                $remoteName = $faker->unique()->word;
            } while (\in_array($remoteName, $remoteNames, true));

            $remoteNames[] = $remoteName;

            $owner = $faker->unique()->word;
            $name = $faker->unique()->word;

            $this->remoteUrls[$remoteName] = \sprintf(
                'git@github.com:%s/%s.git',
                $owner,
                $name
            );
        }

        foreach ($this->remoteUrls as $remoteName => $remoteUrl) {
            \exec(
                \sprintf(
                    'git remote add %s %s',
                    $remoteName,
                    $remoteUrl
                ),
                $output,
                $returnValue
            );

            if (0 !== $returnValue) {
                self::markTestSkipped(\sprintf(
                    'Unable to add remote "%s" with URL "%s".',
                    $remoteName,
                    $remoteUrl
                ));
            }
        }

        $git = new Git();

        $remoteUrls = $git->remoteUrls();

        foreach ($this->remoteUrls as $remoteName => $remoteUrl) {
            self::assertArrayHasKey($remoteName, $remoteUrls);
            self::assertSame($remoteUrl, $remoteUrls[$remoteName]);
        }
    }
}
