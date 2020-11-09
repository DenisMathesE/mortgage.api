<?php

class Action {
    public $path;
    protected $db_mysql;
    protected $auth;
    public $am;
    protected $sendDataWebhooks;

    function __construct($db_mysql, $auth) {

        $this->db_mysql = $db_mysql;
        $this->auth = $auth;

        list($f_path) = explode('?', substr($_SERVER['REQUEST_URI'],1));
        list($f_path) = explode('&', $f_path);
        $this->path = explode('/', preg_replace("/\/\/+/","/",$f_path) );
    }

    public function start() {
        if ( $action = $this->getPathItem(0) ) {
            $action = "action_".$action;
            $data = $this->$action();

            if ( count($this->sendDataWebhooks) ) {
                foreach ($this->sendDataWebhooks as $sendData) {
                    $this->am->sendTask($sendData, 'mortgageWebhooksTasks');
                }
                $this->sendDataWebhooks = [];
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: POST');
            header("Content-type: application/json; charset=utf-8");

            $json_answer = str_replace( '"{', '{', str_replace( '}"', '}',json_encode($data, JSON_UNESCAPED_UNICODE)));
            echo $json_answer;
        }

        $this->end();
    }

    protected function action_saveorder() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"borrowers":[{"fio":"Svetov Denis G","inn":"123456789012","live":{"address":"г Екатеринбург, пр-кт Академика Сахарова, д 53"},"type":1,"work":{"inn":"123456789012","org":"ООО ооо","post":"Сидатель","phone":"+7 902 875 56 04","sphere":"2","workers":"2","work_exp":"100","start_work":"2016-3-01","contract_type":"1","post_category":"2"},"files":{"passport":[{"ext":"png","data":"iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAAXNSR0IB2cksfwAACJlJREFUeJyVVllv3NYZ5b7v5MxwRhpJlq3Fu+ykioMmbmIg6EOCoA9F+9a\/Yv+K\/oC+JkBbFEXstg+ukzipK9txLMuybC0jjWbnkBxe7pdkKcMFktRO4w8gX+4FD+93zznfwYuiuPbqunr16o+svthz7cf24OWDvKKSJAmdfpqmeYEgKIY+r\/8uFlmWwTQNA5AAG8FJgiBe+pFXApQnA+4ovvupd\/8W6LcQnskxtkCLrMTL8yhMgLUbPLvvPrxpb96l68cZXvoO\/E8AiKLQ2bgN1m8JoO88+dYfPB48Xc8Jurf5jySKrG\/+1v7k97tb32zd+Gd7c0s2eH7uDEmQrwEQAGf45R+DwT6jKEGckYqQ9TuQYu7c\/ZKCCWhtu62WRuEEhuEFNESSP\/U2zXD\/e4iXA2R5Ntl71Lrzd4FiiiAgJSV0waFN3f7r7YvvvXnjs2+ZuFheMjjoJxhGiVz\/oF09c45RGziO\/3+AJI6c0TA63Ei2t7A4wgQuC4JeJnQ2DxWSbC40utuHeW9okTxL4jzPd31UqVcIWSOUGkGQ+Pcb9UOAkhuuPbx\/4w9oOthY7+91QkJScU2dk+SzZqXWUNX56gkSWZmZLmhm+uLy7YedPMQfDjLdZCedDbl2jGIF5KhT6PcAiiIPwsCf2IPNtXB\/3enuD10HtK1jJHLRrEoFSTV0z88DJAslzrcjSGqNWY1wknnPa\/dHMS025kRraHMkEjmTAkMRgi7bVQIdAcRJDOyRv\/m5\/c1158lXzsG+sw9aj1url0\/NNCpDlM8BxAQWi8OQFjcHbRoVdeAVGhdv93ZgPmsyC28td+8fDHd6ctLxv75etDcShihIDiNpvNRqaA\/A+mejm3+yu63CD8cg3xsHp9G0KqHs6aUYYogfewBkHF4JJs8Sf4nAAhZPvSD2c9wUtVMzfOD3n\/XDMFJn+Hxoj1zH3Vu3rbY8NV+e4GoC08G\/bu18vSYQtFVgFE0fO9Y8QYsDUsLy\/Iutx5xKT9PUA8+S6WKuMQO90ZoXnNK4EvL2qC9D1B\/4Z5vNcRy4gyGKJT6OcG6Uakbj\/HtHLcrzIrN73uY9VWZojqHyomnKSMEhCBNM4oop7Y6Hi3UNFkSR5EjfispLkyWTJvayuKrIpAOpnJQEBkrovfuPpikco5hJlMxeWOXnLxwBlDee81zW22BoqhNlVi5OcwJeNzAcZp5fZbHTc3PhJI2D9NF+HxlOumGo0jyZIjonKDlLBr5gyoUu4yBEzTqOpsB16tON2tsfUbKJl4ZZsipDSWfQdp3AcdGsbzVIZuIjRJQkCnV30CtKamcY2BuMwqI9dkZeXM8QgGYZQzyNbUaWBZD6fZdLY7A\/OIwJvaY1Ll4Qli9TDPeCpjlSRFjJ1fDrmxtZ6GkCLAQFkbUKCk2ZJWG2cTA6sDwFzzCakBmiGyN2ENI8M4XnaopMErbtuxQW7+73ra5lnFyqvnVFMGZKH3neIhSFSbz15V+ePrm38v6FheVmbXGGmtY3rXYXiwyxxsQ564TdSYQSeLDeygQ+ybMViZ3SVUKVvoVgx7dWLi0LhqI1Neb8Qkz4MISV2VMoThyxqDT7vDR3GLN5eu\/Tz6ET6Sg56jl8QjdSiEEQYeiD1gDiFEtTuC6laeJGsWiqdEVgI0+JoEpVMz\/LW9vr97f3NroixR6\/9D6nNV8IrbR+P\/SHD276O7txmFUpvlFtBr1hJQADmtmMfUwQxRDywN9x\/LKfMUqeE1i5bvB19dbOocZLuuVDWsGTxO5arKI3TI6iEXZqCcPII4DSf8L+s8M\/f5JPBvNnZgHHEAk0lmcRU8Y9sCiKOkOKHMtWNZyl5vLcLyBXUxebJp0VJyS+8HN6oclRRRL6A4pWhBg9PIi8ET21TAjac6uI4+DZmnewgZC0gCMplhWGJMV43JlEXhSy+AEaizkKaULSJEPgeY1hVYaG4VBgUpBldkGAhNfFUMb7T5\/m7pjiUNsF0uw8a54oaXrNB3b7q+vUqBcjBEfmMM4dK9Uh2+UltcE\/mAzKcSJM\/M0QaElC1gwmj56EwQxOAAtsIcA0RCtMWSfojYY6C0maC7wsyhHWkPTFnx15EQRO1nk8aR0GA2uEIpkX98dREYB6nUNqolCw9DhGwkjQdQUlvhq1KxmNJlCgeL7jsicWSBatpPb+Xv\/u7qBWE0Pg+2NXJTC9XlWWV\/AycpTWSprH2ZpCimgYRSRJDZ0EZRlj0Sh6zmQMmSxXmhViDByUdxDAcrUaAFSjEvU9N0FlMqfq1RtfPMAy7PiMrKIpLUszv\/qNculj5kjJ166ViYPmRMo4xh2\/YM6fQAVFmuZJg7pzpwu2OycFhuNoTKO9nuPGYOXyeWzkWB5kOIwVaDP0dtd3rq89vfK7K4wuTJ08a1z+0PjFr8WFS4JSKf\/1hZJLwhIkWU5tQqoVvHr4aK0\/9LqHNg+j7TKqSHjpPMRwmMcTZaYadgYKivlxNJGZtScdywV+lMoNPULxxSu\/FadOs6JGUSSKYj8cmUe6wHGSpo3ZRYNDVcxdrhIImaTOsAvx9YfbO+PInNYe\/Xt3f3c\/4FgCjNW6qAqYrrKLP393bvVDUTMpmnmeLb4\/Mr9b5eCmGDHwnMn6XTv0LCdsbQ\/PLTQ8XNxLsMWLczuP25DhGxUCQ+JSpOlgWFFZ492PlcYyRTE\/KbaUMTGBib9+R4YB5Dme5\/w0o2VVaFQ1ocwZxlRVSHp7LIVW8MKNEWF6WrrwAcu9JNy9IniV+wiGoqC9vxmMx2U4LTXWPLN0cvUtJPDn3ljNQqfwvc4QSGgB00x684qxtEqSr5PscJyIcMY+aCHVunL87MI7vzTPXear89rsaVafl8t3fVqfMr00l+YXau98xMv662XTI15RjDa\/WF953zy5KpmzNCeUtCMotuQbxfKc3uRnzuon35DnF1hl6qXBtKz\/AGloyO6ozT\/dAAAAAElFTkSuQmCC"}],"income_confirm":[{"ext":"png","data":"iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAAXNSR0IB2cksfwAACJlJREFUeJyVVllv3NYZ5b7v5MxwRhpJlq3Fu+ykioMmbmIg6EOCoA9F+9a\/Yv+K\/oC+JkBbFEXstg+ukzipK9txLMuybC0jjWbnkBxe7pdkKcMFktRO4w8gX+4FD+93zznfwYuiuPbqunr16o+svthz7cf24OWDvKKSJAmdfpqmeYEgKIY+r\/8uFlmWwTQNA5AAG8FJgiBe+pFXApQnA+4ovvupd\/8W6LcQnskxtkCLrMTL8yhMgLUbPLvvPrxpb96l68cZXvoO\/E8AiKLQ2bgN1m8JoO88+dYfPB48Xc8Jurf5jySKrG\/+1v7k97tb32zd+Gd7c0s2eH7uDEmQrwEQAGf45R+DwT6jKEGckYqQ9TuQYu7c\/ZKCCWhtu62WRuEEhuEFNESSP\/U2zXD\/e4iXA2R5Ntl71Lrzd4FiiiAgJSV0waFN3f7r7YvvvXnjs2+ZuFheMjjoJxhGiVz\/oF09c45RGziO\/3+AJI6c0TA63Ei2t7A4wgQuC4JeJnQ2DxWSbC40utuHeW9okTxL4jzPd31UqVcIWSOUGkGQ+Pcb9UOAkhuuPbx\/4w9oOthY7+91QkJScU2dk+SzZqXWUNX56gkSWZmZLmhm+uLy7YedPMQfDjLdZCedDbl2jGIF5KhT6PcAiiIPwsCf2IPNtXB\/3enuD10HtK1jJHLRrEoFSTV0z88DJAslzrcjSGqNWY1wknnPa\/dHMS025kRraHMkEjmTAkMRgi7bVQIdAcRJDOyRv\/m5\/c1158lXzsG+sw9aj1url0\/NNCpDlM8BxAQWi8OQFjcHbRoVdeAVGhdv93ZgPmsyC28td+8fDHd6ctLxv75etDcShihIDiNpvNRqaA\/A+mejm3+yu63CD8cg3xsHp9G0KqHs6aUYYogfewBkHF4JJs8Sf4nAAhZPvSD2c9wUtVMzfOD3n\/XDMFJn+Hxoj1zH3Vu3rbY8NV+e4GoC08G\/bu18vSYQtFVgFE0fO9Y8QYsDUsLy\/Iutx5xKT9PUA8+S6WKuMQO90ZoXnNK4EvL2qC9D1B\/4Z5vNcRy4gyGKJT6OcG6Uakbj\/HtHLcrzIrN73uY9VWZojqHyomnKSMEhCBNM4oop7Y6Hi3UNFkSR5EjfispLkyWTJvayuKrIpAOpnJQEBkrovfuPpikco5hJlMxeWOXnLxwBlDee81zW22BoqhNlVi5OcwJeNzAcZp5fZbHTc3PhJI2D9NF+HxlOumGo0jyZIjonKDlLBr5gyoUu4yBEzTqOpsB16tON2tsfUbKJl4ZZsipDSWfQdp3AcdGsbzVIZuIjRJQkCnV30CtKamcY2BuMwqI9dkZeXM8QgGYZQzyNbUaWBZD6fZdLY7A\/OIwJvaY1Ll4Qli9TDPeCpjlSRFjJ1fDrmxtZ6GkCLAQFkbUKCk2ZJWG2cTA6sDwFzzCakBmiGyN2ENI8M4XnaopMErbtuxQW7+73ra5lnFyqvnVFMGZKH3neIhSFSbz15V+ePrm38v6FheVmbXGGmtY3rXYXiwyxxsQ564TdSYQSeLDeygQ+ybMViZ3SVUKVvoVgx7dWLi0LhqI1Neb8Qkz4MISV2VMoThyxqDT7vDR3GLN5eu\/Tz6ET6Sg56jl8QjdSiEEQYeiD1gDiFEtTuC6laeJGsWiqdEVgI0+JoEpVMz\/LW9vr97f3NroixR6\/9D6nNV8IrbR+P\/SHD276O7txmFUpvlFtBr1hJQADmtmMfUwQxRDywN9x\/LKfMUqeE1i5bvB19dbOocZLuuVDWsGTxO5arKI3TI6iEXZqCcPII4DSf8L+s8M\/f5JPBvNnZgHHEAk0lmcRU8Y9sCiKOkOKHMtWNZyl5vLcLyBXUxebJp0VJyS+8HN6oclRRRL6A4pWhBg9PIi8ET21TAjac6uI4+DZmnewgZC0gCMplhWGJMV43JlEXhSy+AEaizkKaULSJEPgeY1hVYaG4VBgUpBldkGAhNfFUMb7T5\/m7pjiUNsF0uw8a54oaXrNB3b7q+vUqBcjBEfmMM4dK9Uh2+UltcE\/mAzKcSJM\/M0QaElC1gwmj56EwQxOAAtsIcA0RCtMWSfojYY6C0maC7wsyhHWkPTFnx15EQRO1nk8aR0GA2uEIpkX98dREYB6nUNqolCw9DhGwkjQdQUlvhq1KxmNJlCgeL7jsicWSBatpPb+Xv\/u7qBWE0Pg+2NXJTC9XlWWV\/AycpTWSprH2ZpCimgYRSRJDZ0EZRlj0Sh6zmQMmSxXmhViDByUdxDAcrUaAFSjEvU9N0FlMqfq1RtfPMAy7PiMrKIpLUszv\/qNculj5kjJ166ViYPmRMo4xh2\/YM6fQAVFmuZJg7pzpwu2OycFhuNoTKO9nuPGYOXyeWzkWB5kOIwVaDP0dtd3rq89vfK7K4wuTJ08a1z+0PjFr8WFS4JSKf\/1hZJLwhIkWU5tQqoVvHr4aK0\/9LqHNg+j7TKqSHjpPMRwmMcTZaYadgYKivlxNJGZtScdywV+lMoNPULxxSu\/FadOs6JGUSSKYj8cmUe6wHGSpo3ZRYNDVcxdrhIImaTOsAvx9YfbO+PInNYe\/Xt3f3c\/4FgCjNW6qAqYrrKLP393bvVDUTMpmnmeLb4\/Mr9b5eCmGDHwnMn6XTv0LCdsbQ\/PLTQ8XNxLsMWLczuP25DhGxUCQ+JSpOlgWFFZ492PlcYyRTE\/KbaUMTGBib9+R4YB5Dme5\/w0o2VVaFQ1ocwZxlRVSHp7LIVW8MKNEWF6WrrwAcu9JNy9IniV+wiGoqC9vxmMx2U4LTXWPLN0cvUtJPDn3ljNQqfwvc4QSGgB00x684qxtEqSr5PscJyIcMY+aCHVunL87MI7vzTPXear89rsaVafl8t3fVqfMr00l+YXau98xMv662XTI15RjDa\/WF953zy5KpmzNCeUtCMotuQbxfKc3uRnzuon35DnF1hl6qXBtKz\/AGloyO6ozT\/dAAAAAElFTkSuQmCC"}]},"snils":"999-999-999 12","income":{"asset":"0","outlay":"100000","confirm":"1","monthly":"140000","bankrupt":"2","bank_name":"Некий","additional":"10000"},"mobile":"+7 965 234 23 43","birthday":"1989-11-12","gov_duty":"1","passport":{"given":"УФМС Родины","serial":"6734 483830","take_date":"2012-12-12","unit_code":"660-007","birthplace":"Камневый водопат"},"childrens":"2","education":"3","citizenship":"1","registration":{"type":"1","address":"ул.Фурманова, д.9, кв.59"},"marriage_status":"1"},{"fio":"Она же","inn":"123456789012","type":2,"snils":"999-999-999 12 ","mobile":"+7 902 875 56 03","birthday":"1999-12-12","gov_duty":"2","passport":{"given":"УФМС Родины","serial":"7698768976","take_date":"2020-01-03","unit_code":"670-098","birthplace":"Средний запад"},"education":"2","use_money":2,"citizenship":"1","fio_changed":"1","family_contract":"2","fio_changed_date":"2019-03-14","fio_changed_reason":"1","fio_changed_last_fio":"длала","fio_changed_custom_reason":""},{"fio":"лорп лорд","inn":"123456789012","type":3,"work":{"inn":"1234567890","org":" гтщшгзтщшгз","post":"фываф ыва","phone":"+7 912 226 62 00","sphere":"2","workers":"1","work_exp":"56","start_work":"2020-1-01","contract_type":"3","post_category":"2"},"snils":"999-999-999 12","income":{"asset":"","outlay":"99","confirm":"2","monthly":"19999","bankrupt":"1","additional":"88"},"mobile":"+7 902 875 56 03","birthday":"1999-12-12","gov_duty":"2","passport":{"given":"ррл орол","serial":"1234567890","take_date":"2012-12-12","unit_code":"456-456","birthplace":"дорд лор"},"education":"4","citizenship":"1","fio_changed":"1","fio_changed_reason":"2","fio_changed_last_fio":" щшгзтщш","fio_changed_custom_reason":"ло лорп"}],"company":{"id":"1","name":"Лот Инфо"},"company_user":{"id":"2567","name":"Светличный Денис Георгиевич","mobile":"79122266200"},"order":{"sum":"3900000","matcap":"","period":"240","region":"6","target":"1","subsidy":"","pay_date":"12 марта","pay_type":"1","first_pay":"100000","object_price":"4000000","id":46, "mortgage_id_":226}}';
//        echo $querySrt1;
        $query = json_decode($querySrt, 1);
        $query1 = $query;

        if ( !count($query) ) {
            $querySrt = substr($querySrt, 3);
            $query = json_decode($querySrt, 1);
        }

//        print_r($query);

        $data = $query;

        $validator = new Validator();
        $data = $validator->checkOrder($data);

        if ( count($validator->errors) ) {
            $answer['error'] = 1;
            $answer['errors'] = $validator->errors;

            $sendData = $answer;
            $sendData['event'] = 'saveOrder';
            $sendData['platform_id'] = PLATFORMID;
            $sendData['order']['external_id'] = $data['order']['id'];

            $this->sendDataWebhooks[] = $sendData;

            return $answer;
        }

        $answer = $this->saveOrder( $data );

        $sendData = $answer;
        $sendData['event'] = 'saveOrder';
        $sendData['platform_id'] = PLATFORMID;

        $this->sendDataWebhooks[] = $sendData;

        return $answer;
    }

    protected function action_closeorder() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"order":{"mortgage_id":256, "bank_id_":1}}';
        $query = json_decode($querySrt, 1);
        $query1 = $query;

        if ( !count($query) ) {
            $querySrt = substr($querySrt, 3);
            $query = json_decode($querySrt, 1);
        }

        $answer = $this->cancelOrder( $query );

        $sendData = $answer;
        $sendData['event'] = 'changeOrderStatus';
        $sendData['platform_id'] = PLATFORMID;

        $this->sendDataWebhooks[] = $sendData;

        return $answer;

//        $sendData = ['order_id' => $this->id, 'bank_id' => $this->bank_id];
//        $this->am->sendTask($sendData, 'mortgageOrdersBanks');
    }

    protected function action_getorderstatus() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"order":{"mortgage_id":241}}';
        $query = json_decode($querySrt, 1);
        $query1 = $query;

        if ( !count($query) ) {
            $querySrt = substr($querySrt, 3);
            $query = json_decode($querySrt, 1);
        }

        $answer = [];
        $data = $query;

        $order = new Order( $this->db_mysql, $data );
        $answer = $order->getOrderStatus();

        return $answer;
    }

    protected function action_getorderstatushistory() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"order":{"mortgage_id":161}}';
        $query = json_decode($querySrt, 1);
        $query1 = $query;

        if ( !count($query) ) {
            $querySrt = substr($querySrt, 3);
            $query = json_decode($querySrt, 1);
        }

        $answer = [];
        $data = $query;

        $order = new Order( $this->db_mysql, $data );
        $answer['order'] = $order->getOrderStatusHistory();

        return $answer;
    }

    protected function action_getorderinfo() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"order":{"mortgage_id":161, "bank_id_":16}}';
        $query = json_decode($querySrt, 1);
        $query1 = $query;

        if ( !count($query) ) {
            $querySrt = substr($querySrt, 3);
            $query = json_decode($querySrt, 1);
        }

        $answer = [];
        $data = $query;

        $order = new Order( $this->db_mysql, $data );
        $answer = $order->getOrderInfo();

        return $answer;
    }

    protected function action_getfeedinfo() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"order":{"mortgage_id":226, "bank_id_":16}}';
        $query = json_decode($querySrt, 1);
        $query1 = $query;

        if ( !count($query) ) {
            $querySrt = substr($querySrt, 3);
            $query = json_decode($querySrt, 1);
        }

        $answer = [];
        $data = $query;

        $order = new Order( $this->db_mysql, $data );
        $answer = $order->getFeedInfo();

        return $answer;
    }

    protected function action_getbanks() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"region":6}';
        $query = json_decode($querySrt, 1);

        $answer = [];

        $answer['banks'] = [];

        $sql = $this->db_mysql->query("select * from bankList ".((isset($query['region']) && $query['region'])?"where regions_id like '%,{$query['region']},%'":''));
        while( $row = $this->db_mysql->fetch( $sql ) ) {
            $regions = explode(',', $row['regions_id']);
            array_shift($regions);
            array_pop($regions);
            $answer['banks'][] = ['id' => $row['id'], 'name' => $row['name'], 'regions' => $regions];
        }

        return $answer;
    }

    protected function action_getcompanies() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt1 = $querySrt = '{"region":6}';
        $query = json_decode($querySrt, 1);

        $answer = [];

        $answer['companies'] = [];

        $sql = $this->db_mysql->query("select * from companys where custom is null ".((isset($query['region']) && $query['region'])?"and regions_id like '%,{$query['region']},%'":''));
        while( $row = $this->db_mysql->fetch( $sql ) ) {
            $regions = explode(',', $row['regions_id']);
            array_shift($regions);
            array_pop($regions);
            $answer['companies'][] = ['id' => $row['id'], 'name' => $row['name'], 'regions' => $regions, 'type' => $row['type']];
        }

        return $answer;
    }

    protected function action_sendmessagetobank() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
        $query = json_decode($querySrt, 1);

        $answer = $this->sendMessageToBank( $query );

        return $answer;
    }

    protected function action_setcompany() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt = '{"mortgage_id":"161","bank_id":"1","company_id":"3","type":"appraisal","custom":"Кастомная оценочкая контора"}'; // appraisal, insurance
        $query = json_decode($querySrt, 1);

        $answer = $this->setCompany( $query );

        return $answer;
    }

    protected function action_setdealdate() {
        $querySrt1 = $querySrt = file_get_contents('php://input');
//        $querySrt = '{"mortgage_id":"161","bank_id":"1","deal_date":"1591297200"}';
        $query = json_decode($querySrt, 1);

        $answer = $this->setDealDate( $query );

        return $answer;
    }

    public function getPathItem($key) {
        if ( isset($this->path[$key]) and $this->path[$key] ) {
            return $this->path[$key];
        }
        return '';
    }

    public function saveOrder( $data ) {
        $order = new Order( $this->db_mysql, $data );
        $order->am = $this->am;
        return $order->save();
    }

    public function setCompany( $data ) {
        $order = new Order( $this->db_mysql, $data );
        $order->am = $this->am;
        return $order->setCompany();
    }

    public function setDealDate( $data ) {
        $order = new Order( $this->db_mysql, $data );
        $order->am = $this->am;
        return $order->setDealDate();
    }

    public function cancelOrder( $data ) {
        $order = new Order( $this->db_mysql, $data );
        $order->am = $this->am;
        return $order->cancel();
    }

    public function sendMessageToBank( $data ) {

        $order = new Order( $this->db_mysql, ['order' => ['mortgage_id' => $data['mortgage_id']]] );

        if ( !$data['mortgage_id'] ) {
            return ['error' => 1, 'error_message' => ''];
        }

        if ( !$order->checkRules() ) {
            return ['error' => 1, 'error_message' => 'У Вас нет прав на просмотр заявки'];
        }

        $feed = new Feed( $this->db_mysql, $data );
        $feed->am = $this->am;
        return $feed->save();
//        $order = new Order( $this->db_mysql, $data );
//        $order->am = $this->am;
//        return $order->save();
    }

    public function end() {
        $this->db_mysql->disconnect();
        $this->am->disconnect();
    }


}