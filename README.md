# README 

[![Build Status](https://travis-ci.org/localheinz/github-changelog.svg?branch=master)](https://travis-ci.org/localheinz/github-changelog) 
[![Code Climate](https://codeclimate.com/github/localheinz/github-changelog/badges/gpa.svg)](https://codeclimate.com/github/localheinz/github-changelog) 
[![Test Coverage](https://codeclimate.com/github/localheinz/github-changelog/badges/coverage.svg)](https://codeclimate.com/github/localheinz/github-changelog)

## CLI Tool

### Global installation

Install globally:

```bash
$ composer global require localheinz/github-changelog
```

Create your changelogs anywhere:

```bash
$ github-changelog generate localheinz github-changelog 0.1.1 0.1.2
```

Enjoy the changelog:

```
- Fix: Catch exceptions in command (#37)
- Fix: Request 250 instead of 30 commits (#38)
```

### Local installation

Install locally:

```bash
$ composer require --dev --sort-packages localheinz/github-changelog
```

Create your changelog from within in your project:

```bash
$ vendor/bin/github-changelog generate localheinz github-changelog ae63248 master
```

Enjoy the changelog:

```
- Enhancement: Create ChangeLog command (#31)
- Fix: Assert exit code is set to 0 (#32)
- Enhancement: Add console application (#33)
- Fix: Readme (#34)
- Fix: Autoloading for console script (#35)
- Fix: Version foo with rebasing and whatnot (#36)
- Fix: Catch exceptions in command (#37)
- Fix: Request 250 instead of 30 commits (#38)
```

## Userland Code

Install locally:

```bash
$ composer require --sort-packages localheinz/github-changelog
```

Retrieve pull requests between references in your application:

```php
<?php

require 'vendor/autoload.php';

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;

$client = new Client(new CachedHttpClient());
$client->authenticate(
    'your-token-here',
    Client::AUTH_HTTP_TOKEN
);

$repository = new Repository\PullRequestRepository(
    $client->pullRequests(),
    new Repository\CommitRepository($client->repositories()->commits())
);

/* @var Resource\RangeInterface $range */
$range = $repository->items(
    'localheinz',
    'github-changelog',
    '0.1.1',
    '0.1.2'
);

$pullRequests = $range->pullRequests();

array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) {
    echo sprintf(
        '- %s (#%s)' . PHP_EOL,
        $pullRequest->title(),
        $pullRequest->id()
    );
});

```

Enjoy the changelog:

```
- Fix: Catch exceptions in command (#37)
- Fix: Request 250 instead of 30 commits (#38)
```

## Hints

:bulb: You can use anything for a reference, e.g., a tag, a branch, a commit!

## License

This package is licensed using the MIT License.
