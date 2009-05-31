<?php

include 'list_funcs.php';

$url = BASE_URL;

if(isset($_GET['list']) && isset($_GET['addr'])) {
    $list = $_GET['list'];
    $addr = $_GET['addr'];

    if(list_create($list, $addr)) {
        echo "List created.  Check your email.";
    }
    else {
        echo "List could not be created.";
    }
    exit(0);
}

echo <<<PAGE
<html>
<head>
  <title>Create List</title>
</head>
<body>

<form action="$url/list_create.php">
    <table>
    <tr>
      <td>List:</td>
      <td><input type="text" name="list"></td>
    </tr>
    <tr>
      <td>Email:</td>
      <td><input type="text" name="addr"></td>
    </tr>
    <tr><td colspan="2"><input type="submit" value="Create List"></td></tr>
    </table>
</form>
</body>
</html>
PAGE;

?>