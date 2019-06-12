<?php
class Core_Ini
{
   public static function parse($filename) 
	{
        $ini = array();
        $lines = file($filename);
        $section = 'default';
        $multi = '';
        foreach($lines as $line) {
            if (substr($line, 0, 1) !== ';') {
                $line = str_replace("\r", "", str_replace("\n", "", $line));
                if (preg_match('/^\[(.*)\]/', $line, $m)) {
                    $section = $m[1];
                } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
                    $key = $m[1];
                    $val = $m[2];
                    if (substr($val, -1) !== "\\") {
                        $val = trim($val);
                        self::manageKeys($ini[$section], $key, $val);
                        $multi = '';
                    } else {
                        $multi = substr($val, 0, -1)."\n";
                    }
                } else if ($multi !== '') {
                    if (substr($line, -1) === "\\") {
                        $multi .= substr($line, 0, -1)."\n";
                    } else {
                        self::manageKeys($ini[$section], $key, $multi.$line);
                        $multi = '';
                    }
                }
            }
        }
        
        $buf = get_defined_constants(true);
        $consts = array();
		if(isset($buf['user'])) {
			foreach($buf['user'] as $key => $val) {
				$consts['{'.$key.'}'] = $val;
			}
		}
        array_walk_recursive($ini, array('self', 'replace_consts'), $consts);
        return $ini;
    }

    /**
     *  manage keys
     */
    public static function getValue($val) 
	{
        if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); } 
        else if (strtolower($val) === 'true') { return true; }
        else if (strtolower($val) === 'false') { return false; }
        else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
        else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
        return $val;
    }

    /**
     *  manage keys
     */
    public static function getKey($val) 
	{
        if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
        return $val;
    }

    /**
     *  manage keys
     */
    public static function manageKeys(& $ini, $key, $val) 
	{
        if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
            self::manageKeys($ini[$m[1]], $m[2], $val);
        } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
            if ($m[2] !== '') {
                $ini[$m[1]][self::getKey($m[2])] = self::getValue($val);
            } else {
                $ini[$m[1]][] = self::getValue($val);
            }
        } else {
            $ini[self::getKey($key)] = self::getValue($val);
        }
    }

    /**
     *  replace utility
     */
    public static function replace_consts(& $item, $key, $consts) 
	{
        if (is_string($item)) {
            $item = strtr($item, $consts);
        }
    }
}


