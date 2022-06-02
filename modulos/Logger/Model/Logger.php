<?php

namespace Customcode\Logger\Model;

class Logger
{
    protected $_file_name;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct($file){
        $this->_file_name = $file;
    }

    public function info($msg){
        $log = "[execution - ".date("Y-m-d H:i:s")."]: ".$msg.PHP_EOL;
        //file_put_contents(__DIR__ . '/../../../../../var/log/'.$this->_file_name."-".date("Y-m-d").'.log', $log, FILE_APPEND);
        file_put_contents(__DIR__ . '/../../../../../var/log/'.$this->_file_name.'.log', $log, FILE_APPEND);
    }
}