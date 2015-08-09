<?php

namespace Localheinz\GitHub\ChangeLog\Test\Entity;

use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testConstructorSetsShaAndMessage()
    {
        $faker = $this->faker();

        $sha = $faker->sha1;
        $message = $faker->sentence();

        $entity = new Entity\Commit(
            $sha,
            $message
        );

        $this->assertSame($sha, $entity->sha());
        $this->assertSame($message, $entity->message());
    }
}
