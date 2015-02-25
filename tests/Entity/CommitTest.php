<?php

namespace Localheinz\ChangeLog\Test\Entity;

use Localheinz\ChangeLog\Entity;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorSetsShaAndMessage()
    {
        $sha = 'a35b6c9';
        $message = 'Enhancement: Reduce memory usage';

        $entity = new Entity\Commit(
            $sha,
            $message
        );

        $this->assertSame($sha, $entity->sha());
        $this->assertSame($message, $entity->message());
    }
}
