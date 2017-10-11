<?php

// from https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/blob/develop/Test/phpcs2-bootstrap.php
$ds = DIRECTORY_SEPARATOR;
// Get the PHPCS dir from an environment variable.
$phpcsDir = getenv( 'PHPCS_DIR' );
if ( false !== $phpcsDir ) {
    $phpcsDir = realpath( $phpcsDir );
}
if ( false === $phpcsDir || ! is_dir( $phpcsDir . $ds . 'CodeSniffer' )
    || ! file_exists( $phpcsDir . $ds . 'tests' . $ds . 'AllTests.php' )
) {
    echo 'Uh oh... can\'t find tests/ in PHPCS. Are you sure you are using PHPCS 2.x ?
Make sure you set a `PHPCS_DIR` environment variable (possibly in your phpunit.xml file)
pointing to the PHPCS directory.';
    die( 1 );
} else {
    define( 'PHPCS_DIR', $phpcsDir );
}

require_once PHPCS_DIR . '/tests/AllTests.php';
require_once __DIR__ . '/Standards/AllSniffs.php';

