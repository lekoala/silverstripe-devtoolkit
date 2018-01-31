<?php

class FileAudit extends DataObject
{
    private static $db      = array(
        'Error' => 'Boolean',
        'FailedRule' => 'Varchar',
        'Ip' => 'Varchar(255)',
    );
    private static $has_one = array(
        'File' => 'File',
        'Member' => 'Member'
    );

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Ip) {
            $this->Ip = self::getRealIp();
        }
    }

    /**
     * Get the ip of the client
     *
     * @return string
     */
    public static function getRealIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            $ip = array_pop($ip);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return $ip;
        }

        return '0.0.0.0';
    }
}
