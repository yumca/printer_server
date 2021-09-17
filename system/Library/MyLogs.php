<?php

class MyLogs
{

    private $FilePath;
    private $FileName;
    private $m_MaxLogFileNum;
    private $m_RotaType;
    private $m_RotaParam;
    private $m_LogCount;
    private $root = '/logs/';
    private $logExt = '.log';
    private $Store = 10;
    private $LogLevel = 0;
    private $Mode = '';
    private $email = false;

    /**
     * @abstract 初始化
     * @param String $dir 文件路径
     * @param String $filename 文件名
     * @return
     */
    function __construct($filename = '', $maxlogfilenum = 3, $rotatype = 1, $rotaparam = 5000000)
    {
        if (!defined('PATH')) {
            echo 'PATH配置丢失!';
            exit;
        }
        if (!empty($filename)) {
            $dot_offset = strpos($filename, ".");
            if ($dot_offset !== false) {
                $this->FileName = substr($filename, 0, $dot_offset);
            } else {
                $this->FileName = $filename;
            }
        } else {
            $this->FileName = date('d');
        }

        $this->m_MaxLogFileNum = intval($maxlogfilenum);
        $this->m_RotaParam = intval($rotaparam);
        $this->m_RotaType = intval($rotatype);
        $this->m_LogCount = 0;
    }

    private function createPath($path = '', $fileName = '')
    {
        return $path . $fileName . $this->logExt;
    }

    private function InitDir()
    {
        $this->FilePath = PATH . $this->root . $this->Mode . '/' . date('Ym') . '/';
        if (is_dir($this->FilePath) === false) {
            if (!$this->createDir($this->FilePath)) {
                //echo("创建目录失败!");
                //throw exception
                return false;
            }
        }
        umask(0000);
        $path = $this->createPath($this->FilePath, $this->FileName);
        if (!$this->isExist($path)) {
            if (!$this->createLogFile($path)) {
                #echo("创建文件失败!");
                return false;
            }
        }
        return true;
    }

    /**
     * @abstract 设置日志类型
     * @param String $error 类型
     */
    private function setLogType($error = '', $isMonitor = false)
    {
        switch ($error) {
            case 'f':
                $type = 'FATAL';
                $this->LogLevel = 50;
                //当错误级别为f,设置为30;
                break;
            case 'e':
                $type = 'ERROR';
                $this->LogLevel = 10;
                //当错误级别为e,设置为30;
                break;
            default;
                $type = 'INFO';
                break;
        }
        $str = ' [' . $type . '] ';
        if (in_array($error, array('e', 'f'))) {
            if ($isMonitor) {
                $str .= '[' . $this->Mode . '] ';
            }
        }
        return $str;
    }

    /**
     * @abstract 写入日志
     * @param String $log 内容
     */
    public function doLog($dir, $log = '', $data = array(), $priority = '', $backtrace = '')
    {
        $this->Mode = $dir;
        if ($this->InitDir() == false) {
            return false;
        }
        if (empty($log)) {
            return false;
        }
        $logType = $this->setLogType($priority);
        $path = $this->getLogFilePath($this->FilePath, $this->FileName) . $this->logExt;
        $handle = @fopen($path, "a+");
        if ($handle === false) {
            return false;
        }
        $txtData = !empty($data) ? ' Data:[' . json_encode($data, JSON_UNESCAPED_UNICODE) . ']' : '';
        $datestr = '[' . strftime("%Y-%m-%d %H:%M:%S") . ']';
        $line = !empty($backtrace['line']) ? '[Line:' . $backtrace['line'] . '] ' : '';
        $file = !empty($backtrace['file']) ? '[File:' . $backtrace['file'] . '] ' : '';
        if (!@fwrite($handle, $datestr . $logType . $file . $line . $log . $txtData . ';' . "\n")) { //写日志失败
            echo ("写入日志失败");
        }
        @fclose($handle);
        $this->RotaLog();
    }

    private function get_caller_info()
    {
        $ret = debug_backtrace();
        foreach ($ret as $item) {
            if (isset($item['class']) && 'Logs' == $item['class']) {
                continue;
            }
            $file_name = basename($item['file']);
            return <<<S
          {$file_name}:{$item['line']}
S;
        }
    }

    private function RotaLog()
    {
        $file_path = $this->getLogFilePath($this->FilePath, $this->FileName) . $this->logExt;
        if ($this->m_LogCount % 10 == 0)
            clearstatcache();
        ++$this->m_LogCount;
        $file_stat_info = stat($file_path);
        if ($file_stat_info === FALSE)
            return;
        if ($this->m_RotaType != 1)
            return;

        //echo "file: ".$file_path." vs ".$this->m_RotaParam."\n";
        if ($file_stat_info['size'] < $this->m_RotaParam)
            return;

        $raw_file_path = $this->getLogFilePath($this->FilePath, $this->FileName);
        $file_path = $raw_file_path . ($this->m_MaxLogFileNum - 1) . $this->logExt;
        //echo "lastest file:".$file_path."\n";
        if ($this->isExist($file_path)) {
            unlink($file_path);
        }
        for ($i = $this->m_MaxLogFileNum - 2; $i >= 0; $i--) {
            if ($i == 0)
                $file_path = $raw_file_path . $this->logExt;
            else
                $file_path = $raw_file_path . $i . $this->logExt;

            if ($this->isExist($file_path)) {
                $new_file_path = $raw_file_path . ($i + 1) . $this->logExt;
                if (rename($file_path, $new_file_path) < 0) {
                    continue;
                }
            }
        }
    }

    private function isExist($path)
    {
        return file_exists($path);
    }

    /**
     * @abstract 创建目录
     * @param <type> $dir 目录名
     * @return bool
     */
    private function createDir($dir)
    {
        return is_dir($dir) or ($this->createDir(dirname($dir)) and @mkdir($dir, 0777));
    }

    /**
     * @abstract 创建日志文件
     * @param String $path
     * @return bool
     */
    private function createLogFile($path)
    {
        $handle = @fopen($path, "w"); //创建文件
        @fclose($handle);
        return $this->isExist($path);
    }

    /**
     * @abstract 创建路径
     * @param String $dir 目录名
     * @param String $filename
     */
    private function getLogFilePath($dir, $filename)
    {
        return $dir . "/" . $filename;
    }
}
