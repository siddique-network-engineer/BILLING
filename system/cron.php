<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/

require('../config.php');
require('orm.php');

require_once 'autoload/PEAR2/Autoload.php';

ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);
ORM::configure('return_result_sets', true);
ORM::configure('logging', true);


include "autoload/Hookers.php";


//register all plugin
foreach (glob("system/plugin/*.php") as $filename)
{
    include $filename;
}

// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists('autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists('autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}

spl_autoload_register('_autoloader');

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach($result as $value){
    $config[$value['setting']]=$value['value'];
}
date_default_timezone_set($config['timezone']);

$d = ORM::for_table('tbl_user_recharges')->where('status','on')->find_many();

run_hook('cronjob'); #HOOK

foreach ($d as $ds){
	if($ds['type'] == 'Hotspot'){
		$date_now = strtotime(date("Y-m-d H:i:s"));
		$expiration = strtotime($ds['expiration'].' '.$ds['time']);
		echo $ds['expiration']." : ".$ds['username'];
		if ($date_now >= $expiration){
			echo " : EXPIRED \r\n";
			$u = ORM::for_table('tbl_user_recharges')->where('id',$ds['id'])->find_one();
			$c = ORM::for_table('tbl_customers')->where('id',$ds['customer_id'])->find_one();
			$m = ORM::for_table('tbl_routers')->where('name',$ds['routers'])->find_one();

            if(!$_c['radius_mode']){
                $client = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
                Mikrotik::setHotspotLimitUptime($client,$c['username']);
                Mikrotik::removeHotspotActiveUser($client,$c['username']);
            }

			//update database user dengan status off
			$u->status = 'off';
			$u->save();
		}else echo " : ACTIVE \r\n";
	}else{
		$date_now = strtotime(date("Y-m-d H:i:s"));
		$expiration = strtotime($ds['expiration'].' '.$ds['time']);
		echo $ds['expiration']." : ".$ds['username'];
		if ($date_now >= $expiration){
			echo " : EXPIRED \r\n";
			$u = ORM::for_table('tbl_user_recharges')->where('id',$ds['id'])->find_one();
			$c = ORM::for_table('tbl_customers')->where('id',$ds['customer_id'])->find_one();
			$m = ORM::for_table('tbl_routers')->where('name',$ds['routers'])->find_one();

            if(!$_c['radius_mode']){
                $client = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
                Mikrotik::disablePpoeUser($client,$c['username']);
                Mikrotik::removePpoeActive($client,$c['username']);
            }

			$u->status = 'off';
			$u->save();
		}else echo " : ACTIVE \r\n";
	}
}
