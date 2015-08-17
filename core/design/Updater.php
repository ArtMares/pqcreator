<?php

class UpdateDSApp {
    public $url;
    public $arr;

    function __construct()
    {
        if (!$ch = @curl_init()) 
            return $this->err(0);
        curl_close($ch);
        return true;
    }
    
    public static function TUDSAEH()
    {
        return true;
    }
    
    function check($url = false)
    {
        if ($url) return $this->getXML($url);
    }
    
    private function checkHost($host)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_exec($ch);
        $err = curl_error($ch);
        if (!empty($err)) return false;
        return true;
    }
    
    private function err($num, $val = "")
    {
        $err = array(
            0 => "Библиотека php_curl.dll не подключена к проекту",
            1 => "Недопустимый URL '$val'",
            2 => "Сервер '$val' не доступен",
            3 => "Ошибка загрузки файла '$val'",
            4 => "Ошибка удаления директории '$val'",
            5 => "Ошибка удаления файла '$val'",
            6 => "Ошибка создания директории '$val'",
            7 => "Ошибка записи в файл '$val'",
            8 => "Ошибка удаления файла '$val'"
        );
        return "Err:$num: " . $err[$num];
    }
    
    private function getXML($url)
    {
        if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url)) return $this->err(1, $url);
        $x = explode("/", $url);
        
        if (!$this->checkHost($x[2])) return $this->err(2, $x[2]);
        $ch = curl_init($url . "/update.xml");
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code < 200 || $http_code >= 300) 
            return $this->err(3, $url . "/update.xml");
        
        $this->url = $url;
        $this->arr = simplexml_load_string($response);
        return true;
    }
    
    function updateInfo($key = "version")
    {
        return trim($this->arr->$key);
    }
    
    private function removeDirectory($dir)
    {
        if ($objs = glob($dir . "/*")) foreach($objs as $obj) {
            if (is_dir($obj)) {
                $result = $this->removeDirectory($obj);
                if ($result != 1) return $result;
            }
            else {
                if (!unlink($obj)) return $this->err(4, $obj);
            }
        }
        if (!rmdir($dir)) return $this->err(5, $obj);
        return true;
    }
    
    private function getFile($url, $dest)
    {
        $fp = fopen($dest, 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        
        if (curl_exec($ch) != 1) 
            return $this->err(3, $url);
            
        curl_close($ch);
        fclose($fp);
        return true;
    }
    
    private function crtTmp($backup, $temp, $file)
    {
        $fn = basename($file);
        $dn = dirname($file);
        
        if (!is_dir($dn)) if (!mkdir($dn)) 
            return $this->err(6, $dn);
            
        if (!is_dir("$temp/$dn")) if (!mkdir("$temp/$dn")) 
            return $this->err(6, "$temp/$dn");
            
        if (is_file("$dn/$fn")) if (!is_writable("$dn/$fn")) 
            return $this->err(7, "$dn/$fn");
        
        $result = $this->getFile($this->url . "/$dn/$fn", "$temp/$dn/$fn");
        
        if ($result != 1) 
            return $result;
            
        return true;
    }
    
    private function downloadUpdate($backup, $temp)
    {
        foreach($this->arr->newfiles->file as $file) {
            if ($file == "")
                continue;
                
            $result = $this->crtTmp($backup, $temp, $file);
            
            if ($result != 1) 
                return $result;
            
            $fn = basename($file);
            $dn = dirname($file);
            
            if (!is_dir("$backup/$dn")) if (!mkdir("$backup/$dn")) 
                return $this->err(6, "$backup/$dn");
                
            copy("$dn/$fn", "$backup/$dn/$fn");
        }
        
        foreach($this->arr->delfiles->file as $file) {
            if ($file == "") 
                continue;
                
            if (is_file($file)) if (!is_writable($file)) 
                return $this->err(7, $file);
            
            $fn = basename($file);
            $dn = dirname($file);
            
            if (!is_dir("$backup/$dn")) if (!mkdir("$backup/$dn")) 
                return $this->err(6, "$backup/$dn");
                
            if (is_file("$dn/$fn")) 
                copy("$dn/$fn", "$backup/$dn/$fn");
        }
        
        return true;
    }
    
    private function preUpdate()
    {
        foreach($this->arr->newfiles->file as $file) {
            if ($file == "") 
                continue;
                
            if (is_file($file)) 
                if (!rename($file, $file . ".old")) 
                    return $this->err(7, $file . ".old");
        }
        
        foreach($this->arr->delfiles->file as $file) {
            if ($file == "") 
                continue;
                
            if (is_file($file)) 
                if (!rename($file, $file . ".old")) 
                    return $this->err(7, $file . ".old");
        }
        
        return true;
    }
    
    private function updateFn($backup, $temp)
    {
        if (is_dir($backup)) 
            $this->removeDirectory($backup);
            
        if (is_dir($backup)) {
            $result = $this->removeDirectory($backup);
            if ($result != 1) 
                return $result;
        }
        
        if (!mkdir($backup)) 
            return $this->err(6, $backup);
            
        if (is_dir($temp)) {
            $result = $this->removeDirectory($temp);
            if ($result != 1) 
                return $result;
        }
        
        if (!mkdir($temp)) 
            return $this->err(6, $temp);
            
        $result = $this->downloadUpdate($backup, $temp);
        
        if ($result != 1) 
            return $result;
            
        $result = $this->preUpdate();
        
        if ($result != 1) {
            foreach($this->arr->newfiles->file as $file) {
                if ($file == "") 
                    continue;
                copy("$backup/$file", $file);
            }
            
            foreach($this->arr->delfiles->file as $file) {
                if ($file == "") 
                    continue;
                    
                if (is_file("$backup/$file")) 
                    copy("$backup/$file", $file);
            }
            
            if (is_dir($backup)) 
                $this->removeDirectory($backup);
                
            if (is_dir($temp)) 
                $this->removeDirectory($temp);
                
            return $result;
        }
        
        foreach($this->arr->newfiles->file as $file) {
            if ($file == "") 
                continue;
            copy("$temp/$file", $file);
        }
        
        return true;
    }
    
    function clean()
    {
        $eh = set_error_handler(array(new $this(), 'TUDSAEH'));
        
        if (!is_file("update.xml")) 
            return false;
            
        $xml = simplexml_load_file("update.xml");
        
        foreach($xml->newfiles->file as $file) {
            if ($file == "") 
                continue;
                
            if (is_file($file . ".old")) 
                if (!unlink($file . ".old")) {
                    set_error_handler($eh);
                    return $this->err(8, $file . ".old");
                }
        }
        
        foreach($xml->delfiles->file as $file) {
            if ($file == "") 
                continue;
                
            if (is_file($file . ".old")) 
                if (!unlink($file . ".old")) {
                    set_error_handler($eh);
                    return $this->err(8, $file . ".old");
                }
        }
        
        if (trim($xml->exec) != "") if (!unlink(trim($xml->exec))) {
            set_error_handler($eh);
            return $this->err(8, trim($xml->exec));
        }
        
        if (!unlink("update.xml")) {
            set_error_handler($eh);
            return $this->err(8, $file . ".old");
        }
        
        set_error_handler($eh);
        
        return true;
    }
    
    function update($restart = false, $backup = "back_00_00", $temp = "temp_00_00")
    {
        $eh = set_error_handler(array( new $this(), 'TUDSAEH'));
        
        $result = $this->updateFn($backup, $temp);
        
        if ($result != 1) {
            set_error_handler($eh);
            return $result;
        }
        
        set_error_handler($eh);
        
        global $_PARAMS, $APPLICATION;
        
        $exec = false;
        if (trim($this->arr->exec) != "") {
            $exec = trim($this->arr->exec);
            $this->arr->exec = basename($_PARAMS[0]);
        }
        
        $this->arr->asXML("update.xml");
        
        if (is_dir($backup)) 
            $this->removeDirectory($backup);
            
        if (is_dir($temp)) 
            $this->removeDirectory($temp);
            
        if ($restart) {
            $exec === FALSE ? Shell_Execute(0, "open", $_PARAMS[0], nil, nil, SW_RESTORE) : Shell_Execute(0, "open", dirname($_PARAMS[0]) . "\\" . $exec, nil, nil, SW_RESTORE);
            app::close();
            $APPLICATION->terminate();
        }
        
        return true;
    }
}
