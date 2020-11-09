<?php

class Borrower {
    protected $data = [];
    protected $id;
    protected $db_mysql;

    function __construct( $db_mysql, $data ){
        if ( isset($data['fio']) ) {
            list($data['last_name'], $data['first_name'], $data['second_name']) = explode(" ", trim(preg_replace('/\s/', ' ', $data['fio'])));
        }
        $this->data = $data;
        $this->db_mysql = $db_mysql;
    }

    public function save() {
        $update = [];
        foreach ( $this->data as $key_1 => $val_1 ) {
            if ( is_array( $val_1 ) ) {
                foreach ( $val_1 as $key_2 => $val_2 ) {
                    $update["{$key_1}_{$key_2}"] = $val_2;
                }
            } else {
                $update["{$key_1}"] = $val_1;
            }
        }

        $update = checkByTableFields($this->db_mysql, "borrowers", $update);

        $id = $this->findBy($update);

        if ( $id ) {
            $this->id = $id;
            $this->update($update);
        } else {
            $this->id = $this->add($update);
        }

        if ( !$this->id ) {
            return ['error' => 1, 'error_message' => 'Не удалось создать запись заемщика'];
        }

        return ['id' => $this->id];
    }

    protected function findBy( $data ) {
        $borrower = $this->db_mysql->fetch("select id from borrowers where passport_serial = '{$data['passport_serial']}'");
        if ( isset( $borrower['id'] ) ) {
            return $borrower['id'];
        }

        $borrower = $this->db_mysql->fetch("select id from borrowers where birthday = '{$data['birthday']}' and ( ( first_name = '{$data['first_name']}' and second_name = '{$data['second_name']}' ) or ( first_name = '{$data['first_name']}' and last_name = '{$data['last_name']}' ) or ( second_name = '{$data['second_name']}' and last_name = '{$data['last_name']}' ) ) and mobile = '{$data['mobile']}'");
        if ( isset( $borrower['id'] ) ) {
            return $borrower['id'];
        }

        $borrower = $this->db_mysql->fetch("select id from borrowers where birthday = '{$data['birthday']}' and ( ( first_name = '{$data['first_name']}' and second_name = '{$data['second_name']}' ) or ( first_name = '{$data['first_name']}' and last_name = '{$data['last_name']}' ) or ( second_name = '{$data['second_name']}' and last_name = '{$data['last_name']}' ) ) and passport_birthplace = '{$data['passport_birthplace']}'");
        if ( isset( $borrower['id'] ) ) {
            return $borrower['id'];
        }
        return 0;
    }

    protected function add( $data ) {
        $data['add_date'] = time();
        $data['update_date'] = time();
        return $this->db_mysql->insert('borrowers', $data);
    }

    protected function update( $data ) {
        $data['update_date'] = time();
        $this->db_mysql->update('borrowers', $data, "id = {$this->id}");
    }

    public function getId() {
        return $this->id;
    }
}