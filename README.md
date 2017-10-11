# Fresh Consulting WCAG 2.0 Linter #

A [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) custom sniff to detect obvious [WCAG 2.0](https://www.w3.org/TR/WCAG20/) violations.

There are many violations which are not caught but I figure the ability to detect some is better than none.

Currently the only rule provided is 
```
#!

FreshConsulting.WCAG20.Violations
```

Run unit tests with `phpunit Test/AllTests` or `PHPCS_DIR=/path/to/PHP_CodeSniffer phpunit Test/AllTests`.
