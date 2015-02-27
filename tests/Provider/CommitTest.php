<?php

namespace Localheinz\ChangeLog\Test\Provider;

use Localheinz\ChangeLog\Provider;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Test\Util\DataProviderTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use DataProviderTrait;

    public function testImplementsProvidesItemsInterface()
    {
        $provider = new Provider\Commit($this->commitRepository());

        $this->assertInstanceOf(Provider\ItemProvider::class, $provider);
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = $startReference;

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->never())
            ->method($this->anything())
        ;

        $provider = new Provider\Commit($commitRepository);

        $commits = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn(null)
        ;

        $commitRepository
            ->expects($this->never())
            ->method('all')
        ;

        $provider = new Provider\Commit($commitRepository);

        $commits = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->commit())
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn(null)
        ;

        $commitRepository
            ->expects($this->never())
            ->method('all')
        ;

        $provider = new Provider\Commit($commitRepository);

        $commits = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsFetchesCommitsUsingShaFromStartCommit()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $startCommit = $this->commit();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($startCommit)
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->commit())
        ;

        $commitRepository
            ->expects($this->once())
            ->method('all')
            ->with($this->equalTo($vendor), $this->equalTo($package), $this->equalTo([
                'sha' => $startCommit->sha(),
            ]))
        ;

        $provider = new Provider\Commit($commitRepository);

        $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );
    }

    public function testItemsReturnsArrayOfCommitsFromStartToEndExcludingStart()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $startCommit = $this->commit();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($startCommit)
        ;

        $endCommit = $this->commit();

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($endCommit)
        ;

        $countBetween = 13;
        $countAfter = 17;

        $allCommits = array_merge(
            [
                $startCommit,
            ],
            $this->commits($countBetween),
            [
                $endCommit,
            ],
            $this->commits($countAfter)
        );

        $expectedCommits = array_slice(
            $allCommits,
            1,
            $countBetween + 1
        );

        $commitRepository
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $startCommit->sha(),
                ])
            )
            ->willReturn($allCommits)
        ;

        $provider = new Provider\Commit($commitRepository);

        $commits = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertCount(count($expectedCommits), $commits);

        foreach ($commits as $commit) {
            $expectedCommit = array_shift($expectedCommits);
            $this->assertSame($expectedCommit, $commit);
        }
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $startCommit = $this->commit();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($startCommit)
        ;

        $endCommit = $this->commit();

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($endCommit)
        ;

        $firstBatch = array_merge(
            [
                $startCommit,
            ],
            $this->commits(50)
        );

        $lastCommitFromFirstBatch = end($firstBatch);

        $secondBatch = array_merge(
            [
                $lastCommitFromFirstBatch,
            ],
            $this->commits(20)
        );

        $expectedCommits = array_merge(
            array_slice(
                $firstBatch,
                1
            ),
            array_slice(
                $secondBatch,
                1
            )
        );

        $commitRepository
            ->expects($this->at(2))
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $startCommit->sha(),
                ])
            )
            ->willReturn($firstBatch)
        ;

        $commitRepository
            ->expects($this->at(3))
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $lastCommitFromFirstBatch->sha(),
                ])
            )
            ->willReturn($secondBatch)
        ;

        $provider = new Provider\Commit($commitRepository);

        $commits = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertCount(count($expectedCommits), $commits);

        foreach ($commits as $commit) {
            $expectedCommit = array_shift($expectedCommits);

            $this->assertSame($expectedCommit, $commit);
        }
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commitRepository()
    {
        return $this->getMockBuilder(Repository\Commit::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
