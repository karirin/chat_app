<?php 
require_once('../header.php');
require_once('../head.php'); 
require_once('../config.php');
?>
<body>
<?php if (isset($flash_messages)): ?>
      <?php foreach ((array)$flash_messages as $message): ?>
        <p class ="flash_message <?= $flash_type ?>"><?= $message?></p>
      <?php endforeach ?>
<?php endif ?>
<?php

if (isset($_SESSION['code'])) {
  $current_user = get_user($_SESSION['code']);
}else{
  $current_user = 'guest';
}

if(isset($_SESSION['login'])==false)
{
print '<br />';
print 'ようこそ、coffeeappへ';
}
else
{
require_once("../staff/staff_mypage.php");
}
?>

</body>
</html>
