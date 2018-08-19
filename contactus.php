
<?php
use PhpMyAdmin\Response;
require_once 'libraries/common.inc.php';

/**
 * start output
 */
$response = Response::getInstance();
$response->addHTML('
<form action="" method="post">
First Name: <input type="text" name="Fname"><br>
Last Name: <input type="text" name="Lname"><br>
Message:<textarea name="message" rows="5" cols="5"></textarea><br>
<input type="submit">
</form>
');

exit;