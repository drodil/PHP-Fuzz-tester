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

require_once 'TestInput.php';

class Testcase
{
    private $type;
    private $fuzztimes = 10;
    private $test_inputs = array();
    
    public function __construct()
    {
    }
    
    public function setType($type)
    {
        $this->type = $type;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setFuzzTimes($times)
    {
        $this->fuzztimes = $times;
    }
    
    public function getFuzzTimes()
    {
        return $this->fuzztimes;
    }

    public function addInput($input, $format)
    {
        $inp = new TestInput($input, $format);
        $this->test_inputs[] = $inp;
    }
    
    public function getInputs()
    {
        return $this->test_inputs;
    }

}

?>
