# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

For a full diff see [`0.7.0...master`][0.7.0...master].

## [`0.7.0`][0.7.0]

For a full diff see [`0.6.1...0.7.0`][0.6.1...0.7.0].

### Changed

* Renamed namespace `Localheinz\GitHub\ChangeLog` to `Ergebnis\GitHub\Changelog` after move to [@ergebnis] ([#336]), by [@localheinz]

  Run

  ```
  $ composer remove localheinz/github-changelog
  ```

  and

  ```
  $ composer require ergebnis/github-changelog
  ```

  to update.

  Run

  ```
  $ find . -type f -exec sed -i '.bak' 's/Localheinz\\GitHub\\ChangeLog/Ergebnis\\GitHub\\Changelog/g' {} \;
  ```

  to replace occurrences of `Localheinz\GitHub\ChangeLog` with `Ergebnis\GitHub\Changelog`.

  Run

  ```
  $ find -type f -name '*.bak' -delete
  ```

  to delete backup files created in the previous step.

### Fixed

* Dropped support for PHP 7.1 ([#314]), by [@localheinz]

[0.7.0]: https://github.com/ergebnis/github-changelog/tag/0.7.0

[0.6.1...0.7.0]: https://github.com/ergebnis/github-changelog/compare/0.6.1...0.7.0
[0.7.0...master]: https://github.com/ergebnis/github-changelog/compare/0.7.0...master

[#314]: https://github.com/ergebnis/github-changelog/pull/314
[#336]: https://github.com/ergebnis/github-changelog/pull/336

[@ergebnis]: https://github.com/ergebnis
[@localheinz]: https://github.com/localheinz
