<?php

define('ANY_CAN_VIEW_LIST', true); /* whether everyone can view a list, or admins only */

define('BASE_URL', 'http://yuba.stanford.edu/github');
define('DB_NAME', 'wordpress');
define('DB_USER', 'wordpress');
define('DB_PASSWORD', '1cthychr0n0s');
define('DB_HOST', 'localhost');

function connect_to_db() {
    mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
}

function get_table() {
    return 'github_email_lists';
}

function get_list_or_die() {
    if(isset($_GET['list'])) {
        return $_GET['list'];
    }
    else {
        echo "Missing list name";
        exit(0);
    }
}

function list_email($list, $subj, $body) {
    $to_addrs = list_get_active_to_addrs($list);
    $to = '';
    foreach($to_addrs as $a) {
        $to .= "$a,";
    }

    $headers = "From: $list List <noreply@yuba.stanford.edu>\r\n";
    return mail($to, $subj, $body, $headers);
}

function list_get_active_to_addrs($list) {
    connect_to_db();
    $table = get_table();
    $result = mysql_query("SELECT email FROM $table WHERE list='$list' AND active='1'");

    $addrs = array();
    $i = 0;
    while($row=mysql_fetch_array($result)) {
        $addrs[$i++] = $row[0];
    }

    mysql_close();
    return $addrs;
}

function list_get_active_to_addrs_as_string($list) {
    $addrs = '';
    foreach(list_get_active_to_addrs($list) as $addr)
        $addrs .= "$addr,";
    return $addrs;
}

function list_exists($list) {
    connect_to_db();
    $table = get_table();
    $result = mysql_query("SELECT COUNT(*) FROM $table WHERE list='$list'");
    $ret = mysql_fetch_array($result);
    mysql_close();
    $count = $ret[0];
    return $count > 0;
}

function list_is_admin($list, $addr, $code) {
    connect_to_db();
    $table = get_table();
    $result = mysql_query("SELECT COUNT(*) FROM $table WHERE list='$list' AND email='$addr' AND code='$code' AND admin='1'");
    $ret = mysql_fetch_array($result);
    mysql_close();
    $count = $ret[0];
    return $count > 0;
}

function list_is_subscribed($list, $addr, $code) {
    connect_to_db();
    $table = get_table();
    $result = mysql_query("SELECT COUNT(*) FROM $table WHERE list='$list' AND email='$addr' AND code='$code'");
    $ret = mysql_fetch_array($result);
    mysql_close();
    $count = $ret[0];
    return $count > 0;
}

function list_add_to_addr($list, $addr, $admin) {
    connect_to_db();
    $table = get_table();
    $result = mysql_query("SELECT COUNT(*) FROM $table WHERE list='$list' AND email='$addr'");
    $row = mysql_fetch_array($result);
    $count = $row[0];
    if($count > 0) {
        return false;
    }

    $code = md5(uniqid(rand(), true));
    $ret = mysql_query("INSERT INTO $table (list, email, active, code, admin)
                        VALUES ('$list', '$addr', '0', '$code', '$admin')");
    $status = mysql_affected_rows() > 0;
    mysql_close();
    if($status)
        list_email_code_direct($list, $addr, $code, $admin==1, false);
    return $status;
}

function list_verify_addr($list, $addr, $code) {
    connect_to_db();
    $table = get_table();
    $ret = mysql_query("UPDATE $table SET active=1
                        WHERE list='$list' AND email='$addr' AND code='$code'
                        LIMIT 1");
    $status = mysql_affected_rows() > 0;
    mysql_close();
    return $status;
}

function list_delete_addr($list, $addr, $code) {
    connect_to_db();
    $table = get_table();
    $ret = mysql_query("DELETE FROM $table
                        WHERE list='$list' AND email='$addr' AND code='$code'
                        LIMIT 1");
    $status = mysql_affected_rows() > 0;
    mysql_close();
    return $status;
}

function list_email_code($list, $addr) {
    connect_to_db();
    $table = get_table();
    $result = mysql_query("SELECT code, admin, active FROM $table WHERE list='$list' AND email='$addr'");
    if(mysql_numrows($result) > 0) {
        $row = mysql_fetch_array($result);
        $code = $row[0];
        $admin = ($row[1] == 1);
        $active = ($row[2] == 1);
        list_email_code_direct($list, $addr, $code, $admin, $active);
    }
    else
        $code = false;

    mysql_close();
    return $code;
}

function list_email_code_direct($list, $addr, $code, $admin, $active) {
    $url = BASE_URL;
    if($admin || ANY_CAN_VIEW_LIST)
        $admin_txt = "View the list: $url/list.php?list=$list&addr=$addr&code=$code&action=view";
    else
        $admin_txt = '';

    if(!$active)
        $active_txt = "Verify that you want to join the list: $url/list.php?list=$list&addr=$addr&code=$code&action=verify";
    else
        $active_txt = '';

    $body = <<<BODY
$admin_txt
$active_txt
Leave the list: $url/list.php?list=$list&addr=$addr&code=$code&action=leave
BODY;
    $headers = "From: $list List <noreply@yuba.stanford.edu>\r\n";
    mail($addr, "$list List", $body, $headers);
}

function list_create($list, $addr) {
    connect_to_db();
    $table = get_table();
    $code = md5(uniqid(rand(), true));
    $ret = mysql_query("CREATE TABLE IF NOT EXISTS $table (
                        id INT NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY(id),
                        list VARCHAR(100),
                        email VARCHAR(100),
                        code VARCHAR(32),
                        admin INT,
                        active INT)");
    mysql_close();

    if(!list_exists($list))
        return list_add_to_addr($list, $addr, 1);
    else {
        echo "List alread exists.  ";
        return false;
    }
}

?>
