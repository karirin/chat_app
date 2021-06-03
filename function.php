<?php

//================================
//  デバッグ
//================================

function _debug($data, $clear_log = false)
{
  $uri_debug_file = $_SERVER['DOCUMENT_ROOT'] . '/debug.txt';
  if ($clear_log) {
    file_put_contents($uri_debug_file, print_r('', true));
  }
  file_put_contents($uri_debug_file, print_r($data, true), FILE_APPEND);
}

//================================
//  ユーザー関連
//================================

//  ユーザーを取得する
function get_user($user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT id,name,password,profile,image
            FROM user
            WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':id' => $user_id));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('ユーザー取得失敗');
  }
}

//  複数のユーザーを取得する
function get_users($type, $query)
{
  try {
    $dbh = db_connect();

    switch ($type) {
      case 'all':
        $sql = "SELECT *
              FROM user
              ORDER BY id DESC";
        $stmt = $dbh->prepare($sql);
        break;

      case 'search':
        $sql = "SELECT *
                FROM user
                WHERE name LIKE CONCAT('%',:input,'%')
                ORDER BY id DESC";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':input', $query);
        break;

      case 'follows':
        $sql = "SELECT follower_id
                FROM relation
                WHERE follow_id = :follow_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':follow_id', $query);
        break;

      case 'followers':
        $sql = "SELECT follow_id
                FROM relation
                WHERE follower_id = :follower_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':follower_id', $query);
        break;
    }
    $stmt->execute();
    return $stmt->fetchAll();
  } catch (\Exception $e) {
    _debug('複数ユーザー取得失敗');
  }
}

//  ユーザー新規登録の際、既にユーザーIDとパスワードがないか確認する
function get_newuser($name, $password)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT id,name,password,profile,image
            FROM user
            WHERE name = :name and password = :password";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':name' => $name, ':password' => $password));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('新規ユーザー取得失敗');
  }
}

//  ユーザーIDから個数を取得する
function get_user_count($object, $user_id)
{
  $dbh = db_connect();
  switch ($object) {

    case 'favorite':
      $sql = "SELECT COUNT(post_id)
          FROM favorite
          WHERE user_id = :id";
      break;

    case 'post':
      $sql = "SELECT COUNT(id)
            FROM post
            WHERE user_id = :id";
      break;

    case 'comment':
      $sql = "SELECT COUNT(id)
            FROM comment
            WHERE user_id = :id";
      break;

    case 'follow':
      $sql = "SELECT COUNT(follower_id)
          FROM relation
          WHERE follow_id = :id";
      break;

    case 'follower':
      $sql = "SELECT COUNT(follow_id)
          FROM relation
          WHERE follower_id = :id";
      break;

    case 'message':
      $sql = "SELECT COUNT(id)
          FROM message
          WHERE user_id = :id";
      break;

    case 'message_relation':
      $sql = "SELECT COUNT(id)
          FROM message_relation
          WHERE user_id = :id";
      break;
  }
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(':id' => $user_id));
  return $stmt->fetch();
}

//  ユーザーのログイン時刻を更新する
function update_login_time($date, $id)
{
  try {
    $dbh = db_connect();
    $dbh->beginTransaction();
    $sql = 'UPDATE user SET login_time = :date WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':date' => $date->format('Y-m-d H:i:s'), ':id' => $id));
    $dbh->commit();
  } catch (\Exception $e) {
    _debug('ログイン時刻更新失敗');
    $dbh->rollback();
    reload();
  }
}

//  フォロー中かどうか確認する
function check_follow($follow_user, $follower_user)
{
  $dbh = db_connect();
  $sql = "SELECT follow_id,follower_id
          FROM relation
          WHERE :follower_id = follower_id AND :follow_id = follow_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(
    ':follow_id' => $follow_user,
    ':follower_id' => $follower_user
  ));
  return  $stmt->fetch();
}

function check_user($user_name)
{
  $dbh = db_connect();
  $sql = "SELECT name
          FROM user
          WHERE :name = name";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(':name' => $user_name));
  return  $stmt->fetch();
}

//================================
//  投稿関連
//================================

// 投稿を取得する
function get_post($post_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM post
            WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':id' => $post_id));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('投稿取得失敗');
  }
}

//　投稿を複数取得する
function get_posts($user_id, $type, $query)
{
  try {
    $dbh = db_connect();
    switch ($type) {
      case 'all':
        $sql = "SELECT *
              FROM post
              ORDER BY created_at DESC";
        $stmt = $dbh->prepare($sql);
        break;
        //　自分の投稿を取得する
      case 'my_post':
        $sql = "SELECT *
              FROM user INNER JOIN post ON user.id = post.user_id
              WHERE user_id = :id
              ORDER BY created_at DESC";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $user_id);
        break;
        //　お気に入り登録した投稿を取得する
      case 'favorite':
        $sql = "SELECT *
              FROM post INNER JOIN favorite ON post.id = favorite.post_id
              INNER JOIN user ON user.id = post.user_id
              WHERE favorite.user_id = :id
              ORDER BY created_at DESC";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $user_id);
        break;
        //　フォローしているユーザーの投稿を取得する
      case 'follow':
        $sql = "SELECT *
                FROM post INNER JOIN relation ON post.user_id = relation.follower_id
                WHERE relation.follow_id = :id
                ORDER BY created_at DESC";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $user_id);
        break;
        //　検索結果の投稿を取得する
      case 'search':
        $sql = "SELECT *
                FROM post
                WHERE text LIKE CONCAT('%',:input,'%')
                ORDER BY id DESC";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':input', $query);
        break;
    }
    $stmt->execute();
    return $stmt->fetchAll();
  } catch (\Exception $e) {
    _debug('複数の投稿取得失敗');
  }
}

//　お気に入りの投稿数を取得する
function get_post_favorite_count($post_id)
{
  $dbh = db_connect();
  $sql = "SELECT COUNT(user_id)
          FROM favorite
          WHERE post_id = :post_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(':post_id' => $post_id));
  return $stmt->fetch();
}

//　投稿IDからコメントを取得する
function get_post_comment_count($post_id)
{
  $dbh = db_connect();
  $sql = "SELECT COUNT(id)
          FROM comment
          WHERE post_id = :post_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(':post_id' => $post_id));
  return $stmt->fetch();
}

//================================
//  コメント関連
//================================

//　コメントを取得する
function get_comment($comment_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM comment
            WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':id' => $comment_id));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('コメント取得失敗');
  }
}

//  複数のコメントを取得する
function get_comments($post_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM comment
            WHERE post_id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':id' => $post_id));
    return $stmt->fetchAll();
  } catch (\Exception $e) {
    _debug('複数コメント取得失敗');
  }
}

//  コメントがある投稿か確認する
function check_comment($post_id)
{
  $dbh = db_connect();
  $sql = "SELECT *
          FROM comment
          WHERE post_id = :post_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(':post_id' => $post_id));
  $favorite = $stmt->fetch();
  return $favorite;
}

//  コメントへの返信コメントを取得する
function get_reply_comments($post_id, $comment_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM comment
            WHERE post_id = :id AND comment_id = :comment_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':id' => $post_id, ':comment_id' => $comment_id));
    return $stmt->fetchAll();
  } catch (\Exception $e) {
    _debug('返信コメント取得失敗');
  }
}

//　コメントへの返信コメント数を取得する
function get_reply_comment_count($comment_id)
{
  $dbh = db_connect();
  $sql = "SELECT COUNT(id)
          FROM comment
          WHERE comment_id = :comment_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(':comment_id' => $comment_id));
  return $stmt->fetch();
}

//================================
//  メッセージ関連
//================================

//　メッセージ数を取得する
function message_count($user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT SUM(message_count)
            FROM message_relation
            WHERE destination_user_id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':user_id' => $user_id));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('メッセージ取得失敗');
  }
}

//  複数のメッセージを取得する
function get_messages($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM message
            WHERE (user_id = :id and destination_user_id = :destination_user_id) or (user_id = :destination_user_id and destination_user_id = :id)
            ORDER BY created_at ASC";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetchAll();
  } catch (\Exception $e) {
    _debug('複数メッセージ取得失敗');
  }
}

//　最新のメッセージを取得する
function get_new_message($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM message
            WHERE (user_id = :user_id and destination_user_id = :destination_user_id)
                  or (user_id = :destination_user_id and destination_user_id = :user_id)
            ORDER BY created_at DESC";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('最新メッセージ取得失敗');
  }
}

//  新規メッセージ数を取得する
function new_message_count($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT message_count
            FROM message_relation
            WHERE ((user_id = :user_id and destination_user_id = :destination_user_id) or (user_id = :destination_user_id and destination_user_id = :user_id)) and user_id = :destination_user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('複数の最新メッセージ取得失敗');
  }
}

//  メッセージリストを新規登録する
function insert_message($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "INSERT INTO
            message_relation(user_id,destination_user_id)
            VALUES (:user_id,:destination_user_id),(:destination_user_id,:user_id)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('メッセージリスト追加失敗');
  }
}

//  ログインユーザーのメッセージリストを新規登録する
function insert_user_message($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "INSERT INTO
            message_relation(user_id,destination_user_id)
            VALUES (:user_id,:destination_user_id)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('ログインユーザーのメッセージリスト新規登録失敗');
  }
}

//  メッセージ数を更新する
function insert_message_count($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "UPDATE message_relation
            SET message_count = message_count + 1
            WHERE ((user_id = :user_id and destination_user_id = :destination_user_id) or (user_id = :destination_user_id and destination_user_id = :user_id)) and user_id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('メッセージ数更新失敗');
  }
}

//  メッセージリストを取得する
function get_message_relations($user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT *
            FROM message_relation
            WHERE user_id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':user_id' => $user_id));
    return $stmt->fetchAll();
  } catch (\Exception $e) {
    _debug('メッセージリスト取得失敗');
  }
}

//  メッセージリストがあるか確認する
function check_relation_message($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT user_id,destination_user_id
            FROM message_relation
            WHERE (user_id = :user_id and destination_user_id = :destination_user_id)
                  or (user_id = :destination_user_id and destination_user_id = :user_id)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('メッセージリスト確認失敗');
  }
}

//  ログインユーザーのメッセージリストがあるか確認する
function check_relation_user_message($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT user_id,destination_user_id
            FROM message_relation
            WHERE user_id = :user_id and destination_user_id = :destination_user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('ログインユーザーのメッセージリスト確認失敗');
  }
}

//　メッセージリスト
function check_relation_delete_message($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $sql = "SELECT user_id,destination_user_id
            FROM message_relation
            WHERE user_id = :destination_user_id and destination_user_id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    return $stmt->fetch();
  } catch (\Exception $e) {
    _debug('メッセージリスト削除確認失敗');
  }
}

//  メッセージ数を０にする
function reset_message_count($user_id, $destination_user_id)
{
  try {
    $dbh = db_connect();
    $dbh->beginTransaction();
    $sql = 'UPDATE message_relation SET message_count = 0 WHERE ((user_id = :user_id and destination_user_id = :destination_user_id) or (user_id = :destination_user_id and destination_user_id = :user_id)) and user_id = :destination_user_id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':user_id' => $user_id,
      ':destination_user_id' => $destination_user_id
    ));
    $dbh->commit();
  } catch (\Exception $e) {
    _debug('メッセージ数リセット失敗');
    $dbh->rollback();
    reload();
  }
}

//================================
//  その他
//================================

function image_check($image)
{
  if ($image['size'] > 0) {
    if ($image['size'] > 10) {
      set_flash('danger', '画像が大きすぎます');
      reload();
    } else {
      move_uploaded_file($image['tmp_name'], './image/' . $image['name']);
    }
  }
}

//  お気に入りの重複を確認する
function check_favolite_duplicate($user_id, $post_id)
{
  $dbh = db_connect();
  $sql = "SELECT *
          FROM favorite
          WHERE user_id = :user_id AND post_id = :post_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute(array(
    ':user_id' => $user_id,
    ':post_id' => $post_id
  ));
  $favorite = $stmt->fetch();
  return $favorite;
}

//  ページネーション
function pagination_block($data)
{
  global $block;
  $data_count = count($data);
  $block_count = ceil($data_count / 5);
  $k = 0;
  for ($i = 0; $i < $block_count; $i++) {
    for ($j = 0; $j < 5; $j++) {
      if ($data_count == $k) {
        break;
      }
      $block[$i][$j] = $data[$k];
      $k++;
    }
  }
  return $block;
}

//  作成時間を～前で表示する
function convert_to_fuzzy_time($time_db)
{
  ini_set("date.timezone", "Asia/Tokyo");
  $unix = strtotime($time_db);
  $now = strtotime('now');
  $diff_sec   = $now - $unix;

  if ($diff_sec < 60) {
    $time   = $diff_sec;
    $unit   = "秒前";
  } elseif ($diff_sec < 3600) {
    $time   = $diff_sec / 60;
    $unit   = "分前";
  } elseif ($diff_sec < 86400) {
    $time   = $diff_sec / 3600;
    $unit   = "時間前";
  } elseif ($diff_sec < 2764800) {
    $time   = $diff_sec / 86400;
    $unit   = "日前";
  } else {
    if (date("Y") != date("Y", $unix)) {
      $time   = date("Y年n月j日", $unix);
    } else {
      $time   = date("n月j日", $unix);
    }

    return $time;
  }

  return (int)$time . $unit;
}

//  ページを再読み込みする
function reload()
{
  header('Location:' . $_SERVER['HTTP_REFERER']);
  exit();
}

//　フラッシュメッセージ
function set_flash($type, $message)
{
  $_SESSION['flash']['type'] = "flash_${type}";
  $_SESSION['flash']['message'] = $message;
}