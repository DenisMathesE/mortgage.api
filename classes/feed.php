<?php

class Feed
{
    protected $data = [];
    protected $id;
    protected $db_mysql;

    function __construct($db_mysql, $data)
    {
        $this->data = $data;
        $this->db_mysql = $db_mysql;
    }

    function save() {
        $answer  = [];

        $update = [];
        $update['direct'] = 'out';
        $update['status'] = 'added';

        if ( isset($this->data['company']) ) {
            $client = new PlatformClient( $this->db_mysql, $this->data['company'] );
            $client_id = $client->findBy(['platform_client_id' => $this->data['company']['id']]);
            if ( $client_id && isset($this->data['company_user']) ) {
                $platformUser = new PlatformUser( $this->db_mysql, $this->data['company_user'] );
                $user_id = $platformUser->findBy(['client_id' => $client_id, 'platform_user_id' => $this->data['company_user']['id']]);
                if ( $user_id ) {
                    $update['platform_user_id'] = $user_id;
                }
            }
        }

        if ( isset($this->data['id']) ) {
            $update['external_id'] = $this->db_mysql->safe($this->data['id']);
        } else { return ['error' => 1, 'error_message' => "Отсутствует ID номер сообщения"]; }
        if ( isset($this->data['bank_id']) ) {
            $update['bank_id'] = intval($this->data['bank_id']);
        } else { return ['error' => 1, 'error_message' => "Отсутствует ID номер банка"]; }
        if ( isset($this->data['mortgage_id']) ) {
            $update['order_id'] = intval($this->data['mortgage_id']);
        } else { return ['error' => 1, 'error_message' => "Отсутствует ID номер заявки"]; }
        if ( isset($this->data['content']) ) {
            $update['content'] = $this->db_mysql->safe($this->data['content']);
        } else { return ['error' => 1, 'error_message' => "Отсутствует текст сообщения"]; }

        $update = checkByTableFields($this->db_mysql, "feed", $update);

        $this->id = $this->findBy($update);

        if ( !$this->id ) {
            $this->id = $this->add($update);
        } else {
            return ['error' => 1, 'error_message' => "Данное сообщение уже было сохранено"];
        }

        if ( isset($this->data['files']) ) {
            $this->saveFiles($this->data['files']);
        }

        if ( $this->id ) {
            $sendData = ['id' => $this->id];
            $this->am->sendTask($sendData, 'mortSendMessageToBanks');
            return ['id' => $this->id, 'external_id' => $update['external_id']];
        } else {
            return ['error' => 1, 'error_message' => "Не удалось сохранить сообщение"];
        }
    }

    protected function findBy( $data ) {
        if ( isset($data['external_id']) && $data['external_id'] ) {
            $feed = $this->db_mysql->fetch("select id from feed where order_id = {$data['order_id']} and bank_id = {$data['bank_id']} and external_id = '{$data['external_id']}'");
            if (isset($feed['id'])) {
                return $feed['id'];
            } else {
                return 0;
            }
        }

        return 0;
    }

    protected function add( $data ) {
        $data['date'] = time();
        return $this->db_mysql->insert('feed', $data);
    }

    protected function saveFiles( $files ) {

        $amWb = new ampq;

        $amWb->connectInfo = array(
            array('host' => "192.168.0.52", 'port' => 5672, 'vhost' => "/", "user" => 'jesus', "password" => 'common' ),
            array('host' => "192.168.1.52", 'port' => 5672, 'vhost' => "/", "user" => 'jesus', "password" => 'common' )
        );

        foreach ( $files as $data ) {
            $md5 = md5($data['data']);
            $ext = ((isset($data['ext']) && strlen($data['ext']))?$data['ext']:'jpg');

            $thisFile = $this->db_mysql->fetch("select id from files where system_name = '{$md5}' and type = ''");
            if (!isset($thisFile['id'])) {

                $thisFile['id'] = $this->db_mysql->insert('files', ['date' => time(), 'system_name' => $md5, 'ext' => $ext, 'client_id' => CLIENTID]);

                if (isset($thisFile['id'])) {
                    $sendData = array(
                        "data" => array(
                            "cmd" => "saveFile",
                            "query" => array(
                                "subFolder" => "mortgage",
                                "fileName" => $md5 . ".{$ext}",
                                "fileType" => "base64",
                                "data" => $data['data'],
                                "operations" => array()
                            )
                        ),
                        "callback" => array(
                            "exchange" => "",
                            "queue" => "wbsMortFileSave",
                            "msg" => ['fileLoaded' => $md5]
                        )
                    );
                    $res = $amWb->sendTask($sendData, 'fileServers_task');
                }
            }

            if (isset($thisFile['id'])) {
                $ordFile = $this->db_mysql->fetch("select id from feedFiles where feed_id " . (($this->id) ? " = {$this->id}" : 'is null') . " and file_id = {$thisFile['id']}");
                if (!isset($ordFile['id'])) {
                    $this->db_mysql->insert("feedFiles", ['feed_id' => $this->id, 'file_id' => $thisFile['id'], 'date' => time(), 'order_id' => intval($this->data['mortgage_id'])]);
                }
            }
        }

        $amWb->disconnect();
    }
}