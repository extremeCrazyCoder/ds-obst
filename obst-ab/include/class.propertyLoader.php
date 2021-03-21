<?php
if(isset($PROPERTY_LOADER)) {
    return;
}
$PROPERTY_LOADER = true;

class propertyLoader {
    private $vars;
    private $basePath;
    
    function __construct($basePath, $file) {
        $this->basePath = $basePath;
        $vars = [];
        $this->recursiveLoad($file);
    }
    
    private function recursiveLoad($file) {
        $contents = file_get_contents($this->basePath . $file);
        foreach(explode("\n", $contents) as $line) {
            $line = str_replace("\r", "", $line);
            if(strlen($line) < 2) continue;
            if($line[0] == "#") continue;
            if($line[0] == "[" && $line[strlen($line) - 1] == "]") {
                $this->recursiveLoad(substr($line, 1, strlen($line) - 2));
            }
            
            $parts = explode("=", $line, 2);
            if(count($parts) != 2) continue;
            $parts[1] = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
            }, $parts[1]);
            
            $this->vars[$parts[0]] = $parts[1];
        }
    }
    
    public function getProperty($prop) {
        return $this->vars[$prop];
    }
};
?>
