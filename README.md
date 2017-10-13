# Fresh Consulting WCAG 2.0 Linter #

A [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) custom rule (sniff) to detect obvious [WCAG 2.0](https://www.w3.org/TR/WCAG20/) accessibility violations.

There are many violations which are not caught but the ability to detect some is better than none.


### Installation

Requires PHPCS 2.x.

Clone this repository:

    git clone https://bitbucket.org/freshconsulting/freshconsulting-wcag-linter.git wcag
	
Add the repository path to the PHP_CodeSniffer configuration:

    phpcs --config-set installed_paths /path/to/wcag


### Use

Add the sniff to your custom ruleset:

    <rule ref="FreshConsulting.WCAG20.Violations"/>

Or run from the command line:

    phpcs --sniffs=FreshConsulting.WCAG20.Violations /path/to/code
	

### Testing

Run unit tests with `phpunit Test/AllTests` or `PHPCS_DIR=/path/to/PHP_CodeSniffer phpunit Test/AllTests`.

