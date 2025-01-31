<?php
    // copyright by Robert Nitsch, 2006-2007
    
    /*
    DS_Bericht

    DS_Bericht is a PHP class which can parse reports of the german browsergame DieStämme. (DieStämme is currently being ported to other countries)
    This class has been written by Robert 'bmaker' Nitsch for the OBST
    */


define('DSBERICHT_VERSION','0.2.0.1');
define('DSBERICHT_DATE','09.04.2007 19:19');

if(isset($_GET['showsource']))
{
    echo '<?xml encoding="utf-8"?>';
    echo "\n";
    ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>class dsBericht SOURCE</title>
    </head>
    <body>
    <h1>class dsBericht SOURCE</h1>
    <?php
    echo '<p>Version: '.DSBERICHT_VERSION.'<br />Date: '.DSBERICHT_DATE.'</p>';
    echo '<p>&copy; copyright 2006-2007 by Robert Nitsch</p>';
    echo '<hr /><br />';
    show_source(__FILE__);
    echo '</body></html>';
    exit;
}

if(!defined('INC_CHECK_DSBERICHT'))
    die('hacking attempt');


if(!defined('DSBERICHT_DEBUG')) define('DSBERICHT_DEBUG',FALSE); // debugging can be activated from outside (before including this file)

include "include/class.propertyLoader.php";

class dsBericht {
    public static $BUILDING_NAMES = ["main", "barracks", "stable", "garage",
        "church", "watchtower", "snob", "smith", "place", "statue", "market", "wood",
        "stone", "iron", "farm", "storage", "hide", "wall"
    ];
    
    private ?propertyLoader $properties = null;
    private $data = null;
    
    public $report;
    public $units;
    
    public function __construct($units)
    {
        $this->reset();
        
        $this->units = array();
        $this->set_units($units);
    }

    private function reset()
    {
        $this->data = null;

        $this->report= [
            'time' => FALSE,
            'winner' => FALSE,
            'luck' => FALSE,
            'moral' => FALSE,
            'attacker' => FALSE,
            'defender' => FALSE,
            'troops' => FALSE,
            'wall' => FALSE,
            'catapult' => FALSE,
            'spied' => FALSE,
            'buildings' => FALSE,
            'troops_out' => FALSE,
            'booty' => FALSE,
            'mood' => FALSE,
        ];
    }

    function set_units($units)
    {
        if(is_array($units) && count($units) > 0)
        {
            $this->units = $units;
        }
        else
            trigger_error('ERROR: invalid argument $units', E_USER_ERROR);
    }

    function &getReport()
    {
        return $this->report;
    }

    // builds an INSERT INTO query automatically
    function buildSQL($table, $extra_columns=false)
    {
        if(!$this->data) return '';
        
        // alle Daten zunächst in einem Array ablegen
        $data = $this->buildAssoc();
        if($extra_columns !== false)
            $data = array_merge($extra_columns, $data);
        
        $keys = '';
        $values = '';
        foreach($data as $key => $value)
        {
            //ignore everything that the db does not support
            if(strpos($key, "milita") !== false) continue;
            if(strpos($key, "watchtower") !== false) continue;
            if(strpos($key, "church") !== false) continue;
            $keys .= '`'.$key.'`';
            $keys .= ',';
            
            if(!is_numeric($value))
                $values .= "'$value', ";
            else
                $values .= "$value, ";
            $values .= "\n";
        }
        
        $values = trim($values);
        $values = trim($values, ",");
        $keys = trim($keys);
        $keys = trim($keys, ",");
        
        return 'INSERT INTO '.$table.' ('.$keys.') VALUES ('.$values.')';
    }
    
    // builds an associative array containing all data of the report
    function buildAssoc()
    {
        $assoc = array(
            /* general data */
            'time'   => ($this->report['time'] ? $this->report['time'] : 0),
            'winner' => ($this->report['winner'] ? $this->report['winner'] : 1),
            'luck'   => ($this->report['luck'] ? $this->report['luck'] : 0.0),
            'moral'  => ($this->report['moral'] ? $this->report['moral'] : 0),
            /* attacker/defender data */
            'attacker_nick'      => (isset($this->report['attacker']['nick']) ? trim($this->report['attacker']['nick']) : 'unknown'),
            'attacker_village'   => (isset($this->report['attacker']['village']) ? trim($this->report['attacker']['village']) : 'unknown'),
            'attacker_coords'    => (isset($this->report['attacker']['coords']) ? trim($this->report['attacker']['coords']) : 'x|y'),
            'attacker_continent' => (isset($this->report['attacker']['continent']) ? trim($this->report['attacker']['continent']) : -1),
            'defender_nick'      => (isset($this->report['defender']['nick']) ? trim($this->report['defender']['nick']) : 'unknown'),
            'defender_village'   => (isset($this->report['defender']['village']) ? trim($this->report['defender']['village']) : 'unknown'),
            'defender_coords'    => (isset($this->report['defender']['coords']) ? trim($this->report['defender']['coords']) : 'x|y'),
            'defender_continent' => (isset($this->report['defender']['continent']) ? trim($this->report['defender']['continent']) : -1),
            /* troops */
            'troops'         => (is_array($this->report['troops']) ? 1 : 0),
            /* spied troops out */
            'spied_troops_out' => ((isset($this->report['spied_troops_out']) && is_array($this->report['spied_troops_out'])) ? 1 : 0),
            /* conquer troops out */
            'troops_out' => (is_array($this->report['troops_out']) ? 1 : 0),
            /* wall damage */
            'wall' => ($this->report['wall'] ? 1 : 0),
            'wall_before'     => (isset($this->report['wall']['before']) ? $this->report['wall']['before'] : 0),
            'wall_after'     => (isset($this->report['wall']['after']) ? $this->report['wall']['after'] : 0),
            /* catapult damage */
            'catapult' => ($this->report['catapult'] ? 1 : 0),
            'catapult_before' => (isset($this->report['catapult']['before']) ? $this->report['catapult']['before'] : 0),
            'catapult_after' => (isset($this->report['catapult']['after']) ? $this->report['catapult']['after'] : 0),
            'catapult_building' => (isset($this->report['catapult']['building']) ? $this->report['catapult']['building'] : ''),
            /* spied resources */
            'spied' => ($this->report['spied'] ? 1 : 0),
            'spied_wood' => (isset($this->report['spied']['wood']) ? intval(str_replace('.','',$this->report['spied']['wood'])) : 0),
            'spied_loam' => (isset($this->report['spied']['loam']) ? intval(str_replace('.','',$this->report['spied']['loam'])) : 0),
            'spied_iron' => (isset($this->report['spied']['iron']) ? intval(str_replace('.','',$this->report['spied']['iron'])) : 0),
            /* buildings */
            'buildings' => (is_array($this->report['buildings']) ? 1 : 0),
            'buildings_main' => (isset($this->report['buildings']['main']) ? $this->report['buildings']['main'] : 0),
            'buildings_barracks' => (isset($this->report['buildings']['barracks']) ? $this->report['buildings']['barracks'] : 0),
            'buildings_stable' => (isset($this->report['buildings']['stable']) ? $this->report['buildings']['stable'] : 0),
            'buildings_garage' => (isset($this->report['buildings']['garage']) ? $this->report['buildings']['garage'] : 0),
            'buildings_snob' => (isset($this->report['buildings']['snob']) ? $this->report['buildings']['snob'] : 0),
            'buildings_smith' => (isset($this->report['buildings']['smith']) ? $this->report['buildings']['smith'] : 0),
            'buildings_place' => (isset($this->report['buildings']['place']) ? $this->report['buildings']['place'] : 0),
            'buildings_statue' => (isset($this->report['buildings']['statue']) ? $this->report['buildings']['statue'] : 0),
            'buildings_market' => (isset($this->report['buildings']['market']) ? $this->report['buildings']['market'] : 0),
            'buildings_wood' => (isset($this->report['buildings']['wood']) ? $this->report['buildings']['wood'] : 0),
            'buildings_stone' => (isset($this->report['buildings']['stone']) ? $this->report['buildings']['stone'] : 0),
            'buildings_iron' => (isset($this->report['buildings']['iron']) ? $this->report['buildings']['iron'] : 0),
            'buildings_farm' => (isset($this->report['buildings']['farm']) ? $this->report['buildings']['farm'] : 0),
            'buildings_storage' => (isset($this->report['buildings']['storage']) ? $this->report['buildings']['storage'] : 0),
            'buildings_hide' => (isset($this->report['buildings']['hide']) ? $this->report['buildings']['hide'] : 0),
            'buildings_wall' => (isset($this->report['buildings']['wall']) ? $this->report['buildings']['wall'] : 0),
            /* booty */
            'booty' => ($this->report['booty'] ? 1 : 0),
            'booty_wood' => (isset($this->report['booty']['wood']) ? intval(str_replace('.','',$this->report['booty']['wood'])) : 0),
            'booty_loam' => (isset($this->report['booty']['loam']) ? intval(str_replace('.','',$this->report['booty']['loam'])) : 0),
            'booty_iron' => (isset($this->report['booty']['iron']) ? intval(str_replace('.','',$this->report['booty']['iron'])) : 0),
            'booty_all' => (isset($this->report['booty']['all']) ? intval(str_replace('.','',$this->report['booty']['all'])) : 0),
            'booty_max' => (isset($this->report['booty']['max']) ? intval(str_replace('.','',$this->report['booty']['max'])) : 0),
            /* mood */
            'mood' => ($this->report['mood'] ? 1 : 0),
            'mood_before' => (isset($this->report['mood']['before']) ? $this->report['mood']['before'] : 0),
            'mood_after' => (isset($this->report['mood']['after']) ? $this->report['mood']['after'] : 0),
            'spied_troops_village' => (isset($this->report['spied_troops_village'][1]) ? $this->report['spied_troops_village'][1] : 0),
            'no_information' => 0,
            'dot' => $this->report['dot'] ? $this->report['dot'] : 'grey'
        );
        
        foreach($this->units as $unit)
        {
            $assoc['troops_att_'.$unit->iname] = isset($this->report['troops']['att_'.$unit->iname]) ? $this->report['troops']['att_'.$unit->iname] : 0;
            $assoc['troops_attl_'.$unit->iname] = isset($this->report['troops']['attl_'.$unit->iname]) ? $this->report['troops']['attl_'.$unit->iname] : 0;
            $assoc['troops_def_'.$unit->iname] = isset($this->report['troops']['def_'.$unit->iname]) ? $this->report['troops']['def_'.$unit->iname] : 0;
            $assoc['troops_defl_'.$unit->iname] = isset($this->report['troops']['defl_'.$unit->iname]) ? $this->report['troops']['defl_'.$unit->iname] : 0;
        }
        foreach($this->units as $unit)
        {
            $assoc['spied_troops_out_'.$unit->iname] = isset($this->report['spied_troops_out'][$unit->iname]) ? $this->report['spied_troops_out'][$unit->iname] : 0;
        }
        foreach($this->units as $unit)
        {
            $assoc['troops_out_'.$unit->iname] = isset($this->report['troops_out'][$unit->iname]) ? $this->report['troops_out'][$unit->iname] : 0;
        }
        
        return $assoc;
    }
    
    public function parse($data, $server)
    {
        $this->properties = new propertyLoader("include/ParserLang/", "{$server}.parserprop");
        $this->data = $data;

        $this->report['moral'] = $this->parse_moral();
        $this->report['luck'] = $this->parse_luck();
        $this->report['attacker'] = $this->parse_attacker();
        $this->report['defender'] = $this->parse_defender();
        $this->report['winner'] = $this->parse_winner();
        $this->report['time'] = $this->parse_time();
        $this->report['troops'] = $this->parse_troops();
        $this->report['troops_out'] = $this->parse_troops_out();
        $this->report['spied_troops_out'] = $this->parse_spied_troops();
        $this->report['booty'] = $this->parse_booty();
        $this->report['spied'] = $this->parse_spied();
        $this->report['buildings'] = $this->parse_buildings();
        $this->report['wall'] = $this->parse_wall();
        $this->report['catapult'] = $this->parse_catapult();
        $this->report['mood'] = $this->parse_mood();
        $this->report['spied_troops_village'] = $this->parse_spied_troops_village();
        $this->report['dot'] = $this->parse_dot();
        
        if(DSBERICHT_DEBUG)
        {
            echo "\n\n";
            echo '<span style="font-weight: bold;">';
            print_r($this->report);
            echo '</span>';
            echo '<hr /><br />And this is the SQL VALUES part:<br />';
            echo $this->buildSQL('pseudotable');
            echo '<hr /><br />And this one is the associative array generated for the sql statement:<br />';
            print_r($this->buildAssoc());
        }

        $error = false;
        // check whether all needed data has been parsed correctly. otherwise => error!
        if(!is_array($this->report['troops']))
            $error = true;
        if($this->report['time'] === false)
            $error = true;
        if($this->report['winner'] === false)
            $error = true;
        if($this->report['luck'] === false)
            $error = true;

        if($error and DSBERICHT_DEBUG)
            echo "\nAn error occured: not all needed data could be parsed!\n";

        // report successfully parsed ...
        return $error;
    }
    
    // #############
    // PARSE FUNCTIONS ... each function parses ONE specific part of the report.
    // #############
    
    private function parse_moral() {
        $matches = $this->match($this->gProp("report.moral") . ":\\s+([0-9]+)");
        if($matches === false) return false;
        return intval($matches[1]);
    }
    
    private function parse_luck() {
        $matches = $this->match($this->gProp("report.att.luck") . ".*\\s+([\\-0-9]*[0-9]+\\.[0-9]+)%\\s");
        if($matches === false) return false;
        return floatval($matches[1]);
    }
    
    private function parse_attacker() {
        $matches = $this->match($this->gProp("report.village.2") . ":\\s+(.*?)\\s+(\\(\d\d\d\\|\d\d\d\\)) K(\d\d)\s*\n");
        if($matches === false) return false;
        
        $attacker = [
            'village' => $matches[1],
            'coords' => $matches[2],
            'continent' => $matches[3],
        ];
        
        $matches = $this->match($this->gProp("report.att.player") . ":\\s+(.*?)\\s*\n");
        if($matches === false) return false;
        $attacker['nick'] = $matches[1];
        return $attacker;
    }
    
    private function parse_defender() {
        $matches = $this->match($this->gProp("report.village.3") . ":\\s+(.*?)\\s+(\\(\d\d\d\\|\d\d\d\\)) K(\d\d)\s*\n");
        if($matches === false) return false;
        
        $defender = [
            'village' => $matches[1],
            'coords' => $matches[2],
            'continent' => $matches[3],
        ];
        
        $matches = $this->match($this->gProp("report.defender.player") . ":\\s+(.*?)\\s*\n");
        if($matches === false) return false;
        $defender['nick'] = $matches[1];
        return $defender;
    }
    
    private function parse_winner() {
        $escapedAtt = preg_quote($this->report['attacker']['nick']);
        $escapedDef = preg_quote($this->report['defender']['nick']);
        
        $matches = $this->match("($escapedAtt|$escapedDef) " . $this->gProp("report.has.won"));
        if($matches !== false) {
            return ($matches[1] == $this->report['attacker']['nick']) ? 1 : 2;
        }
        
        $matches = $this->match("$escapedAtt.* $escapedDef " . $this->gProp("report.spy"));
        if($matches !== false) {
            return 1;
        }
        return false;
    }
    
    private function parse_time() {
        $escapedAtt = preg_quote($this->report['attacker']['nick']);
        $escapedDef = preg_quote($this->report['defender']['nick']);
        $matches = $this->match($this->gProp("report.fight.time") . "(.*?)($escapedAtt|$escapedDef)");
        if($matches === false) return false;
        
        $time = str_replace(" :", ":", trim($matches[1]));
        if($time[strlen($time) - 4] == ":") {
            //millis world
            $time = substr($time, 0, strlen($time) - 4);
        }
        
        $parsed = date_parse_from_format($this->javaToPHPTimeFormat("report.date.format"), $time);
        $parsedTimestamp = mktime(
            $parsed['hour'], 
            $parsed['minute'], 
            $parsed['second'], 
            $parsed['month'], 
            $parsed['day'], 
            $parsed['year']
        );
        
        return $parsedTimestamp;
    }
    
    private function parse_troops() {
        $pattern = $this->getTroopsPattern(true, false);
        $att = $this->match($this->gProp("report.village.2") . ".*?\n.*?" . $this->gProp("report.num") . ":" . $pattern);
        $attL = $this->match($this->gProp("report.village.2") . ".*?\n.*?" . $this->gProp("report.loss") . ":" . $pattern);
        
        $pattern = $this->getTroopsPattern(true, true);
        $def = $this->match($this->gProp("report.village.3") . ".*?\n.*?" . $this->gProp("report.num") . ":" . $pattern);
        $defL = $this->match($this->gProp("report.village.3") . ".*?\n.*?" . $this->gProp("report.loss") . ":" . $pattern);
        
        // make an associative array
        $countOff = 1;
        $countDeff = 1;
        $troops = array();
        foreach($this->units as $unit)
        {
            if($unit->iname != 'milita') {
                if($att === false) {
                    $troops['att_'.$unit->iname] = 0;
                } else {
                    $troops['att_'.$unit->iname] =   $att[$countOff];
                }
                if($attL === false) {
                    $troops['attl_'.$unit->iname] =  0;
                } else {
                    $troops['attl_'.$unit->iname] =  $attL[$countOff];
                }
                $countOff++;
            }
            if($def === false) {
                $troops['def_'.$unit->iname] =  0;
            } else {
                $troops['def_'.$unit->iname] =  $def[$countDeff];
            }
            if($defL === false) {
                $troops['defl_'.$unit->iname] = 0;
            } else {
                $troops['defl_'.$unit->iname] = $defL[$countDeff];
            }
            $countDeff++;
        }
        
        return $troops;
    }
    
    //only troops that were on the way during conquer
    private function parse_troops_out() {
        $pattern = $this->getTroopsPattern(false, false);
        $out = $this->match($this->gProp("report.ontheway") . "\\n?\\s?" . $pattern);
        if($out === false) {
            return false;
        }
        
        // make an associative array
        $count = 1;
        $troops_out = array();
        foreach($this->units as $unit)
        {
            if($unit->iname != 'milita') {
                $troops_out[$unit->iname] = $out[$count];
                $count++;
            }
        }
        
        return $troops_out;
    }
    
    private function parse_booty() {
        $this->data = str_replace(" . ", ".", $this->data);
        
        $match = $this->match($this->gProp("report.haul") . ":\\s+([\\.0-9]*)\\s?([\\.0-9]*)\\s?([\\.0-9]*)\\s([\\.0-9]+)\\/([\\.0-9]+)");
        if($match === false) {
            return [
                'wood' => 0,
                'loam' => 0,
                'iron' => 0,
                'all' => 0,
                'max' => 0,
            ];
        }
        
        return [
            'wood' => str_replace(".", "", $match[1]),
            'loam' => str_replace(".", "", $match[2]),
            'iron' => str_replace(".", "", $match[3]),
            'all' => str_replace(".", "", $match[4]),
            'max' => str_replace(".", "", $match[5]),
        ];
    }
    
    private function parse_spied() {
        $match = $this->match($this->gProp("report.spy.res") . ":\\s+([\\.0-9]*)\\s?([\\.0-9]*)\\s?([\\.0-9]*)");
        if($match === false) {
            return false;
        }
        
        return [
            'wood' => str_replace(".", "", $match[1]),
            'loam' => str_replace(".", "", $match[2]),
            'iron' => str_replace(".", "", $match[3]),
        ];
    }
    
    private function parse_buildings() {
        $any = false;
        $retArr = [];
        foreach(static::$BUILDING_NAMES as $buildName) {
            $match = $this->match($this->gProp("report.buildings.$buildName") . "\\s+([0-9]+)");
            if($match !== false) {
                $any = true;
                $retArr[$buildName] = intval($match[1]);
            } else {
                $retArr[$buildName] = 0;
            }
        }
        
        $match = $this->match($this->gProp("report.buildings.first.church") . "\\s+([0-9]+)");
        if($match !== false) {
            $any = true;
            $retArr['church'] = intval($match[1]);
        }
        
        if(!$any) return false;
        return $retArr;
    }
    
    private function parse_wall() {
        $matches = $this->match($this->gProp("report.damage.ram") . ":\\s+" .
                $this->gProp("report.damage.wall") . "\\s+([0-9]+)\\s+" .
                $this->gProp("report.damage.to") . "\\s+([0-9]+)");
        if($matches === false) return false;
        return [
            'before' => intval($matches[1]),
            'after' => intval($matches[2]),
        ];
    }
    
    private function parse_catapult() {
        $matches = $this->match($this->gProp("report.damage.kata") . ":\\s+(.*)\\s+" .
                $this->gProp("report.damage.level") . "\\s+([0-9]+)\\s+" .
                $this->gProp("report.damage.to") . "\\s+([0-9]+)");
        if($matches === false) return false;
        return [
            'building' => $matches[1],
            'before' => intval($matches[2]),
            'after' => intval($matches[3]),
        ];
    }
    
    private function parse_mood() {
        $matches = $this->match($this->gProp("report.acceptance.1") . ":\\s+" .
                $this->gProp("report.acceptance.2") . "\\s+([0-9]+)\\s+" .
                $this->gProp("report.acceptance.3") . "\\s+(.*?)\\s");
        if($matches === false) return false;
        return [
            'before' => intval($matches[1]),
            'after' => intval($matches[2]),
        ];
    }
    
    private function parse_spied_troops() {
        $pattern = $this->getTroopsPattern(false, false);
        $out = $this->match($this->gProp("report.ontheway_spy") . "\\n?\\s?" . $pattern);
        if($out === false) {
            return false;
        }
        
        // make an associative array
        $count = 1;
        $troops_out = array();
        foreach($this->units as $unit)
        {
            if($unit->iname != 'milita') {
                $troops_out[$unit->iname] = $out[$count];
                $count++;
            }
        }
        
        return $troops_out;
    }
    
    //dummy function return value cannot be used...
    private function parse_spied_troops_village() {
        return false;
        
        /*
        $pattern = $this->getTroopsPattern(false, true);
        echo $this->data . "\n";
        $match = $this->match($this->gProp("report.outside") . "(\\s+(.*?)\\s\\((\d+\\|\d+)\\)\\sK(\d\d)\\s" . $pattern . ")+");
        if($match === false) {
            return false;
        }
        
        $src = str_replace($this->gProp("report.outside"), "", $match[0]);
        $out = $this->matchAll("\\s+(.*?)\\s\\((\d+\\|\d+)\\)\\sK(\d\d)\\s" . $pattern, $src);
        if($out === false) {
            return false;
        }
        
        // make an associative array
        $villages_out = [];
        for($i = 0; $i < count($out[0]); $i++) {
            $count = 4;
            $vil_out = [
                'village' => $out[1][$i],
                'coords' => $out[2][$i],
                'continent' => $out[3][$i],
            ];
            foreach($this->units as $unit)
            {
                if($unit->iname != 'milita') {
                    $vil_out[$unit->iname] = $out[$count][$i];
                    $count++;
                }
            }
            $villages_out[] = $vil_out;
        }
        
        return $villages_out;
         */
    }
    private function parse_dot() {
        $lostEverything = true;
        $lostEverythingExceptSpy = true;
        $lostNothing = true;
        foreach($this->units as $unit)
        {
            if($unit->iname != 'milita') {
                if($this->report['troops']["att_{$unit->iname}"] !=
                        $this->report['troops']["attl_{$unit->iname}"]) {
                    if($unit->iname != 'spy') {
                        $lostEverythingExceptSpy = false;
                    }
                    $lostEverything = false;
                }
                
                if($this->report['troops']["attl_{$unit->iname}"] != 0) {
                    $lostNothing = false;
                }
            }
        }
        if ($lostEverything) return 'red';
        if ($lostEverythingExceptSpy) return 'blue';
        if ($lostNothing) return 'green';
        return 'yellow';
    }
    
    
    private function gProp($property) {
        return $this->properties->getProperty($property);
    }
    
    private function match($pattern, $data=null) {
        $data = $data ?? $this->data;
        $matches = []; 
        
        $ret = preg_match("/" . $pattern . "/s", $data, $matches);
        
        if($ret === false || $ret == 0) {
            if(DSBERICHT_DEBUG) {
                echo "False match for '$pattern '\n";
            }
            return false;
        }
        return $matches;
    }
    
    private function matchAll($pattern, $data=null) {
        $data = $data ?? $this->data;
        $matches = [];
        $ret = preg_match_all("/" . $pattern . "/s", $data, $matches);
        
        if($ret === false || $ret == 0) {
            if(DSBERICHT_DEBUG) {
                echo "False match for '$pattern '\n";
            }
            return false;
        }
        return $matches;
    }
    
    private function javaToPHPTimeFormat($javaFormat) {
        /*
         * //PHP
        $keys = array(
            'Y' => array('year', '\d{4}'),
            ok 'y' => array('year', '\d{2}'),
            ok 'm' => array('month', '\d{2}'),
            'n' => array('month', '\d{1,2}'),
            'M' => array('month', '[A-Z][a-z]{3}'),
            'F' => array('month', '[A-Z][a-z]{2,8}'),
            ok 'd' => array('day', '\d{2}'),
            'j' => array('day', '\d{1,2}'),
            'D' => array('day', '[A-Z][a-z]{2}'),
            'l' => array('day', '[A-Z][a-z]{6,9}'),
            'u' => array('hour', '\d{1,6}'),
            'h' => array('hour', '\d{2}'),
            ok 'H' => array('hour', '\d{2}'),
            'g' => array('hour', '\d{1,2}'),
            'G' => array('hour', '\d{1,2}'),
            ok 'i' => array('minute', '\d{2}'),
            ok 's' => array('second', '\d{2}')
        );
         */
        $java = [
            'yy',
            'MM',
            'dd',
            'HH',
            'mm',
            'ss',
        ];
        $php = [
            'y',
            'm',
            'd',
            'H',
            'i',
            's',
        ];
        return str_replace($java, $php, $this->gProp($javaFormat));
    }
    
    private function getTroopsPattern($trailingSpace, $milita) {
        $troopRegex = "";
        foreach($this->units as $unit) {
            if($milita || $unit->iname != "milita") {
                if(!$trailingSpace) {
                    $troopRegex .= "([0-9]+)";
                    $trailingSpace = true;
                } else {
                    $troopRegex .= "\\s+([0-9]+)";
                }
            }
        }
        return $troopRegex;
    }
};
?>
