<?php

class AllTests extends PHP_CodeSniffer_AllTests {

    /**
     * Add all PHP_CodeSniffer test suites into a single test suite.
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $GLOBALS['PHP_CODESNIFFER_STANDARD_DIRS'] = array();

        // Use a special PHP_CodeSniffer test suite so that we can
        // unset our autoload function after the run.
        $suite = new PHP_CodeSniffer_TestSuite('PHP CodeSniffer');

        // EDIT: remove PHPCS built-in tests, add custom tests
        $suite->addTest(AllSniffs::suite());

        // Unregister this here because the PEAR tester loads
        // all package suites before running then, so our autoloader
        // will cause problems for the packages included after us.
        spl_autoload_unregister(array('PHP_CodeSniffer', 'autoload'));

        return $suite;

    }//end suite()


}//end class
