<?php

class FreshConsulting_Tests_WCAG20_ViolationsUnitTest extends AbstractSniffUnitTest {


    private $expected_results = array(

        'no-php-invalid.inc' => array(
            1 => 1,
            7 => 2,
        ),

        'valid-simple.inc' => array(
            1 => 0,
        ),

        'valid-unclosed-a.inc' => array(
            1 => 0,
        ),

	);


    protected function getTestFiles( $testFileBase ) {
        $ds = DIRECTORY_SEPARATOR;
        $test_files = glob( dirname( $testFileBase ) . $ds . 'ViolationsUnitTests{' . $ds . ',' . $ds . '*' . $ds . '}*.inc', GLOB_BRACE );

        if ( ! empty( $test_files ) ) {
            return $test_files;
        }

        return array( $testFileBase . '.inc' );

    }


    public function getErrorList( $testFile = '' ) {

        if ( isset( $this->expected_results[ $testFile ] ) ) {
            return $this->expected_results[ $testFile ];
        }

        return array();
    }


    public function getWarningList() {
        return array();
    }

}

