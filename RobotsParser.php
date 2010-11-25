<?php

class RobotsParser
{
    const ROBOT_USER_AGENT_PATTERN = "/Mozilla/i";
    const PROCESS_STATE_USER_AGENT = 0;
    const PROCESS_STATE_USER_AGENT_MATCHED = 1;
    const PROCESS_STATE_DISALLOW = 2;
    
    function RobotsParser($robots_txt = "") {
        $this->state = RobotsParser::PROCESS_STATE_USER_AGENT;
        $this->disallowed_paths = array();
        $this->parse($robots_txt);
    }
    
    function disallowed_paths()
    {
        return $this->disallowed_paths;
    }
    
    protected function parse($robots_txt)
    {    
        if (!preg_match_all("/((User-agent|Disallow):\s*.*)\s*/i", $robots_txt, $matches))            
            throw new Exception("robots.txt was invalid.");
            
        $this->process($matches[1]);
    }
    
    protected function process($lines)
    {
        foreach($lines as $line) {
            preg_match("/(.+):\s*(.*)/", $line, $matches);
            $this->process_line($matches[1], $matches[2]);
        }
    }
    
    protected function process_line($key, $value)
    {
        switch ($this->state) {
            case RobotsParser::PROCESS_STATE_USER_AGENT:
                if (strtolower($key) == "user-agent" && $value == "*" ||
                    preg_match(RobotsParser::ROBOT_USER_AGENT_PATTERN, $value)) {
                        
                    $this->state = RobotsParser::PROCESS_STATE_USER_AGENT_MATCHED;
                }
                break;

            case RobotsParser::PROCESS_STATE_USER_AGENT_MATCHED:
                if (strtolower($key) == "disallow") {
                    $this->state = RobotsParser::PROCESS_STATE_DISALLOW;
                    $this->proces_line($key, $value);
                }
                break;
                    
            case RobotsParser::PROCESS_STATE_DISALLOW:
                if (strtolower($key) == "disallow") {
                    // The robots.txt spec states that an empty Disallow entry
                    // should undo any previously matched rules
                    if (empty($value)) {
                        $this->disallowed_paths = array();
                    } else {
                        array_push($this->disallowed_paths, $value);
                    }
                } else {
                    $this->state = RobotsParser::PROCESS_STATE_USER_AGENT;
                    $this->process_line($key, $value);
                }                    
                break;
        }
    }
}

?>