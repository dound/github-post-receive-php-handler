<?php

include 'list_funcs.php';

$list = get_list_or_die();
$url = BASE_URL;

function getOrEmpty($v) {
    if(isset($_GET[$v]))
        return $_GET[$v];
    else
        return '';
}

$action = getOrEmpty('action');
$addr = getOrEmpty('addr');
$code = getOrEmpty('code');
$create_only = getOrEmpty('co');

echo <<<PAGE
<html>
<head>
  <title>$list List</title>
</head>
<body>

<form action="$url/list.php">
    <input type="hidden" name="list" value="$list">
    Email: <input type="text" name="addr" size="64" value="$addr"><br/>
    <input type="submit" name="action" value="Subscribe to List">
    <input type="submit" name="action" value="I forgot my code - email it to me"><br/>
    &nbsp;<br/>
PAGE;

if($create_only != '1')
    echo <<< MORE
    <i>Actions for those on the list:</i><br/>
    Code:  <input type="text" name="code" size="64" value="$code"><br/>
    <input type="submit" name="action" value="Unsubscribe from List">
    <input type="submit" name="action" value="View List">
MORE;
echo '</form>';

if($action == 'Subscribe to List') {
    $ret = list_add_to_addr($list, $addr, 0);
    if($ret === -1)
        echo "Error: you already belong to this list!";
    else if($ret)
        echo "A verification has been sent to your email";
    else
        echo "Error: " . mysql_error();
}
elseif($action == 'I forgot my code - email it to me') {
    if(list_email_code($list, $addr))
        echo 'Your code has been emailed to you.';
    else
        echo 'Unable to email your code to you.';
}
elseif($action == 'Unsubscribe from List' || $action == 'leave') {
    if(list_delete_addr($list, $addr, $code))
        echo "Address $addr removed from list $list.";
    else
        echo "Unable to remove that address.";
}
elseif($action == 'View List' || $action=='view') {
    if((ANY_CAN_VIEW_LIST && list_is_subscribed($list, $addr, $code)) || list_is_admin($list, $addr, $code)) {
        echo '<b>List subscribers:</b><br/><ul>';
        foreach(list_get_active_to_addrs($list) as $a)
            echo "<li>$a</li>";
        echo '</ul>';
    }
    else
        echo "Denied.";
}
else if($action == 'verify') {
    if(list_verify_addr($list, $addr, $code))
        echo "$addr is now verified for the $list list";
    else
        echo "Invalid verification code";
}

echo <<<ENDPAGE
</body>
</html>
ENDPAGE;

?>
