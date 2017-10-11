# github-changelog

[![Build Status](https://travis-ci.org/localheinz/github-changelog.svg?branch=master)](https://travis-ci.org/localheinz/github-changelog)
[![Code Climate](https://codeclimate.com/github/localheinz/github-changelog/badges/gpa.svg)](https://codeclimate.com/github/localheinz/github-changelog)
[![Test Coverage](https://codeclimate.com/github/localheinz/github-changelog/badges/coverage.svg)](https://codeclimate.com/github/localheinz/github-changelog/coverage)
[![Issue Count](https://codeclimate.com/github/localheinz/github-changelog/badges/issue_count.svg)](https://codeclimate.com/github/localheinz/github-changelog)
[![Latest Stable Version](https://poser.pugx.org/localheinz/github-changelog/v/stable)](https://packagist.org/packages/localheinz/github-changelog)
[![Total Downloads](https://poser.pugx.org/localheinz/github-changelog/downloads)](https://packagist.org/packages/localheinz/github-changelog)

Provides a script that generates a changelog based on titles of pull requests merged between specified references.

## Is this the right tool for me?

Probably not. There are a range of other tools that probably do a better job.

Take a look at 

* https://packagist.org/?q=changelog
* https://www.npmjs.com/search?q=changelog
* https://pypi.python.org/pypi?%3Aaction=search&term=changelog

Nonetheless, for me and my projects, it's the second best thing after *manually* 
keeping a changelog as suggested at http://keepachangelog.com.

## When will it work for me?

| My process                                   | Will this tool work for me? |
|----------------------------------------------|-----------------------------|
| I need elaborate changelogs                  | No                          |
| I push to `master`                           | No                          |
| ![Rebase and merge][rebase-and-merge-button] | No                          |
| ![Squash and merge][squash-and-merge-button] | No                          |
| ![Merge pull request][merge-button]          | **Yes**                     |

[rebase-and-merge-button]: https://user-images.githubusercontent.com/605483/30547612-18674f5c-9c90-11e7-8c0c-b300a8abb30c.png
[squash-and-merge-button]: https://user-images.githubusercontent.com/605483/30547621-1e1683fa-9c90-11e7-8233-fe41629d84d6.png
[merge-button]: https://user-images.githubusercontent.com/605483/30547611-18656e26-9c90-11e7-9dd3-c49aaa9bb4bf.png

## Why is this tool so limited?

All this tool does is this:

- it collects commits between references
- it matches commit messages against what is used by GitHub as a merge commit message
- it fetches the pull request title from the corresponding pull request
- it then uses all of the pull request titles to compile a list

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
$ composer require --dev localheinz/github-changelog
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
$ composer require localheinz/github-changelog
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

## Contributing

Please have a look at [`CONTRIBUTING.md`](.github/CONTRIBUTING.md).

## License

This package is licensed using the MIT License.
