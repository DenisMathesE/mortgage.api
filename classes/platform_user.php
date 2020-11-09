<?php

class PlatformUser {
    protected $data = [];
    protected $id;
    protected $db_mysql;

    function __construct( $db_mysql, $data ){
        $this->data = $data;
        $this->db_mysql = $db_mysql;
    }

    public function save() {
        $update = [];

        if ( isset($this->data['id']) ) {
            $update['client_id'] = $this->data['client_id'];
            $update['platform_user_id'] = $this->db_mysql->safe($this->data['id']);
            $update['name'] = $this->db_mysql->safe($this->data['name']);
            $update['mobile'] = $this->db_mysql->safe($this->data['mobile']);

            $update = checkByTableFields($this->db_mysql, "platformsUsers", $update);

            $id = $this->findBy($update);

            if ( $id ) {
                $this->id = $id;
//                $this->update($update);
            } else {
                $this->id = $this->add($update);
            }

            if ( !$this->id ) {
                return ['error' => 1, 'error_message' => 'Не удалось создать клиента'];
            }

            return ['id' => $this->id];
        }

        return ['error' => 1, 'error_message' => 'Не удалось создать клиента'];

    }

    public function findBy( $data ) {
        $client = $this->db_mysql->fetch("select id from platformsUsers where client_id = '{$data['client_id']}' and platform_user_id = '{$data['platform_user_id']}'");
        if ( isset( $client['id'] ) ) {
            return $client['id'];
        }
        return 0;
    }

    protected function add( $data ) {
        $data['add_date'] = time();
        $data['update_date'] = time();
        return $this->db_mysql->insert('platformsUsers', $data);
    }

    protected function update( $data ) {
        $data['update_date'] = time();
        $this->db_mysql->update('platformsUsers', $data, "id = {$this->id}");
    }

    public function getId() {
        return $this->id;
    }
}