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

class WebSocket
{
    private $socket;
    private $host;
    private $port;
    
    public function __construct($host, $port)
    {
        $host = str_replace('ws://', 'tcp://', $host);
   
        $this->host = $host;
        $this->port = $port;
    }
    
    private function handShake($socket)
    {
        $handshake = 'GET /fuzzer HTTP/1.1';
        $handshake .= 'Upgrade: WebSocket';
        $handshake .= 'Connection: Upgrade';
        $handshake .= 'Host: ' . $this->host;
        $handshake .= 'Origin: ' .$this->host;
        $handshake .= 'WebSocket-Protocol: sample';
        
        $hanshake = $this->format_frames($handshake);
        $this->fwrite_stream($socket, $handshake);
    }
    
    private function fwrite_stream($fp, $string) 
    {
        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false) 
            {
                return $written;
            }
        }
        return $written;
    }
    
    public function send($msg)
    {
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        
        if (!$socket) 
        {
            return false;
        }
        else 
        {    
            $this->handShake($socket);
            $msg = $this->format_frames($msg);
            $this->fwrite_stream($socket, $msg);
        }
        
        fclose($socket);
        
        return true;
    }
    
    private function format_frames($msg="")
    { 
		//better way: http://stackoverflow.com/questions/8125507/how-can-i-send-and-receive-websocket-messages-on-the-server-side
		$ret = chr(129);
		$len = strlen($msg);

		if($len <= 125) 
        {
			$ret .= chr($len);
		} 
        else if ($len >= 126 && $len <= 65535) 
        {
			$ret .= chr(126);
			$ret .= chr( ( $len >> 8 ) & 255 );
			$ret .= chr( ( $len      ) & 255 );
		} 
        else 
        {
			$ret .= chr(127);
			$ret .= chr( ( $len >> 56 ) & 255 );
			$ret .= chr( ( $len >> 48 ) & 255 );
			$ret .= chr( ( $len >> 40 ) & 255 );
			$ret .= chr( ( $len >> 32 ) & 255 );
			$ret .= chr( ( $len >> 24 ) & 255 );
			$ret .= chr( ( $len >> 16 ) & 255 );
			$ret .= chr( ( $len >>  8 ) & 255 );
			$ret .= chr( ( $len       ) & 255 );
		}
        
        /* MASKS as zero*/
        $ret .= chr(0);
        $ret .= chr(0);
        $ret .= chr(0);
        $ret .= chr(0);
        
		$ret .= trim($msg);
        
		return $ret; //chr(129).chr(strlen($msg)&127).$msg; 
	}
}

class WebsocketFuzzer extends GeneralFuzzer 
{
    private $test_case;
    private $websocket;
    private $test_data = array();
    
    public function __construct($test)
    {
        $this->test_case = $test;
    }

    public function runTests()
    {   
        $this->websocket = new WebSocket($this->host, $this->port);
    
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
        
            $test_cases = count($this->test_data);
            $i = 1;
        
            foreach($this->test_data as $test)
            {
                echo 'Sending Websocket testcase ' . $i . ' / ' . $test_cases . ' (Input ' . $j . ' / ' . $input_count .')'. PHP_EOL;
                $i++;
                if($this->websocket->send($test) === false)
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
        
        echo 'Done with Websocket TEST set' . PHP_EOL;
    }
}

/* Ma 11.00 */
?>
