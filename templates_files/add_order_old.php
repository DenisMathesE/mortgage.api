<?php

require_once("/home/repo/mortgage/config.php");
require_once("/home/repo/mortgage/mysql.php");

$db_mysql = new db;
$db_mysql->connect(1);

$body['order_id'] = 256;
$body['bank_id'] = 1;

                $order = $db_mysql->fetch("select * from ordersBanks where order_id = {$body['order_id']} and bank_id = {$body['bank_id']}");
                $borrowers = [];
                $sql = $db_mysql->query("select * from borrowersBanks where order_id = {$body['order_id']} and bank_id = {$body['bank_id']} order by borrower_type");
                while ($row = $db_mysql->fetch($sql)) {
                    $borrowers[$row['id']] = $row;
                }

                $fields = [];
                $sql = $db_mysql->query("select f.name, fc.key, fc.value from fields f join fieldsContent fc on fc.fieldId = f.id");
                while ($row = $db_mysql->fetch($sql)) {
                    $fields[$row['name']][$row['key']] = $row['value'];
                }

                $templates = [];
                $sql = $db_mysql->query("select * from bankTemplates where bank_id = {$body['bank_id']} and reason = 'addorder'");
                while ($row = $db_mysql->fetch($sql)) {
                    $templates[$row['type']] = $row;
                }

                if (!count($templates)) {
                    $sql = $db_mysql->query("select * from bankTemplates where bank_id is null and reason = 'addorder'");
                    while ($row = $db_mysql->fetch($sql)) {
                        $templates[$row['type']] = $row;
                    }
                }

                $borrowersHtml = "";
                foreach ($borrowers as $borrower) {
                    $content = $templates['borrower']['content'];
                    if ($borrower['passport_take_date']) {
                        $borrower['passport_take_date'] = date('d.m.Y', strtotime($borrower['passport_take_date']));
                    }
                    if ($borrower['birthday']) {
                        $borrower['birthday'] = date('d.m.Y', strtotime($borrower['birthday']));
                    }
                    if ($borrower['work_start_work']) {
                        $borrower['work_start_work'] = date('d.m.Y', strtotime($borrower['work_start_work']));
                    }
                    if ($borrower['registration_equal_live']) {
                        $borrower['live_address'] = $borrower['registration_address'];
                    }

                    if (!strlen(trim($borrower['fio_changed_reason']))) {
                        $content = clearByTag('fio_changed_reason', $content);
                    } elseif (!strlen(trim($borrower['fio_changed_custom_reason']))) {
                        $content = clearByTag('fio_changed_custom_reason', $content);
                    }

                    if ($borrower['borrower_type'] != 2) {
                        $content = clearByTag('borrower_type', $content);
                    }
                    if ($borrower['use_money'] == 2) {
                        $content = clearByTag('use_money', $content);
                    }
                    if ($borrower['citizenship'] == 1) {
                        $content = clearByTag('citizenship_country', $content);
                    }
                    if ($borrower['income_confirm'] != 1) {
                        $content = clearByTag('income_bank_name', $content);
                    }

                    $content = clearReplaceTags($content);

                    foreach ($borrower as $key => $val) {
                        if (isset($fields[$key]) && isset($fields[$key][$val])) {
                            $val = $fields[$key][$val];
                        }
                        $content = str_replace("%{$key}%", $val, $content);
                    }
                    $borrowersHtml .= $content;
                }

                foreach ($order as $key => $val) {
                    if (isset($fields[$key]) && isset($fields[$key][$val])) {
                        $val = $fields[$key][$val];
                    }
                    $templates['main']['content'] = str_replace("%{$key}%", $val, $templates['main']['content']);
                }

                $sendingBody = str_replace('%borrowers%', $borrowersHtml, $templates['main']['content']);

                echo $sendingBody . "\n";







                
                function clearByTag( $tag, $content ) {
                    return preg_replace("/%%{$tag}\*\*(.*)\*\*{$tag}%%/us", '', $content);
                }
                
                function clearReplaceTags( $content ) {
                    return preg_replace("/(%%[^%*]{5,}\*\*)/us", '', preg_replace("/(\*\*[^%*]{5,}%%)/us", '', $content));
                }