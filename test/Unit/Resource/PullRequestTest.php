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
use PHPUnit\Framework;
use Refinery29\Test\Util\DataProvider;
use Refinery29\Test\Util\TestHelper;

final class PullRequestTest extends Framework\TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $this->assertFinal(Resource\PullRequest::class);
    }

    public function testImplementsPullRequestInterface()
    {
        $this->assertImplements(Resource\PullRequestInterface::class, Resource\PullRequest::class);
    }

    /**
     * @dataProvider providerInvalidId
     *
     * @param mixed $id
     */
    public function testConstructorRejectsInvalidId($id)
    {
        $this->expectException(\InvalidArgumentException::class);

        $title = $this->getFaker()->sentence();

        new Resource\PullRequest(
            $id,
            $title
        );
    }

    public function providerInvalidId(): \Generator
    {
        return $this->provideDataFrom(
            new DataProvider\InvalidIntegerish(),
            new DataProvider\Elements([
                0,
                -1 * $this->getFaker()->numberBetween(1),
            ])
        );
    }

    /**
     * @dataProvider \Refinery29\Test\Util\DataProvider\InvalidString::data()
     *
     * @param mixed $message
     */
    public function testConstructorRejectsInvalidTitle($message)
    {
        $this->expectException(\InvalidArgumentException::class);

        $sha = \sha1($this->getFaker()->sentence());

        new Resource\PullRequest(
            $sha,
            $message
        );
    }

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->getFaker();

        $id = $faker->numberBetween(1);
        $title = $faker->sentence();

        $pullRequest = new Resource\PullRequest(
            $id,
            $title
        );

        $this->assertSame($id, $pullRequest->number());
        $this->assertSame($title, $pullRequest->title());
    }
}
