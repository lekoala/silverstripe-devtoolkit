# SilverStripe Devtoolkit

[![Build Status](https://travis-ci.com/lekoala/silverstripe-devtoolkit.svg?branch=master)](https://travis-ci.com/lekoala/silverstripe-devtoolkit/)
[![scrutinizer](https://scrutinizer-ci.com/g/lekoala/silverstripe-devtoolkit/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lekoala/silverstripe-devtoolkit/)
[![Code coverage](https://codecov.io/gh/lekoala/silverstripe-devtoolkit/branch/master/graph/badge.svg)](https://codecov.io/gh/lekoala/silverstripe-devtoolkit)

A common set of dev tools and helpers for Silverstripe

## Improved Debug view

The BetterDebugView class provided clickable links to trace. It is configured by default for VS Code but you can
configure your own ide placeholder with env var IDE_PLACEHOLDER.

## Useful tasks




## What's included?

- AdminBasicAuth : simple http basic auth for .env admin without any login/authenticate/member stuff
- Benchmark : simple way to log time to execute code
- BuildTaskTools : a trait to make your task tools easier to work with
- FastExportButton : to make exporting large table easier by executing raw queries
- FakeDataProvider : a lightweight alternative to faker to get some random stuff

## Compatibility

Tested with ^4.3

## Maintainer

LeKoala - thomas@lekoala.be
