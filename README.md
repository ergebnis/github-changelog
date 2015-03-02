# README [![Build Status](https://travis-ci.org/localheinz/github-changelog.svg?branch=master)](https://travis-ci.org/localheinz/github-changelog) [![Code Climate](https://codeclimate.com/github/localheinz/github-changelog/badges/gpa.svg)](https://codeclimate.com/github/localheinz/github-changelog) [![Test Coverage](https://codeclimate.com/github/localheinz/github-changelog/badges/coverage.svg)](https://codeclimate.com/github/localheinz/github-changelog) [![Dependency Status](https://www.versioneye.com/user/projects/54f078634f31083e1b0004c7/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54f078634f31083e1b0004c7)

## Usage

### Global installation

Install globally.

```bash
$ composer global require localheinz/github-changelog:^0.2
```

Create your changelogs anywhere:

```bash
$ github-changelog pull-requests localheinz github-changelog 0.1.0 0.1.1
- Fix: Show PHP version requirement (#21)
```


## Local installation

Install locally (in your project):

```bash
$ composer require --sort-packages localheinz/github-changelog:^0.2
```

Create your changelog from within in your project:

```bash
$ vendor/bin/github-changelog pull-requests localheinz github-changelog 0.1.0 0.1.1
- Fix: Show PHP version requirement (#21)
```

Or retrieve pull-requests between references in your application, if you need it:

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
    'localheinz',
    'github-changelog',
    '0.1.0',
    '0.1.1'
);

array_walk($pullRequests, function (Entity\PullRequest $pullRequest) {
    echo sprintf(
        '- %s (#%s)' . PHP_EOL,
        $pullRequest->title(),
        $pullRequest->id()
    );
});

// - Fix: Show PHP version requirement (#21)
```
