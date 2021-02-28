<?php

class Validator {
    public $errors = [];
    public $data = [];

     function __construct(){
     }

    public function checkOrder( $data ) {
         $this->errors = [];

         if ( !isset($data['borrowers']) || !is_array($data['borrowers']) || !count($data['borrowers']) ) {
             $this->errors[] = 'Данные заемщиков обязательны для заполнения';
         }
         if ( !isset($data['order']) ) {
             $this->errors[] = 'Данные по кредиту обязательны для заполнения';
         }
         if ( count($this->errors) ) {
             return $data;
         }

         foreach ( $data['borrowers'] as $key => $borrower ) {
             $data['borrowers'][$key] = $this->checkValidate( $this->getBorrowerValidate($borrower), $borrower, "borrowers.".$key );
         }

         $data['order'] = $this->checkValidate( $this->getOrderValidate($data['order']), $data['order'] );

        $this->data = $data;
        return $this->data;
    }

    protected function getOrderValidate( $data ) {
        return [
            'sum' => ['required' => true, 'minlength' => 5, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите сумму кредита'],
            'period' => ['required' => true, 'minlength' => 2, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите период кредитования'],
            'region' => ['required' => true, 'minlength' => 1, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите регион'],
            'target' => ['required' => true, 'minlength' => 1, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите цель кредита'],
            'first_pay' => ['required' => true, 'minlength' => 5, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите сумму взноса'],
            'object_price' => ['required' => true, 'minlength' => 5, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите стоимость объекта недвижимости'],
            'matcap' => ['preg_replace' => ['/\s+/', '']],
            'subsidy' => ['preg_replace' => ['/\s+/', '']],
            'pay_type' => ['preg_replace' => ['/\s+/', '']],
            'pay_source' => ['required' => true, 'preg_replace' => ['/\s+/', ' '], 'error_message' => 'Укажите источник первого взноса'],
            'insurance_pack' => ['required' => true, 'minlength' => 1, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Укажите пакет страхования'],
            'pay_date' => [],
            'builder' => [],
        ];
    }

    protected function getBorrowerValidate( $data ) {
         return [
             'fio' => ['required' => true],
             'inn' => ['required' => true, 'preg' => '/^[\d]{12}$/', 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Длина ИНН должна быть 12 символов'],
             'snils' => ['required' => true, 'preg' => '/^[\d]{3}\-[\d]{3}\-[\d]{3} [\d]{2}$/', 'error_message' => 'Заполните СНИЛС в формате ХХХ-ХХХ-ХХХ YY'],
             'mobile' => ['required' => true, 'length' => 11, 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Укажите личный номер телефона'],
             'email' => ['required' => true, 'minlength' => 8, 'error_message' => 'Укажите E-mail'],
             'birthday' => ['required' => true, 'eval' => '$checkV = ((strtotime($data) > -946789200 && strtotime($data) < time()-18*365*24*3600)?1:0);', 'error_message' => 'Укажите корректную дату рождения'],
             'gov_duty' => ['preg_replace' => ['/[^\d]/', ''], 'minlength' => 1, 'error_message' => 'Укажите занимает ли заемщик или его родственник правительственные должности'],
             'childrens' => ['required' => (($data['type'] == 1)?1:0), 'preg_replace' => ['/[^\d]/', ''], 'minlength' => 1, 'error_message' => 'Укажите количество детей до 18 лет'],
             'education' => ['required' => true, 'preg_replace' => ['/[^\d]/', ''], 'minlength' => 1, 'error_message' => 'Укажите образование'],
             'marriage_status' => ['required' => (($data['type'] == 1)?1:0), 'preg_replace' => ['/[^\d]/', ''], 'minlength' => 1, 'error_message' => 'Укажите семейное положение'],
             'gender' => ['required' => 1, 'preg_replace' => ['/[^\d]/', ''], 'minlength' => 1, 'error_message' => 'Укажите пол'],
             'aliments' => ['required' => 0, 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Укажите сумму алиментов в виде числа'],
             'citizenship' => ['required' => true, 'preg_replace' => ['/[^\d]/', ''], 'minlength' => 1, 'error_message' => 'Укажите гражданство'],
             'citizenship_country' => ['required' => (($data['citizenship'] == 2)?1:0), 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Укажите гражданство'],
             'live' => ['type' => 'array', 'childs' => ['address' => ['minlength' => 10, 'error_message' => 'Укажите адрес проживания']]],
             'registration' => ['required' => (($data['type'] == 1)?1:0), 'type' => 'array', 'error_message' => 'Укажите адрес регистрации', 'childs' => ['address' => ['minlength' => 10, 'error_message' => 'Укажите адрес регистрации']]],
             'work' => ['required' => (($data['use_money'] == 1 || !isset($data['use_money']))?1:0), 'type' => 'array', 'error_message' => 'Укажите данные о месте работы',
                 'childs' => [
                     'inn' => ['required' => true, 'minlength' => 8, 'maxlength' => 12, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Заполните ИНН'],
                     'org' => ['required' => true, 'minlength' => 3, 'error_message' => 'Заполните название организации места работы'],
                     'post' => ['required' => true, 'minlength' => 3, 'error_message' => 'Заполните должность'],
                     'age' => ['required' => true, 'minlength' => 1, 'error_message' => 'Заполните время существования организации'],
                     'phone' => ['required' => true, 'length' => 11, 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Заполните номер телефона работодателя. Телефон должен состоять из 11 цифр'],
                     'address' => ['required' => true, 'error_message' => 'Заполните адрес организации'],
                     'site' => [],
                     'sphere' => ['required' => true, 'error_message' => 'Заполните сферу деятельности'],
                     'contract_type' => ['required' => true, 'error_message' => 'Заполните вид трудового договора'],
                     'post_category' => ['required' => true, 'error_message' => 'Заполните категорию занимаемой должности'],
                     'work_exp' => ['required' => true, 'minlength' => 1, 'error_message' => 'Заполните стаж'],
                     'start_work' => ['required' => true, 'preg_replace' => ['/\s+/', ''], 'makeEvalAfterValidate' => '$makedVal = date("Y-m-d", strtotime($makedVal));', 'error_message' => 'Заполните дату начала работы',
                         'validates' => [
                             ['eval' => '$checkV = ((strtotime($data) > 473367600 && strtotime($data) < time())?1:0);', 'error_message' => 'Укажите верную дату начала работы'],
                         ]
                     ],
                 ],
             ],
             'income' => ['required' => (($data['use_money'] == 1 || !isset($data['use_money']))?1:0), 'type' => 'array', 'error_message' => 'Укажите данные о доходе',
                 'childs' => [
                     'asset' => ['preg_replace' => ['/[^\d]/', '']],
                     'additional' => ['preg_replace' => ['/[^\d]/', '']],
                     'outlay' => ['required' => true, 'minlength' => 1, 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Заполните сумму ежемесячных расходов'],
                     'monthly' => ['required' => true, 'minlength' => 1, 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Заполните сумму ежемесячных доходов'],
                     'bankrupt' => ['required' => true, 'minlength' => 1, 'preg_replace' => ['/[^\d]/', ''], 'error_message' => 'Укажите применялась ли процедура банкротства'],
                     'bank_name' => ['required' => (( isset($data['income']['confirm']) && $data['income']['confirm'] == 1)?1:0), 'minlength' => 1, 'error_message' => 'Укажите наименование банка зарплатного проекта'],
                ],
             ],
             'passport' => [
                 'required' => true,
                 'type' => 'array',
                 'error_message' => 'Заполните паспортные данные',
                 'childs' => [
                     'serial' => ['required' => true, 'preg' => '/^[\d]{10}$/', 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Серия и номер паспорта должна содежать 10 цифр'],
                     'take_date' => ['required' => true, 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Заполните дату выдачи паспорта', 'makeEvalAfterValidate' => '$makedVal = date("Y-m-d", strtotime($makedVal));','eval' => '$checkV = ((strtotime($data) > 473367600 && strtotime($data) < time())?1:0);'],
                     'given' => ['required' => true, 'error_message' => 'Заполните место выдачи паспорта'],
                     'birthplace' => ['required' => true, 'minlength' => 5, 'error_message' => 'Заполните место рождения'],
                     'unit_code' => ['preg' => '/^[\d]{3}-[\d]{3}$/', 'error_message' => 'Код подразделения должен быть в формате 999-999'],
                 ]
             ],
         ];
    }

    // protected function getAddOrderValidate() { // old
    //     return [
    //         'passport' => [
    //             'required' => true,
    //             'type' => 'array',
    //             'error_message' => 'Заполните паспортные данные',
    //             'childs' => [
    //                 'seria' => ['required' => true, 'preg' => '/^[\d]{4}$/', 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Серия паспорта должна состоять из 4 цифр'],
    //                 'number' => ['required' => true, 'preg' => '/^[\d]{6}$/', 'preg_replace' => ['/\s+/', ''], 'error_message' => 'Номер паспорта долежн состоять из 6 цифр'],
    //                 'date' => ['required' => true, 'preg_replace' => ['/\s+/', ''], 'name' => 'Заполните дату выдачи паспорта', 'makeEvalAfterValidate' => '$makedVal = date("Y-m-d", strtotime($makedVal));',
    //                     'validates' => [
    //                         ['preg' => '/^[\d]{2}\.[\d]{2}\.[\d]{4}$/', 'error_message' => 'Заполните дату выдачи паспорта в формате 01.01.2000'],
    //                         ['eval' => '$checkV = ((strtotime($data) > 473367600 && strtotime($data) < time())?1:0);', 'error_message' => 'Укажите верную дату выдачи паспорта'],
    //                     ]
    //                  ],
    //                 'place' => ['required' => true, 'error_message' => 'Заполните место выдачи паспорта'],
    //                 'placeCode' => ['preg' => '/^[\d]{3}-[\d]{3}$/', 'error_message' => 'Код подразделения должен быть в формате 999-999'],
    //             ]
    //         ]
    //     ];
    // }

    protected function checkValidate ( $validates, $data, $key = '' ) {
         foreach ( $validates as $name => $item ) {
             $data[$name] = $this->checkValidateItem( $name, $item, $data, $name, $key );
         }
         return $data;
    }

    protected function checkValidateItem( $name, $validateItem, $data, $error_name = '', $key = '' ) {
         if ( strlen($key) ) $key = $key.".";
         if ( !isset($data[$name]) && isset($validateItem['required']) && $validateItem['required'] ) {
             if ( isset($validateItem['error_message']) ) {
                 $this->errors[$key.(($error_name)?$error_name:$name)] = $validateItem['error_message'];
             }
         } elseif ( isset($data[$name]) ) {
            if ( isset($validateItem['childs']) && count($validateItem['childs']) ) {
                foreach ( $validateItem['childs'] as $nameChild => $validateItemChild ) {
                    $data[$name][$nameChild] = $this->checkValidateItem( $nameChild, $validateItemChild, $data[$name], $key.(($error_name)?$error_name.".":'').$nameChild );
                }
            } else {
                $data[$name] = trim($data[$name]);
                if ( isset($validateItem['preg_replace']) and is_array($validateItem['preg_replace']) && count($validateItem['preg_replace']) > 1 ) {
                    $data[$name] = preg_replace($validateItem['preg_replace'][0], $validateItem['preg_replace'][1], $data[$name]);
                }
                if ( isset($validateItem['makeEvalBeforeValidate']) ) {
                    $makedVal = $data[$name];
                    eval($validateItem['makeEvalBeforeValidate']);
                    $data[$name] = $makedVal;
                }
                if ( isset($data[$name]) ) {
                    $this->useValidates($validateItem, $data[$name], (($error_name) ? $error_name : $name), $key);
                    if (isset($validateItem['makeEvalAfterValidate'])) {
                        $makedVal = $data[$name];
                        eval($validateItem['makeEvalAfterValidate']);
                        $data[$name] = $makedVal;
                    }
                }
            }
         }
         return $data[$name];
    }

    protected function useValidates($validateItem, $data, $error_name = '', $key = '') {
        if ( isset($validateItem['preg']) && !preg_match($validateItem['preg'], $data) ) {
            $this->errors[$key.$error_name] = $validateItem['error_message'];
        }
        if ( isset($validateItem['minlength']) && strlen($data) < $validateItem['minlength'] ) {
            $this->errors[$key.$error_name] = $validateItem['error_message'];
        }
        if ( isset($validateItem['maxlength']) && strlen($data) > $validateItem['maxlength'] ) {
            $this->errors[$key.$error_name] = $validateItem['error_message'];
        }
        if ( isset($validateItem['length']) && strlen($data) != $validateItem['length'] ) {
            $this->errors[$key.$error_name] = $validateItem['error_message'];
        }
        if ( isset($validateItem['eval']) ) {
            eval($validateItem['eval']);
            if ( !$checkV ) {
                $this->errors[$key.$error_name] = $validateItem['error_message'];
            }
        }
        if ( isset($validateItem['validates']) && is_array($validateItem['validates']) && count($validateItem['validates']) ) {
            foreach ( $validateItem['validates'] as $validate ) {
                $this->useValidates($validate, $data, $error_name);
            }
        }
    }
}




//        'validates' => [
//            ['minlength' => 10, 'error_message' => 'Длина места должна быть от 10 символов'],
//            ['maxlength' => 10, 'error_message' => 'Длина места должна быть до 10 символов'],
//            ['length' => 10, 'error_message' => 'Длина места должна быть 10 символов']
//            ['preg' => '/^[\d]{2}\.[\d]{2}\.[\d]{4}$/', 'error_message' => 'Заполните дату выдачи паспорта в формате 01.01.2000'],
//            ['eval' => '$checkV = ((strtotime($data) > 473367600 && strtotime($data) < time())?1:0);', 'error_message' => 'Укажите верную дату выдачи паспорта'],
//        ]