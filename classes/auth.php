<?php

class Auth {
    protected $platform;
    protected $isAuth;
    protected $platformId;

    public $db_mysql;
    public $key;

    function __construct( $db_mysql ) {
        $this->db_mysql = $db_mysql;

        // $_SERVER['HTTP_AUTHORIZATION'] = "ce4f27b14f419255b24ea5a87e23d11e";

        if ( isset($_SERVER['HTTP_AUTHORIZATION']) ) {
            $this->key = $_SERVER['HTTP_AUTHORIZATION'];
        }

        $this->key = $this->db_mysql->safe($this->key);
        if ( $this->key ) {
            $platform = $this->db_mysql->fetch("select * from platforms where token = '{$this->key}'");
            if ( isset($platform['id']) ) {
                $this->platform = $platform;
                $this->platformId = $platform['id'];
                define("PLATFORMID", $platform['id']);
            }
        }

    }

    public function isAuth() {
        if ( $this->key && $this->platformId ) {
            return true;
        }
        return false;
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function end() {
//        $this->db_mysql->disconnect();
    }
}
