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
require_once 'GeneralFuzzer.php';

class HTTPSocket
{
    private $socket;
    private $host;
    private $port;
    private $type;
    
    public function __construct($host, $port, $type = "POST")
    {
        $host = str_replace('http://', 'tcp://', $host);
        $host = str_replace('https://', 'tcp://', $host);
   
        $this->host = $host;
        $this->port = $port;
        
        $this->type = strtolower($type);
    }
    
    public function send($data, $optional_headers = null)
    {
        $params = array('http' => array(
                        'method' => $this->type,
                        'content' => $data
                        ));
                        
        if ($optional_headers!== null) 
        {
            $params['http']['header'] = $optional_headers;
        }
        
        $ctx = stream_context_create($params);
        
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        
        if (!$socket) 
        {
            return false;
        }
        
        fwrite($socket, $ctx);
        
        return true;
    } 

}

class PostGetFuzzer extends GeneralFuzzer 
{
    private $test_case;
    private $test_data = array();
    private $type;
    private $socket;
    
    public function __construct($test, $type = "POST")
    {
        $this->test_case = $test;
        $this->type = $type;
    }

    public function runTests()
    {  
        $this->socket = new HTTPSocket($this->host, $this->port, $type);
    
        $inputs = $this->test_case->getInputs();
        $tests_ongoing = true;
        
        $input_count = count($inputs); 
        $j = 1;
        
        foreach($inputs as $input)
        {
            switch($input->getFormat())
            {
                case "JSON":
                    $this->test_data = $this->FuzzJSONData($input->getInput());
                    break;
                    
                case "RAW":
                    $this->test_data = $this->FuzzRAWData($input->getInput());
                    break;
            }
            
            foreach($this->test_data as $test)
            {
                echo 'Sending '.$this->type.' testcase ' . $i . ' / ' . $test_cases . ' (Input ' . $j . ' / ' . $input_count .')'. PHP_EOL;
                $i++;
                if($this->socket->send($test) === false)
                {
                    echo 'Server broke down when sending: ' . $test . PHP_EOL;
                    echo 'Ending tests..' . PHP_EOL;
                    $tests_ongoing = false;
                    break;
                }
            }
            
            if($tests_ongoing === false) break;
            $j++;
        }
        
        echo 'Done with '.$this->type.' TEST set' . PHP_EOL;
    }
}


?>
