<?php

function checkByTableFields( $db, $name, $data ) {
    $ans = $db->fetch("SHOW CREATE TABLE ".$db->safe($name));
    $fields = [];
    if ( isset( $ans['Create Table'] ) ) {
        $lines = explode("\n", $ans['Create Table']);
        foreach ( $lines as $key => $line ) {
            if ( !$key ) continue;
            if ( substr(trim($line), 0, 1) == '`' ) {
                list($i, $field) = explode("`", $line);
                $fields[$field] = $field;
            } else {
                break;
            }
        }
    }

    foreach ( $data as $key => $val ) {
        if ( !isset($fields[$key]) ) {
            unset($data[$key]);
        }
    }

    return $data;
}

function makeArrayForUpdate( $data ) {
    $result = [];
    if ( count( $data) && is_array($data) ) {
        foreach ( $data as $key => $val ) {
            $result[] = "`$key` = '{$val}'";
        }
    }
    return $result;
}
