<?php

namespace Localheinz\ChangeLog\Test\Service;

use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Service;
use Localheinz\ChangeLog\Test\Util\DataProviderTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use DataProviderTrait;

    public function testImplementsProvidesItemsInterface()
    {
        $service = new Service\Commit($this->commitRepository());

        $this->assertInstanceOf(Service\ProvidesItems::class, $service);
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';

        $endSha = $startSha;

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->never())
            ->method($this->anything())
        ;

        $service = new Service\Commit($commitRepository);

        $commits = $service->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn(null)
        ;

        $commitRepository
            ->expects($this->never())
            ->method('all')
        ;

        $service = new Service\Commit($commitRepository);

        $commits = $service->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn($this->commit())
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($endSha)
            )
            ->willReturn(null)
        ;

        $commitRepository
            ->expects($this->never())
            ->method('all')
        ;

        $service = new Service\Commit($commitRepository);

        $commits = $service->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $commits);
    }

    public function testItemsFetchesCommitsUsingShaFromStartCommit()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $startCommit = $this->commit();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn($startCommit)
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($endSha)
            )
            ->willReturn($this->commit())
        ;

        $commitRepository
            ->expects($this->once())
            ->method('all')
            ->with($this->equalTo($userName), $this->equalTo($repository), $this->equalTo([
                'sha' => $startCommit->sha(),
            ]))
        ;

        $service = new Service\Commit($commitRepository);

        $service->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );
    }

    public function testItemsReturnsArrayOfCommitsFromStartToEndExcludingStart()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $startCommit = $this->commit();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn($startCommit)
        ;

        $endCommit = $this->commit();

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($endSha)
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
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $startCommit->sha(),
                ])
            )
            ->willReturn($allCommits)
        ;

        $service = new Service\Commit($commitRepository);

        $commits = $service->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertCount(count($expectedCommits), $commits);

        foreach ($commits as $commit) {
            $expectedCommit = array_shift($expectedCommits);
            $this->assertSame($expectedCommit, $commit);
        }
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $startCommit = $this->commit();

        $commitRepository
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn($startCommit)
        ;

        $endCommit = $this->commit();

        $commitRepository
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($endSha)
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
                $this->equalTo($userName),
                $this->equalTo($repository),
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
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $lastCommitFromFirstBatch->sha(),
                ])
            )
            ->willReturn($secondBatch)
        ;

        $service = new Service\Commit($commitRepository);

        $commits = $service->items(
            $userName,
            $repository,
            $startSha,
            $endSha
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
