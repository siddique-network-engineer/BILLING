<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/
_auth();
$ui->assign('_title', $_L['Dashboard'] . ' - ' . $config['CompanyName']);

$user = User::_info();
$ui->assign('_user', $user);

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);


$ui->assign('unpaid', ORM::for_table('tbl_payment_gateway')
    ->where('username', $user['username'])
    ->where('status', 1)
    ->find_one());
run_hook('view_customer_dashboard'); #HOOK
$ui->display('user-dashboard.tpl');
