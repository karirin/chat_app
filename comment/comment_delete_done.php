<?php
require_once('../config_2.php');

try {

    $comment_id = $_POST['id'];
    $comment_image_name = $_POST['image_name'];
    $user_id = $_POST['user_id'];
    $post_id = $_POST['post_id'];

    $dbh = db_connect();
    $sql = 'DELETE FROM comment WHERE id=?';
    $stmt = $dbh->prepare($sql);
    $data[] = $comment_id;
    $stmt->execute($data);
    if (!empty(get_reply_comments($post_id, $comment_id))) {
        $reply_comment = get_reply_comments($post_id, $comment_id);
        $sql = 'DELETE FROM comment WHERE comment_id=?';
        $stmt = $dbh->prepare($sql);
        $data[] = $reply_comment['id'];
        $stmt->execute($data);
    }

    $dbh = null;

    if ($comment_image_name != '') {
        unlink('./image/' . $comment_image_name);
    }
} catch (Exception $e) {
    _debug('コメント削除失敗');
    exit();
}

set_flash('sucsess', 'コメントを削除しました');
reload();

?>

</body>
<?php require_once('../footer.php'); ?>

</html>