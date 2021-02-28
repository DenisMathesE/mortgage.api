<?php

class Order {
    protected $data = [];
    public $am;
    protected $id;
    protected $same_order_id;
    protected $bank_id;
    protected $bank;
    protected $order;
    protected $external_id;
    protected $db_mysql;
    protected $borrowers_ids;
    protected $borrowers_types;
    protected $borrowers_use_money;
    protected $borrower_id_general;
    protected $platform_client;
    protected $platform_user;

    function __construct( $db_mysql, $data ){
        $this->data = $data;
        $this->db_mysql = $db_mysql;

        if ( !isset($this->data['order']['mortgage_id']) && isset($this->data['mortgage_id']) && !isset($this->data['order']) ) {
            $this->data['order'] = $this->data;
        }

        if ( isset($this->data['order']['mortgage_id']) && intval($this->data['order']['mortgage_id']) ) {
            $this->data['order']['mortgage_id'] = intval($this->data['order']['mortgage_id']);
            $order = $this->db_mysql->fetch("select * from orders where id = '{$this->data['order']['mortgage_id']}'");
            if ( isset($order['id']) ) {
                $this->id = $order['id'];
                $this->order = $order;
            }
            if ( isset($this->data['order']['bank_id']) && intval($this->data['order']['bank_id']) ) {
                $this->data['order']['bank_id'] = intval($this->data['order']['bank_id']);
                $bank = $this->db_mysql->fetch("select * from bankList where id = '{$this->data['order']['bank_id']}'");
                if ( isset($bank['id']) ) {
                    $this->bank_id = $bank['id'];
                    $this->bank = $bank;
                }
            }
        }
    }

    public function cancel() {
        if ( !$this->checkRules() ) {
            return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
        }

        $answer = ['order' => ['id' => $this->id]];

        if ( !$this->bank_id ) {
            $this->update(['order_status' => 7]);
        }

        $sendData = ['order_id' => $this->id, 'bank_id' => $this->bank_id, 'cmd' => 'cancel'];
        $this->am->sendTask($sendData, 'mortgageOrdersBanks');

        return $answer;
    }

    public function save() {
        $answer  = [];

        if ( isset($this->data['company']) ) {
            $client = new PlatformClient( $this->db_mysql, $this->data['company'] );
            $client->save();
            if ( !$client->getId() ) {
                $answer['company'] = ['error' => 1, 'error_message' => 'не удалось соханить данные компании'];
            } else {
                define('CLIENTID', $client->getId());
                $this->platform_client = $client->getId();
            }
        }

        if ( isset($this->data['company_user']) && isset($client) && $client->getId() ) {
            $this->data['company_user']['client_id'] = $client->getId();
            $platformUser = new PlatformUser( $this->db_mysql, $this->data['company_user'] );
            $platformUser->save();
            if ( !$platformUser->getId() ) {
                $answer['company_user'] = ['error' => 1, 'error_message' => 'не удалось соханить данные агента'];
            } else {
                $this->platform_user = $platformUser->getId();
            }
        }

        if ( isset($this->data['borrowers']) ) {
            foreach ( $this->data['borrowers'] as $key => $borrowerData ) {
                $borrower = new Borrower( $this->db_mysql, $borrowerData );
                $answer['borrowers'][] = $borrower->save();
                if ( $borrower->getId() ) {
                    $this->borrowers_ids[] = $borrower->getId();
                    $this->borrowers_types[$borrower->getId()] = $borrowerData['type'];
                    $this->borrowers_use_money[$borrower->getId()] = $borrowerData['use_money'];
                    if ( $borrowerData['type'] == 1 ) {
                        $this->borrower_id_general = $borrower->getId();
                    }
                    $this->data['borrowers'][$key]['id'] = $borrower->getId();
                } else {
                    $answer['borrowers'][] = ['error' => 1, 'error_message' => 'Не удалось сохранить данные заемщика'];
                }
            }
        }

        $answer['order'] = $this->saveOrder();

        if ( isset($this->data['borrowers']) && $this->id ) {
            foreach ( $this->data['borrowers'] as $key => $borrowerData ) {
                if ( isset($borrowerData['files']) ) {
                    $this->saveFiles($borrowerData['files'], $borrowerData['id']); 
                }
            }
        }

        $this->saveUsersOrders();

        $this->savePlatformsOrders();

        $this->saveOrdersBorrowers();

        $sendData = ['order_id' => $this->id, 'bank_id' => $this->bank_id];
        $this->am->sendTask($sendData, 'mortgageOrdersBanks');

        return $answer;
    }

    public function setCompany() {
        $answer = [];

        if ( $this->id ) {
            if (!$this->checkRules()) {
                return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
            }

            $orderBank = $this->db_mysql->fetch("select id, insurance_company_id, appraisal_company_id from ordersBanks where order_id = {$this->id} and bank_id = {$this->data['order']['bank_id']}");
            if ( isset($orderBank['id']) ) {
                if ( isset($this->data['order']['type']) && $this->data['order']['type'] && isset($this->data['order']['custom']) && strlen($this->data['order']['custom']) > 2 ) {
                    $external_id = $this->data['order']['company_id'];
                    $this->data['order']['company_id'] = $this->db_mysql->insert('companys', ['type' => $this->data['order']['type'], 'name' => $this->data['order']['custom'], 'custom' => 1, 'external_id' => $external_id, 'platform_id' => PLATFORMID]);
                    if ( !$this->data['order']['company_id'] ) {
                        $company = $this->db_mysql->fetch("select id from companys where type = '{$this->data['order']['type']}' and custom = 1 and external_id = '{$external_id}' and platform_id = ".PLATFORMID);
                        if ( isset($company['id']) ) {
                            $this->data['order']['company_id'] = $company['id'];
                        }
                    }
                    if ( !$this->data['order']['company_id'] ) {
                        return ['error' => 1, 'error_message' => 'Не удалось сохранить компанию'];
                    }
                }
                if ( isset($this->data['order']['company_id']) && isset($this->data['order']['type']) && array_key_exists($this->data['order']['type']."_company_id", $orderBank) ) {
                    $company = $this->db_mysql->fetch("select name, type from companys where id = {$this->data['order']['company_id']}");
                    if ( !isset($company['name']) ) {
                        return ['error' => 1, 'error_message' => 'Компании не существует'];
                    }
                    if ( $company['type'] != $this->data['order']['type'] ) {
                        return ['error' => 1, 'error_message' => 'Не верно указан тип компании'];
                    }
                    $this->data['order']['company_id'] = intval($this->data['order']['company_id']);
                    $this->db_mysql->query("update ordersBanks set {$this->data['order']['type']}_company_id = {$this->data['order']['company_id']} where id = {$orderBank['id']}");
                    $type = '';
                    $name = '';
                    if ($company['type'] == 'insurance' ) {
                        $type = 'страховая компания: ';
                    } elseif ($company['type'] == 'appraisal') {
                        $type = 'оценочная компания: ';
                    }
                    if ( isset($company['name']) ) {
                        $name = $company['name']; 
                    }
 
                    $feed_id = $this->db_mysql->insert('feed', ['bank_id' => $this->bank_id, 'order_id' => $this->id, 'direct' => 'out', 'type' => 'event', 'content' => "Выбрана {$type}{$name}", 'date' => time()]);
                    if ($feed_id) {
                        $sendData = [];
                        $sendData['cmd'] = "updateFeed";
                        $sendData['id'] = $feed_id;

                        $this->am->sendTask($sendData, 'mortgageFeedUpdate');
                    }
                    return ['success' => 1];
                }
            } else {
                return ['error' => 1, 'error_message' => 'Заявка в данный банк не отправлялась'];
            }
        }
    }

    public function setDealDate() {
        $answer = [];

        if ( $this->id ) {
            if (!$this->checkRules()) {
                return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
            }

            $orderBank = $this->db_mysql->fetch("select id from ordersBanks where order_id = {$this->id} and bank_id = {$this->data['order']['bank_id']}");
            if ( isset($orderBank['id']) ) {
                if ( isset($this->data['order']['deal_date']) && $this->data['order']['deal_date'] > time() ) { 
                    $this->db_mysql->query("update ordersBanks set bank_deal_date = {$this->data['order']['deal_date']} where id = {$orderBank['id']}");

                    $feed_id = $this->db_mysql->insert('feed', ['bank_id' => $this->bank_id, 'order_id' => $this->id, 'direct' => 'out', 'type' => 'event', 'content' => "Выбрана дата сделки: ".date('d.m.Y H:i:s', $this->data['order']['deal_date']), 'date' => time()]);
                    if ($feed_id) {
                        $sendData = [];
                        $sendData['cmd'] = "updateFeed";
                        $sendData['id'] = $feed_id;

                        $this->am->sendTask($sendData, 'mortgageFeedUpdate');
                    }

                    return ['success' => 1];
                }
                return ['error' => 1, 'error_message' => 'Указанная дата уже прошла'];
            } else {
                return ['error' => 1, 'error_message' => 'Заявка в данный банк не отправлялась'];
            }
        }
    }

    public function getOrderStatus() {
        $answer = [];
        if ( $this->id ) {

            if ( !$this->checkRules() ) {
                return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
            }

            $orderStatuses = [];
            $sql = $this->db_mysql->query("select * from fieldsContent where fieldId = 96");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $orderStatuses[$row['key']] = $row;
            }
            $banksOrderStatuses = [];
            $sql = $this->db_mysql->query("select * from fieldsContent where fieldId = 106");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $banksOrderStatuses[$row['key']] = $row;
            }
            $banks = [];
            $sql = $this->db_mysql->query("select s.*, b.id, b.name from ordersBanks s left join bankList b on b.id = s.bank_id where order_id = {$this->id}");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $banks[] = $row;
            }

            $answer['order']['id'] = $this->id;
            $answer['order']['status'] = ((isset($orderStatuses[$this->order['order_status']]))?$orderStatuses[$this->order['order_status']]['value']:$this->order['order_status']);

            if ( count($banks) ) {
                foreach ( $banks as $bank ) {
                    $ans = [];
                    $ans['id'] = $bank['id'];
                    $ans['name'] = $bank['name'];
                    $ans['status'] = $bank['bank_order_status'];
                    $ans['status_name'] = ((isset($banksOrderStatuses[$bank['bank_order_status']]))?$banksOrderStatuses[$bank['bank_order_status']]['value']:$bank['bank_order_status']);
                    $answer['banks'][] = $ans;
                }
            }
            return $answer;
        } else {
            return ['error' => 1, 'error_message' => 'Заявка с данным номером не найдена'];
        }
    }

    public function getOrderStatusHistory() {
        $answerOrder = $this->getOrderStatus();
        $answer = [];

        if ( $this->id ) {

            if ( !$this->checkRules() ) {
                return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
            }

            $orderStatuses = [];
            $sql = $this->db_mysql->query("select * from fieldsContent where fieldId = 96");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $orderStatuses[$row['key']] = $row;
            }
            $banksOrderStatuses = [];
            $sql = $this->db_mysql->query("select * from fieldsContent where fieldId = 106");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $banksOrderStatuses[$row['key']] = $row;
            }

            $history = [];
            $answer['order'] = ['id' => $answerOrder['order']['id'], 'status' => $answerOrder['order']['status']];
            $sql = $this->db_mysql->query("select * from ordersStatusesHistory where order_id = {$this->id} order by date");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                if ( $row['order_status'] ) {
                    $ans = [];
                    $ans['date'] = date('Y-m-d H:i:s', $row['date']);
                    $ans['status'] = ((isset($orderStatuses[$row['order_status']])) ? $orderStatuses[$row['order_status']]['value'] : $row['order_status']);
                }

                $answer['order']['history'][] = $ans;
            }

            if ( count( $answerOrder['banks'] ) ) {
                foreach ( $answerOrder['banks'] as $bank ) {
                    $ansBank = $bank;
                    $sql = $this->db_mysql->query("select * from ordersBanksStatusesHistory where order_id = {$this->id} and bank_id = {$bank['id']}");
                    while( $row = $this->db_mysql->fetch( $sql ) ) {
                        $ans = [];
                        $ans['date'] = date('Y-m-d H:i:s', $row['date']);
                        $ans['status'] = ((isset($banksOrderStatuses[$row['bank_order_status']]))?$banksOrderStatuses[$row['bank_order_status']]['value']:$row['bank_order_status']);
                        $ansBank['history'][] = $ans;
                    }
                    $answer['banks'][] = $ansBank;
                }
            }

            return $answer;
        } else {
            return ['error' => 1, 'error_message' => 'Заявка с данным номером не найдена'];
        }
    }

    public function getOrderInfo() {
        if ( $this->id ) {

            if ( !$this->checkRules() ) {
                return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
            }

            $orderStatuses = [];
            $sql = $this->db_mysql->query("select * from fieldsContent where fieldId = 96");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $orderStatuses[$row['key']] = $row;
            }
            $banksOrderStatuses = [];
            $sql = $this->db_mysql->query("select * from fieldsContent where fieldId = 106");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $banksOrderStatuses[$row['key']] = $row;
            }

            $answer['order'] = $this->order;
            $answer['order']['status'] = $answer['order']['order_status'];
            $answer['order']['status_value'] = ((isset($orderStatuses[$answer['order']['order_status']]))?$orderStatuses[$answer['order']['order_status']]['value']:$answer['order']['order_status']);

            unset($answer['order']['same_order_id']);
            unset($answer['order']['order_status']);

            $ordersBanks = [];
            $sql = $this->db_mysql->query("select o.*, b.name bank_name, ci.name insurance_company_name, ca.name appraisal_company_name, ci.custom insurance_custom, ca.custom appraisal_custom from ordersBanks o left join bankList b on b.id = o.bank_id left join companys ci on ci.id = o.insurance_company_id left join companys ca on ca.id = o.appraisal_company_id where o.order_id = {$this->id}");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                if ( $row['appraisal_custom'] ) {
                    $row['appraisal_custom_company_id'] = $row['appraisal_company_id'];
                    $row['appraisal_company_id'] = '';
                }
                if ( $row['insurance_custom'] ) {
                    $row['insurance_custom_company_id'] = $row['insurance_company_id'];
                    $row['insurance_company_id'] = '';
                }
                unset($row['appraisal_custom']);
                unset($row['insurance_custom']);

                if ( $row['bank_deal_date'] ) {
                    $row['bank_deal_date_gmt'] = gmdate('Y-m-d H:i:s', $row['bank_deal_date']);
                }

                if ( $row['bank_deal_date_end'] ) {
                    $row['bank_deal_date_end_gmt'] = gmdate('Y-m-d H:i:s', $row['bank_deal_date_end']);
                }

                $ordersBanks[$row['id']] = $row;
            }

            $general_borrower_id = 0;
            $general_borrower = $this->db_mysql->fetch("select borrower_id from ordersBorrowers where order_id = {$this->id} and general = 1");
            if ( isset($general_borrower['borrower_id']) ) {
                $general_borrower_id = $general_borrower['borrower_id'];
            }

            $answer['banks'] = [];
            if ( count($ordersBanks) ) {
                foreach ( $ordersBanks as $orderBank ) {
                    $ans = $orderBank;
                    $ans['id'] = $ans['bank_id'];
                    $ans['status'] = $ans['bank_order_status'];
                    $ans['status_value'] = ((isset($banksOrderStatuses[$ans['bank_order_status']]))?$banksOrderStatuses[$ans['bank_order_status']]['value']:$ans['bank_order_status']);
                    unset($ans['bank_order_status']);
                    unset($ans['parent_update_date']);
                    unset($ans['bank_id']);

                    $borrowersBanks = [];
                    $sql_b = $this->db_mysql->query("select * from borrowersBanks where order_id = {$this->id} and bank_id = {$orderBank['bank_id']}");
                    while( $row_b = $this->db_mysql->fetch( $sql_b ) ) {
                        $borrowersBanks[$row_b['id']] = $row_b;
                    }

                    $ans['borrowers'] = [];
                    if ( count($borrowersBanks) ) {
                        foreach ( $borrowersBanks as $borrowersBank ) {
                            $ans_b = $borrowersBank;
                            $ans_b['id'] = $borrowersBank['borrower_id'];
                            $ans_b['general'] = 0;
                            if ( $borrowersBank['borrower_id'] == $general_borrower_id ) {
                                $ans_b['general'] = 1;
                            }
                            unset($ans_b['borrower_id']);
                            unset($ans_b['bank_id']);
                            unset($ans_b['parent_update_date']);
                            unset($ans_b['first_name']);
                            unset($ans_b['second_name']);
                            unset($ans_b['last_name']);
                            $ans['borrowers'][] = $ans_b;
                        }
                    }

                    $companiesBank = [];
                    $sql_b = $this->db_mysql->query("select c.id, c.name, c.type from companysBankMergeds cb join companys c on c.id = cb.company_id where cb.bank_id = {$orderBank['bank_id']} and (c.regions_id like '%,{$this->order['region']},%' or c.regions_id is null)");
                    while( $row_b = $this->db_mysql->fetch( $sql_b ) ) {
                        $companiesBank[$row_b['id']] = $row_b;
                    }
                    $ans['companies'] = $companiesBank;

                    $answer['banks'][] = $ans;
                }
            }
            // print_r($answer);

            return $answer;
        } else {
            return ['error' => 1, 'error_message' => 'Заявка с данным номером не найдена'];
        }
    }

    public function getFeedInfo() {
        if ( $this->id ) {

            if ( !$this->checkRules() ) {
                return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
            }

            $answer['order']['id'] = $this->id;

            $answer['feed'] = [];

            $feeds = [];
            $sql = $this->db_mysql->query("select f.*, b.name bank_name, u.fio user_fio from feed f left join bankList b on b.id = f.bank_id left join users u on u.id = f.local_user_id where f.order_id = {$this->id}");
            while( $row = $this->db_mysql->fetch( $sql ) ) {
                $feeds[$row['id']] = $row;
            }

            if ( count($feeds) ) {
                foreach ( $feeds as $feed ) {
                    $ans = $feed;
                    $ans['user_id'] = $ans['platform_user_id'];
                    $ans['external_user'] = $ans['user_fio'];
                    $ans['date_format'] = date('Y-m-d H:i:s', $ans['date']);
                    unset($ans['platform_user_id']);
                    unset($ans['platform_id']);
                    unset($ans['seen']);
                    unset($ans['data']);
                    unset($ans['last_msg_by_order']);
                    unset($ans['local_user_id']);

                    $files = [];
                    $sql = $this->db_mysql->query("select f.* from feedFiles ff join files f on f.id = ff.file_id where ff.feed_id = {$feed['id']}");
                    while( $row = $this->db_mysql->fetch( $sql ) ) {
                        $files[] = "https://files.lotinfo.ru/mortgage/{$row['system_name']}.{$row['ext']}";
                    }
                    $ans['files'] = $files;

                    $answer['feed'][] = $ans;
                }
            }

            return $answer;
        } else {
            return ['error' => 1, 'error_message' => 'Заявка с данным номером не найдена'];
        }
    }

    protected function findBy( $data ) {
        $same_order = $this->db_mysql->fetch("select o.id, o.order_status from ordersBorrowers ob left join orders o on o.id = ob.order_id where ob.borrower_id = {$this->borrower_id_general} and ob.borrower_type = 1 order by o.add_date desc limit 1");
        if (!isset($same_order['id'])) {
            return 0;
        }
        $this->same_order_id = $same_order['id'];
        $order = $this->db_mysql->fetch("select o.id, o.order_status from ordersBorrowers ob left join orders o on o.id = ob.order_id where ob.borrower_id = {$this->borrower_id_general} and ob.borrower_type = 1 and o.order_status not in (6,7)");
        if (isset($order['id'])) {
            $this->id = $order['id'];
            return $order['id'];
        }

        return 0;
    }

    protected function add( $data ) {
        $data['add_date'] = time();
        $data['update_date'] = time();
        return $this->db_mysql->insert('orders', $data);
    }

    protected function update( $data ) {
        $data['update_date'] = time();
        unset($data['id']);
        $this->db_mysql->update('orders', $data, "id = {$this->id}");
    }

    public function checkRules() {
        $orderInPlatform = $this->db_mysql->fetch("select id from platformsOrders where order_id = {$this->id} and platform_id = ".PLATFORMID." limit 1");
        if ( isset($orderInPlatform['id']) ) {
            return true;
        } else {
            return false;
        }
    }

    protected function saveOrder() {

        if ( isset($this->data['order']['id']) ) {
            $this->external_id = $this->data['order']['id'];
        }
        unset($this->data['order']['id']);

        if ( $this->id && $this->order['order_status'] != 4 ) {
            return ['error' => 1, 'error_message' => 'Невозможно отредактировать заявку с текущим статусом', "id" => $this->id];
        }

        $update = [];
        if ( isset($this->data['order']) )
        foreach ( $this->data['order'] as $key_1 => $val_1 ) {
            if ( is_array( $val_1 ) ) {
                foreach ( $val_1 as $key_2 => $val_2 ) {
                    $update["{$key_1}_{$key_2}"] = $val_2;
                }
            } else {
                $update["{$key_1}"] = $val_1;
            }
        }

        $update = checkByTableFields($this->db_mysql, "orders", $update);

        if ( !$this->id ) {
            $this->findBy($update);
            if ( $this->id ) {
                return ['error' => 1, 'error_message' => 'Данная заявка уже находится в работе', 'id' => $this->id];
            }
        }

        if ( $this->same_order_id && $this->same_order_id != $this->id ) {
            $update['same_order_id'] = $this->same_order_id;
        }

        if ( !$this->id ) {
            $update['order_status'] = 1;
            $this->id = $this->add($update);
        } else {
            $this->update($update);
        }

        $order = $this->db_mysql->fetch("select * from orders where id = '{$this->id}'");
        if ( isset($order['id']) ) {
            $this->order = $order;
        }

        if ( !$this->id ) {
            return ['error' => 1, 'error_message' => 'Не удалось создать анкету'];
        }

        return ['id' => $this->id, 'external_id' => $this->external_id];
    }

    protected function saveOrdersBorrowers() {
        if ( $this->id and count($this->borrowers_ids) and !$this->bank_id ) {
            $this->db_mysql->query("delete from ordersBorrowers where order_id = {$this->id} and borrower_id not in (" . implode(',', $this->borrowers_ids) . ")");
            foreach ( $this->borrowers_ids as $borrower_id ) {
                $ordBor = $this->db_mysql->fetch("select id from ordersBorrowers where order_id = {$this->id} and borrower_id = {$borrower_id}");
                if ( !isset($ordBor['id']) ) {
                    $this->db_mysql->insert("ordersBorrowers", ['date' => time(), 'order_id' => $this->id, 'borrower_id' => $borrower_id, 'borrower_type' => $this->borrowers_types[$borrower_id], 'use_money' => $this->borrowers_use_money[$borrower_id]]);
                }
            }
        }
    }

    protected function saveUsersOrders() {
        if ( $this->id and count($this->borrowers_ids) ) {
            foreach ( $this->borrowers_ids as $borrower_id ) {
                $ordBor = $this->db_mysql->fetch("select id from platformsUsersOrders where order_id = {$this->id} and platform_user_id = {$this->platform_user}");
                if ( !isset($ordBor['id']) ) {
                    $this->db_mysql->insert("platformsUsersOrders", ['order_id' => $this->id, 'platform_user_id' => $this->platform_user, 'date' => time()]);
                }
            }
        }
    }

    protected function savePlatformsOrders() {
        if ( $this->id ) {
            $ordPlatform = $this->db_mysql->fetch("select id from platformsOrders where order_id = {$this->id} and platform_id = " . PLATFORMID);
            if (!isset($ordPlatform['id'])) {
                $this->db_mysql->insert("platformsOrders", ['order_id' => $this->id, 'platform_id' => PLATFORMID, 'date' => time(), 'external_id' => $this->external_id]);
            }
        }
    }

    protected function saveFiles( $files, $borrower_id = 'null' ) {

        $amWb = new ampq;

        $amWb->connectInfo = array(
            array('host' => "192.168.1.1", 'port' => 5777, 'vhost' => "/", "user" => 'jesus', "password" => 'common' ),
            array('host' => "192.168.3.1", 'port' => 5777, 'vhost' => "/", "user" => 'jesus', "password" => 'common' )
        );

//        include "/home/repo/parsers/wbRebbitList.php";
//        $amWb->connectInfo = $amWbConfig;

        foreach ( $files as $type =>$files_arr ) {
            foreach ($files_arr as $data) {
                $md5 = md5($data['data']);
                $ext = ((isset($data['ext']) && strlen($data['ext']))?$data['ext']:'jpg');

                $thisFile = $this->db_mysql->fetch("select id from files where system_name = '{$md5}' and type = '{$type}'");
                if (!isset($thisFile['id'])) {

                    $thisFile['id'] = $this->db_mysql->insert('files', ['date' => time(), 'system_name' => $md5, 'ext' => $ext, 'client_id' => CLIENTID, 'type' => $type]);

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
                    $ordFile = $this->db_mysql->fetch("select id from ordersFiles where order_id " . (($this->id) ? " = {$this->id}" : 'is null') . " and file_id = {$thisFile['id']} and borrower_id " . (($borrower_id == 'null') ? " is null " : " = {$borrower_id}"));
                    if (!isset($ordFile['id'])) {
                        $this->db_mysql->insert("ordersFiles", ['order_id' => $this->id, 'file_id' => $thisFile['id'], 'date' => time(), 'borrower_id' => $borrower_id]);
                    }
                }
            }
        }

        $amWb->disconnect();
    }

    public function getId() {
        return $this->id;
    }
}