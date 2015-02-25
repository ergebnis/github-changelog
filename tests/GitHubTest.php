<?php

namespace Localheinz\ChangeLog\Test;

use Localheinz\ChangeLog;
use PHPUnit_Framework_TestCase;

class GitHubTest extends PHPUnit_Framework_TestCase
{
    public function testClassExists()
    {
        $this->assertTrue(class_exists(ChangeLog\GitHub::class));
    }
}
