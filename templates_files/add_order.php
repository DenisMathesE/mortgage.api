<?php

require_once("/home/repo/mortgage/config.php");
require_once("/home/repo/mortgage/mysql.php");

define("TEMPLATE_DIR", "/home/repo/mortgage/bank_templates/");

$db_mysql = new db;
$db_mysql->connect(1);

$body['order_id'] = 256;
$body['bank_id'] = 1;

$order = $db_mysql->fetch("select * from ordersBanks where order_id = {$body['order_id']} and bank_id = {$body['bank_id']}");
$borrowers = [];
$main_borrower_id = [];
$sql = $db_mysql->query("select * from borrowersBanks where order_id = {$body['order_id']} and bank_id = {$body['bank_id']} order by borrower_type");
while ($row = $db_mysql->fetch($sql)) {
    $borrowers[$row['id']] = $row;
    if ( $row['borrower_type'] == 1 ) {
        $main_borrower_id = $row['id'];
    }
}

$fields = [];
$sql = $db_mysql->query("select f.name, fc.key, fc.value from fields f join fieldsContent fc on fc.fieldId = f.id");
while ($row = $db_mysql->fetch($sql)) {
    $fields[$row['name']][$row['key']] = $row['value'];
}

$borrowersHtml = "";
foreach ($borrowers as $borrower) {
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

    $borrowerFields = [];
    foreach ($borrower as $key => $val) {
        if (isset($fields[$key]) && isset($fields[$key][$val])) {
            $borrowerFields[$key] = $fields[$key][$val];
        } else {
            $borrowerFields[$key] = $val;
        }
    }

    if ( trim($borrowerFields['marriage_status']) == '0' ) {
        $borrowerFields['marriage_status'] = '';
    }

    $borrowersHtml .= getTemplateHtml(['borrower' => $borrowerFields, 'borrower_source' => $borrower], $body['bank_id'], 'add_order_borrower');
}

$orderFields = [];
foreach ($order as $key => $val) {
    if (isset($fields[$key]) && isset($fields[$key][$val])) {
        $orderFields[$key] = $fields[$key][$val];
    } else {
        $orderFields[$key] = $val;
    }
}

$html = $sendingBody = getTemplateHtml(['order' => $orderFields, 'order_source' => $order, 'borrowersHtml' => $borrowersHtml, 'borrower' => $borrowers[$main_borrower_id]], $body['bank_id'], 'add_order_main'); 

if ( isset($_GET['pdf']) ) {
    require_once("/var/www/lotinfo.ru/crm/prod/include/billing/dompdf/dompdf_config.inc.php");
    def("DOMPDF_DPI", 300);

    $dompdf   = new DOMPDF();
    $dompdf->set_paper("a4");
    $dompdf->load_html($html);
    $dompdf->render();
    $output   = $dompdf->output();

    header("Content-type: application/pdf");
    echo $output;
} else {
    echo $sendingBody . "\n";
}



function getTemplateHtml( $args, $bank_id, $file ) {
    extract($args);
    if ( file_exists(TEMPLATE_DIR.$bank_id."/".$file.".php") ) {
        $file = TEMPLATE_DIR.$bank_id."/".$file.".php";
    } elseif ( file_exists(TEMPLATE_DIR."default/".$file.".php") ) {
        $file = TEMPLATE_DIR."default/".$file.".php";
    } else {
        return "";
    }
    ob_start();
    include($file);
    $out = ob_get_contents();
    ob_end_clean();
    return $out;
}