<?php

namespace Localheinz\GitHub\ChangeLog\Test\Entity;

use Localheinz\GitHub\ChangeLog\Entity;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorSetsIdAndTitle()
    {
        $id = '9000';
        $title = 'Enhancement: Reduce memory usage';

        $entity = new Entity\PullRequest(
            $id,
            $title
        );

        $this->assertSame($id, $entity->id());
        $this->assertSame($title, $entity->title());
    }
}
