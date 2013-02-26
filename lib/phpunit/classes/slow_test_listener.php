<?php
class SlowTestListener implements PHPUnit_Framework_TestListener {
    private $start = 0.0;
    private $end = 0.0;
    private $limit = 2.0;

    public function startTest(PHPUnit_Framework_Test $test) {
          $this->start = microtime(true);
    }
    public function endTest(PHPUnit_Framework_Test $test, $time) {
        $this->end = microtime(true);
        $took = $this->end - $this->start;
        if($took > $this->limit ) {
            echo "\nName: ".$test->getName()." took ".$took . " second(s) (from: $this->start, to: $this->end)\n";
        }
    }
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) { }
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) { }
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time){ }
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) { }
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) { }
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) { }
}
