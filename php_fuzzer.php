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

require_once 'lib/Fuzzer.php';

set_time_limit(0);
error_reporting(E_ERROR);
ini_set('default_charset', 'UTF-8');

function print_help()
{
print <<<OPTIONS
PHP FUZZER
----------------
Usage:

php php_fuzzer.php [[ OPTIONS ]] [target] 

By default php_fuzzer uses test_cases.xml for
test cases. Default port is 80. If no output file
specified, the output will come out of stdout.

Options:
-t  Test_list (.xml)
-p  Port number
-v  Verbose, show error messages
-o  Output file
-h  Print this help

Fuzzing options:
-f  Fuzz buffer overflow
-i  Fuzz SQL injections
-r  Fuzz random data
-w  Fuzz HTML data

OPTIONS;
}

$OPTIONS = getopt('t:p:o:wvrhfi');
$port = 80;
$test_file = "examples/test_cases.xml";
$verbose_mode = false;
$output_file = "";
$host = $argv[$argc-1];
$fuzz_random_data = false;
$fuzz_sql_injection = false;
$fuzz_buffer_overflow = false;
$fuzz_html_data = false;

if(($OPTIONS === false || count($OPTIONS) === 0) && !isset($argv[$argc-1]) )
{
    print_help();
    return;
}

foreach ($OPTIONS as $option => $value) 
{
    switch ($option) 
    {
        case 'h':
            print_help();
            return;
            
        case 'p':
            $port = $OPTIONS['p'];
            break;
            
        case 't':
            $test_file = $OPTIONS['t'];
            break;
            
        case 'v':
            $verbose_mode = true;
            break;
            
        case 'o':
            $output_file = $OPTIONS['o'];
            break;
            
        case 'f':
            $fuzz_buffer_overflow = true;
            break;
            
        case 'i':
            $fuzz_sql_injection = true;
            break;
            
        case 'r':
            $fuzz_random_data = true;
            break;
            
        case 'w':
            $fuzz_html_data = true;
            break;
            
    }
}
echo PHP_EOL . '--------------------------------------------------------' . PHP_EOL;
echo '|                   PHP FUZZER                         |' . PHP_EOL;
echo '--------------------------------------------------------' . PHP_EOL;
echo PHP_EOL . 'Initializing PHP fuzzer @ ' . $host . ':' . $port . PHP_EOL;
$t = 0;

if($fuzz_random_data === true) $t |= FUZZ_RANDOM_DATA;
if($fuzz_sql_injection === true) $t |= FUZZ_DB_INJECTION;
if($fuzz_buffer_overflow === true) $t |= FUZZ_BUF_OVERFLOW;
if($fuzz_html_data === true) $t |= FUZZ_HTML_DATA;

$fuzzer = new Fuzzer($host, $port, $test_file);
$fuzzer->setFuzzTypes($t);
$fuzzer->setVerboseMode($verbose_mode);
$fuzzer->setOutputFile($output_file);
$fuzzer->run();

?>
