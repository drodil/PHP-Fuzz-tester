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

require_once 'Fuzzer.php';

class GeneralFuzzer
{
    protected $fuzzTypes = 0;
    protected $host = "127.0.0.1";
    protected $port = "80";
    
    private $html_break_codes = array('<br>', '\'', '\0', '>\'', '<', '>', '<<', '>>', '";', '<script type="text/javascript">window.location = "http://www.google.com";</script>',
                                      '\';', '"""', '""', '"\"\"', '\n', '/n', '\nb', '\r\n', '/r/n', '<strong>', '</p>', '<%>', '</', '<\<\<>>', 
                                      '::', '%#"', '\'\'\'', '}', '{', '<body>', '<head>', ';<<<', ':D:D:D:D:D:D', ':>>>>>>', '<!--', '<?php', '?>', '-->', 'die(1);',
                                      'exit();', 'C:\\notes.txt%00', '%00', '../../../../../../../../etc/passwd%00', '$(exit(1))', '; exit(1);', 
                                      '> /etc/php.ini', '> /', '< /etc/php.ini', '|| die("HACKED");', '%01%02%03%04%05%06%07%08%09%10', '$page=.htaccess;', 
                                      'passthru("rm -rf *");', '"; print phpinfo();', '"; $_SESSION[\'user\'] = "hacked.";', '"; shell_exec("sudo shutdown -h now");',
                                      '&& shell_exec("shutdown -h now");', '&& shell_exec("rm -rf *");', '\'; exec("rm -rf *");', '|| exec("rm -rf *");', 
                                      '|| while(1) { }', '"; while(1){}', '\'; while(1) {}', 'while(1) {}', 'rm -rf *', '/etc/passwd', '/etc/php.ini', 
                                      'shutdown -h now', '../../', '../../etc/passwd\0', '/etc/passwd\0');
                                      
    private $sql_incjection_codes = array('\'; DROP TABLE users;', 'anything\' OR \'x\' = \'x', 'x\' AND email IS NULL; --', 'x\' AND id IS NULL; --', 'x\' OR 1=1',
                                          'x\'; INSERT INTO users (\'username\', \'password\') VALUES (\'hack_admin\', \'NULL\');', '23 OR 1=1', '\\\'\'; DROP TABLE users; --',
                                          '\'DROP users;--', '\'DROP users;#', 'admin\' --', 'admin\' #', 'user\' #', '\' OR 1=1--', '" or 1=1--', 'or 1=1--', '\' or \'a\'=\'a',
                                          '" or "a"="a', '\') or (\'a\'=\'a');
    
    public function setHost($host)
    {
        $this->host = $host;
    }
    
    public function setPort($port)
    {
        $this->port = $port;
    }
    
    private function genRandomString($length, $overflow) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz*\'\"\<àèðøàèðø¸°¨ ¡©±¹-éñùáéñù¢º²ºâê$€òü¾«»³¶®¦æþýõåäö×ßçï';
        if($overflow)
        {
            $characters = '0123456789';
        }
        
        $string = '';    

        for ($p = 0; $p < $length; $p++) 
        {
            $string .= $characters[mt_rand(0, strlen($characters)-1)];
        }

        return $string;
    }    

    public function setFuzzTypes($types)
    {
        $this->fuzzTypes = $types;
    }

    public function FuzzRAWData($input)
    {
        $output = array();
        
        echo 'Generating raw test data..' . PHP_EOL;
    
        if($this->fuzzTypes & FUZZ_RANDOM_DATA)
        {
            for($i = 0; $i < 100; $i++)
            {
                $output[] = $this->genRandomString($i*10, false);
            }
        }
        
        if($this->fuzzTypes & FUZZ_BUF_OVERFLOW)
        {
            for($i = 0; $i < 20; $i++)
            {
                $output[] = $this->genRandomString($i*1024, true);
            }
        }
        
        if($this->fuzzTypes & FUZZ_HTML_DATA)
        {
            for($i = 0; $i < count($this->html_break_codes)-1; $i++)
            {
                $output[] = $this->html_break_codes[$i];
            }
        }
        
        if($this->fuzzTypes & FUZZ_DB_INJECTION)
        {
            for($i = 0; $i < count($this->sql_incjection_codes)-1; $i++)
            {
                $output[] = $this->sql_incjection_codes[$i];
            }
        }
        
        return $output;
    }
    
    public function FuzzJSONData($input)
    {
        $array = json_decode($input, true);
        $output = array();
        
        echo 'Generating JSON test data..' . PHP_EOL;
        
        while ($content = current($array)) 
        {
            $key = key($array);
        
            if($this->fuzzTypes & FUZZ_RANDOM_DATA)
            {
                $out = $array;
                for($i = 0; $i < 100; $i++)
                {
                    $out[$key] = $this->genRandomString($i*10, false);
                    $output[] = json_encode($out);
                }

                for($i = 0; $i < 100; $i++)
                {
                    $out = array();
                    $out[$this->genRandomString($i*10)] = $array[$key];
                    $output[] = json_encode($out);
                }
            }
            
            if($this->fuzzTypes & FUZZ_BUF_OVERFLOW)
            {
                $out = $array;
                for($i = 1; $i < 20; $i++)
                {
                    $out[$key] = $this->genRandomString($i*1024, true);
                    $output[] = json_encode($out);
                }
            }
            
            if($this->fuzzTypes & FUZZ_HTML_DATA)
            {
                $out = $array;
                for($i = 0; $i < count($this->html_break_codes)-1; $i++)
                {
                    $out[$key] = $this->html_break_codes[$i];
                    $output[] = json_encode($out);
                }
                
                for($i = 0; $i < count($this->html_break_codes)-1; $i++)
                {
                    $out = array();
                    $out[$this->html_break_codes[$i]] = $array[$key];
                    $output[] = json_encode($out);
                }
            }
            
            if($this->fuzzTypes & FUZZ_DB_INJECTION)
            {
                $out = $array;
                
                for($i = 0; $i < count($this->sql_incjection_codes)-1; $i++)
                {
                    $out[$key] = $this->sql_incjection_codes[$i];
                    $output[] = json_encode($out);
                }
                
                for($i = 0; $i < count($this->sql_incjection_codes)-1; $i++)
                {
                    $out = array();
                    $out[$this->sql_incjection_codes[$i]] = $array[$key];
                    $output[] = json_encode($out);
                }
            }
            
            next($array);
        }
        
        return $output;
    }


}


?>
