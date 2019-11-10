# drupal-package-cleaner

[![Build Status](https://travis-ci.org/eiriksm/drupal-package-cleaner.svg?branch=master)](https://travis-ci.org/eiriksm/drupal-package-cleaner)

Makes your drupal updates use less memory and time.

## Installation

Just require it in your Drupal project like a normal package:

```
composer require eiriksm/drupal-package-cleaner
```

## Configuration

There is no configuration. Currently the only thing it does, is filter out packages we know we don't need if you are requiring a specific version of `drupal/core-recommended`. Like for example so:

```
composer require drupal/core-recommended:8.7.1 --update-with-dependencies
```
