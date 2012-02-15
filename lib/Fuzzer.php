<?php
/*

PHP fuzz testing framework
Copyright (C) 2012 Heikki Hellgren <heiccih@gmail.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

require_once 'Testcase.php';
require_once 'WebsocketFuzzer.php';
require_once 'PostGetFuzzer.php';

define("FUZZ_BUF_OVERFLOW", 0x0001);
define("FUZZ_DB_INJECTION", 0x0002);
define("FUZZ_RANDOM_DATA",  0x0004);
define("FUZZ_HTML_DATA",    0x0008);

class Fuzzer
{
    private $host;
    private $port;
    private $verbose_mode = false;
    private $test_file = "";
    private $output_file = "";
    private $tests;
    private $test_cases = array();
    private $fuzzTypes = 0;
    
    public function __construct($host, $port, $test_file) 
    {
        $this->host = $host;
        $this->port = $port;
        $this->test_file = $test_file;
    }

    public function setVerboseMode($new_mode)
    {
        $this->verbose_mode = $new_mode;
    }
    
    public function setOutputFile($file)
    {
        $this->output_file = $file;
    }
    
    public function setFuzzTypes($types)
    {
        $this->fuzzTypes |= $types;
    }
    
    private function loadTestData()
    {
        $doc = new DOMDocument();
        $doc->encoding = 'UTF-8';
        $doc->load($this->test_file); 

        $correct_inputs = $doc->getElementsByTagName('correct_inputs');
        
        foreach($correct_inputs as $test_case)
        {
            $new_testcase = new Testcase();
        
            if($test_case->hasAttribute('type'))
            {
                $new_testcase->setType($test_case->getAttribute('type'));
            }
            else
            {
                $new_testcas->setType("POST");
            }
            
            $inputs = $test_case->getElementsByTagName('input');
            
            foreach($inputs as $input)
            {
                $format = "STRING";
                
                if($input->hasAttribute('format'))
                {
                    $format = $input->getAttribute('format');
                }
            
                $new_testcase->addInput($input->nodeValue, $format);
            }
            
            $this->test_cases[] = $new_testcase;
        }
    }
    
    /* Runs actual test data */
    private function runTestData()
    {
        foreach($this->test_cases as $test_case)
        {
            switch($test_case->getType())
            {
                case "POST":
                    $fuzzer = new PostGetFuzzer($test_case, "POST");
                    $fuzzer->setFuzzTypes($this->fuzzTypes);
                    $fuzzer->setHost($this->host);
                    $fuzzer->setPort($this->port);
                    $fuzzer->runTests();
                    break;
                    
                case "GET":
                    $fuzzer = new PostGetFuzzer($test_case, "GET");
                    $fuzzer->setFuzzTypes($this->fuzzTypes);
                    $fuzzer->setHost($this->host);
                    $fuzzer->setPort($this->port);
                    $fuzzer->runTests();
                    break;
                    
                case "WEBSOCKET":
                    $fuzzer = new WebsocketFuzzer($test_case);
                    $fuzzer->setFuzzTypes($this->fuzzTypes);
                    $fuzzer->setHost($this->host);
                    $fuzzer->setPort($this->port);
                    $fuzzer->runTests();
                    break;
                    
                default:
                    echo 'UNDEFINED REQUEST: ' . $test_case->getType();
                    continue;
            }
        }
    }
    
    public function run()
    {
        $this->loadTestData(); 
        $this->runTestData();
    }

}

?>
