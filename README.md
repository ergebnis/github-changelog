# github-changelog

[![Integrate](https://github.com/ergebnis/github-changelog/workflows/Integrate/badge.svg?branch=master)](https://github.com/ergebnis/github-changelog/actions)
[![Prune](https://github.com/ergebnis/github-changelog/workflows/Prune/badge.svg?branch=master)](https://github.com/ergebnis/github-changelog/actions)
[![Release](https://github.com/ergebnis/github-changelog/workflows/Release/badge.svg?branch=master)](https://github.com/ergebnis/github-changelog/actions)
[![Renew](https://github.com/ergebnis/github-changelog/workflows/Renew/badge.svg?branch=master)](https://github.com/ergebnis/github-changelog/actions)

[![Code Coverage](https://codecov.io/gh/ergebnis/github-changelog/branch/master/graph/badge.svg)](https://codecov.io/gh/ergebnis/github-changelog)
[![Type Coverage](https://shepherd.dev/github/ergebnis/github-changelog/coverage.svg)](https://shepherd.dev/github/ergebnis/github-changelog)

[![Latest Stable Version](https://poser.pugx.org/ergebnis/github-changelog/v/stable)](https://packagist.org/packages/ergebnis/github-changelog)
[![Total Downloads](https://poser.pugx.org/ergebnis/github-changelog/downloads)](https://packagist.org/packages/ergebnis/github-changelog)

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
$ composer global require ergebnis/github-changelog
```

Create your changelogs from within a Git repository:

```bash
$ git clone git@github.com:ergebnis/github-changelog.git
$ cd github-changelog
$ github-changelog generate 0.1.1 0.1.2
```

Create your changelogs from anywhere, specifying the repository using the `--repository` option:

```bash
$ github-changelog generate --repository ergebnis/github-changelog 0.1.1 0.1.2
```

Enjoy the changelog:

```
- Fix: Catch exceptions in command (#37), by @localheinz
- Fix: Request 250 instead of 30 commits (#38), by @localheinz
```

### Local installation

Install locally:

```bash
$ composer require --dev ergebnis/github-changelog
```

Create your changelog from within in your project:

```bash
$ vendor/bin/github-changelog generate ergebnis/github-changelog ae63248 master
```

Enjoy the changelog:

```
- Enhancement: Create ChangeLog command (#31), by @localheinz
- Fix: Assert exit code is set to 0 (#32), by @localheinz
- Enhancement: Add console application (#33), by @localheinz
- Fix: Readme (#34), by @localheinz
- Fix: Autoloading for console script (#35), by @localheinz
- Fix: Version foo with rebasing and whatnot (#36), by @localheinz
- Fix: Catch exceptions in command (#37), by @localheinz
- Fix: Request 250 instead of 30 commits (#38), by @localheinz
```

## Userland Code

Install locally:

```bash
$ composer require ergebnis/github-changelog
```

Retrieve pull requests between references in your application:

```php
<?php

require 'vendor/autoload.php';

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Ergebnis\GitHub\Changelog\Repository;
use Ergebnis\GitHub\Changelog\Resource;

$client = new Client(new CachedHttpClient());
$client->authenticate(
    'your-token-here',
    Client::AUTH_HTTP_TOKEN
);

$pullRequestRepository = new Repository\PullRequestRepository(
    $client->pullRequests(),
    new Repository\CommitRepository($client->repositories()->commits())
);

/* @var Resource\RangeInterface $range */
$range = $repository->items(
    Resource\Repository::fromString('ergebnis/github-changelog'),
    '0.1.1',
    '0.1.2'
);

$pullRequests = $range->pullRequests();

array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) {
    echo sprintf(
        '- %s (#%d), submitted by @%s' . PHP_EOL,
        $pullRequest->title(),
        $pullRequest->number(),
        $pullRequest->author()->login(),
    );
});

```

Enjoy the changelog:

```
- Fix: Catch exceptions in command (#37), submitted by @localheinz
- Fix: Request 250 instead of 30 commits (#38), submitted by @localheinz
```

## Hints

:bulb: You can use anything for a reference, e.g., a tag, a branch, a commit!

## Changelog

Please have a look at [`CHANGELOG.md`](CHANGELOG.md).

## Contributing

Please have a look at [`CONTRIBUTING.md`](.github/CONTRIBUTING.md).

## Code of Conduct

Please have a look at [`CODE_OF_CONDUCT.md`](https://github.com/ergebnis/.github/blob/master/CODE_OF_CONDUCT.md).

## License

This package is licensed using the MIT License.

Please have a look at [`LICENSE.md`](LICENSE.md).
