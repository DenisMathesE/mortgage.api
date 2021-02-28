<table style="background: #999;" cellspacing="1" cellpadding="5">
    <tr style="background: #EEE;">
        <th colspan="2">Данные по кредиту
    <tr style="background: #FFF;">
        <th align="right">Цель кредита
        <td align="left"><?=$order['target']?>
    <tr style="background: #FFF;">
        <th align="right">Цена объекта
        <td align="left"><?=$order['object_price']?>
    <tr style="background: #FFF;">
        <th align="right">Сумма кредита
        <td align="left"><?=$order['sum']?>
    <tr style="background: #FFF;">
        <th align="right">Первоначальный взнос
        <td align="left"><?=$order['first_pay']?>
    <tr style="background: #FFF;">
        <th align="right">Размер материнского капитала
        <td align="left"><?=$order['matcap']?>
    <tr style="background: #FFF;">
        <th align="right">Размер субсидий
        <td align="left"><?=$order['subsidy']?>
    <tr style="background: #FFF;">
        <th align="right">Срок кредита
        <td align="left"><?=$order['period']?>
    <tr style="background: #FFF;">
        <th align="right">Желаемая дата платежа
        <td align="left"><?=$order['pay_date']?>
    <tr style="background: #FFF;">
        <th align="right">Вид платежа
        <td align="left"><?=$order['pay_type']?>
    <tr style="background: #FFF;">
        <th align="right">Регион
        <td align="left"><?=$order['region']?>
    <tr style="background: #EEE;">
        <th colspan="2">Заемщики
        <?=$borrowersHtml?>
</table>