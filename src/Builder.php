<?php

namespace Localheinz\ChangeLog;

use BadMethodCallException;

class Builder
{
    public function fromPullRequests()
    {
        throw new BadMethodCallException('User needs to be specified');
    }
}
