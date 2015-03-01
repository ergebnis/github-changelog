# README [![Build Status](https://travis-ci.org/localheinz/github-changelog.svg?branch=master)](https://travis-ci.org/localheinz/github-changelog) [![Code Climate](https://codeclimate.com/github/localheinz/github-changelog/badges/gpa.svg)](https://codeclimate.com/github/localheinz/github-changelog) [![Test Coverage](https://codeclimate.com/github/localheinz/github-changelog/badges/coverage.svg)](https://codeclimate.com/github/localheinz/github-changelog) [![Dependency Status](https://www.versioneye.com/user/projects/54f078634f31083e1b0004c7/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54f078634f31083e1b0004c7)

## Installation

```
$ composer require --sort-packages localheinz/github-changelog
```


## Example


```php
<?php

require 'vendor/autoload.php';

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Repository;

$client = new Client(new CachedHttpClient());
$client->authenticate(
    'your-token-here',
    Client::AUTH_HTTP_TOKEN
);

$repository = new Repository\PullRequest(
    $client->pullRequests(),
    new Repository\Commit($client->repositories()->commits())
);

$pullRequests = $repository->items(
    'zendframework',
    'modules.zendframework.com',
    '1.1.1',
    '1.1.2'
);

array_walk($pullRequests, function (Entity\PullRequest $pullRequest) {
    echo sprintf(
        '- %s (#%s)' . PHP_EOL,
        $pullRequest->title(),
        $pullRequest->id()
    );
});
```

```
$ php example.php
- "Show module on github" target blank (#417)
- Fix: Return null if nothing was found by Mapper\Module (#418)
- Fix: Method actually returns unregistered modules (#420)
```
