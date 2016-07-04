#!/usr/bin/php
<?php
/*****************************
 *
 * MikroTik RouterOS PHP API integration for FastNetMon  
 *
 * This script connect to router MikroTik and add or remove a blackhole's rule for the IP attack
 * 
 * Author: Maximiliano Dobladez info@mkesolutions.net
 *
 * http://maxid.com.ar | http://www.mkesolutions.net  
 *
 * for API MIKROTIK:
 * http://www.mikrotik.com
 * http://wiki.mikrotik.com/wiki/API_PHP_class
 *
 * LICENSE: GPLv2 GNU GENERAL PUBLIC LICENSE
 *
 *
 * v1.0 - 4 Jul 16 - initial version
 ******************************/
//sin errores
error_reporting( 0 );
// error_reporting( E_ALL );
// ini_set( 'display_errors', 'On' );
define( "_VER", '1.0' );

$fecha_now = date("Y-m-d H:i:s", time());

$cfg[ ip_mikrotik ] = "192.168.10.1"; // IP Mikrotik Router 
$cfg[ api_user ]    = "api"; //user
$cfg[ api_pass ]    = "api123"; //pass
/*
INPUT info
This script will get following params:
$1 client_ip_as_string
$2 data_direction
$3 pps_as_string
$4 action (ban or unban)
*/
$IP_ATTACK          = $argv[ 1 ];
$DIRECTION_ATTACK   = $argv[ 2 ];
$POWER_ATTACK       = $argv[ 3 ];
$ACTION_ATTACK      = $argv[ 4 ];
//**Si faltan argumentos no hacer nada
if ( $argc <= 4 ) {
    $msg .= "MikroTik's API Integration for FastNetMon  - Ver: " . _VER . "\n";
    $msg .= "missing arguments";
    $msg .= "php fastnetmon_logger.php [IP] [data_direction] [pps_as_string] [action]  \n";
    echo $msg;
    exit( 1 );
}
//NOTE  help
if ( $argv[ 1 ] == "help" ) {
    $msg = "MikroTik's API Integration for FastNetMon  - Ver: " . _VER;
    echo $msg;
    _log( $msg );
    exit( 1 );
}
require_once "routeros_api.php";
$API = new RouterosAPI();
// $API->debug = true;
if ( $API->connect( $cfg[ ip_mikrotik ], $cfg[ api_user ], $cfg[ api_pass ] ) ) {
    //Blocking by route blackhole
    if ( $ACTION_ATTACK == "ban" ) {
        $comment_rule = 'FastNetMon Guard: IP ' . $IP_ATTACK . ' blocked because ' . $DIRECTION_ATTACK . ' attack with power ' . $POWER_ATTACK . ' pps | at '.$fecha_now;
        $API->write( '/ip/route/add', false );
        $API->write( '=dst-address=' . $IP_ATTACK, false );
        $API->write( '=type=blackhole', false );
        $API->write( '=comment=' . $comment_rule );
        $ret = $API->read();

    }
    if ( $ACTION_ATTACK == "unban" ) {
        $comment_rule = 'FastNetMon Guard: IP ' . $IP_ATTACK . ' remove from blacklist ';
        $API->write( '/ip/route/print', false );
        $API->write( '?dst-address=' . $IP_ATTACK . "/32" );
        $ID_ARRAY = $API->read();
        $API->write( '/ip/route/remove', false );
        $API->write( '=.id=' . $ID_ARRAY[ 0 ][ '.id' ] );
        $ret = $API->read();
    }
    if ($ret) _log( $comment_rule );
} else { // can't connect
    $msg = "Couldn't connect to " . $cfg[ ip_mikrotik ];
    _log( $msg );
    echo $msg;
    exit( 1 );
}
function _log( $msg ) {
    $FILE_LOG_TMP = "/tmp/fastnetmon_api_mikrotik.log";
    if ( !file_exists( $FILE_LOG_TMP ) ) {
        exec( "echo `date` \"- [FASTNETMON] - " . $msg . " \" > " . $FILE_LOG_TMP );
    } else {
        exec( "echo `date` \"- [FASTNETMON] - " . $msg . " \" >> " . $FILE_LOG_TMP );
    }
    return;
}
?>