<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/
_admin();
$ui->assign('_title', $_L['Network'] . ' - ' . $config['CompanyName']);
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if ($admin['user_type'] != 'Admin') {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}

use PEAR2\Net\RouterOS;

require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
    case 'list':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/pool.js"></script>');

        $name = _post('name');
        if ($name != '') {
            $paginator = Paginator::bootstrap('tbl_pool', 'pool_name', '%' . $name . '%');
            $d = ORM::for_table('tbl_pool')->where_like('pool_name', '%' . $name . '%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_pool');
            $d = ORM::for_table('tbl_pool')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        }

        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        run_hook('view_pool'); #HOOK
        $ui->display('pool.tpl');
        break;

    case 'add':
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_pool'); #HOOK
        $ui->display('pool-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_pool')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_edit_pool'); #HOOK
            $ui->display('pool-edit.tpl');
        } else {
            r2(U . 'pool/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('delete_pool'); #HOOK
        $d = ORM::for_table('tbl_pool')->find_one($id);
        $mikrotik = Mikrotik::info($d['routers']);
        if ($d) {
            if (!$config['radius_mode']) {
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::removePool($client, $d['pool_name']);
            }
            $d->delete();

            r2(U . 'pool/list', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'add-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $routers = _post('routers');
        run_hook('add_pool'); #HOOK
        $msg = '';
        if (Validator::Length($name, 30, 2) == false) {
            $msg .= 'Name should be between 3 to 30 characters' . '<br>';
        }
        if ($ip_address == '' or $routers == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_pool')->where('pool_name', $name)->find_one();
        if ($d) {
            $msg .= $_L['Pool_already_exist'] . '<br>';
        }
        $mikrotik = Mikrotik::info($routers);
        if ($msg == '') {
            if (!$config['radius_mode']) {
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::addPool($client, $name, $ip_address);
            }

            $b = ORM::for_table('tbl_pool')->create();
            $b->pool_name = $name;
            $b->range_ip = $ip_address;
            $b->routers = $routers;
            $b->save();

            r2(U . 'pool/list', 's', $_L['Created_Successfully']);
        } else {
            r2(U . 'pool/add', 'e', $msg);
        }
        break;


    case 'edit-post':
        $ip_address = _post('ip_address');
        $routers = _post('routers');
        run_hook('edit_pool'); #HOOK
        $msg = '';

        if ($ip_address == '' or $routers == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_pool')->find_one($id);
        if ($d) {
        } else {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }

        $mikrotik = Mikrotik::info($routers);
        if ($msg == '') {
            if (!$config['radius_mode']) {
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::setPool($client, $d['pool_name'], $ip_address);
            }

            $d->range_ip = $ip_address;
            $d->routers = $routers;
            $d->save();

            r2(U . 'pool/list', 's', $_L['Updated_Successfully']);
        } else {
            r2(U . 'pool/edit/' . $id, 'e', $msg);
        }
        break;

    default:
        echo 'action not defined';
}
