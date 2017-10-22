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
     * @dataProvider providerInvalidNumber
     *
     * @param mixed $number
     */
    public function testConstructorRejectsInvalidNumber(int $number)
    {
        $this->expectException(\InvalidArgumentException::class);

        $title = $this->getFaker()->sentence();

        new Resource\PullRequest(
            $number,
            $title
        );
    }

    public function providerInvalidNumber(): \Generator
    {
        $values = [
            'int-negative' => -1 * $this->getFaker()->numberBetween(1),
            'int-zero' => 0,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->getFaker();

        $number = $faker->numberBetween(1);
        $title = $faker->sentence();

        $pullRequest = new Resource\PullRequest(
            $number,
            $title
        );

        $this->assertSame($number, $pullRequest->number());
        $this->assertSame($title, $pullRequest->title());
    }
}
