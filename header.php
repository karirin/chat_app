<?php require_once('class.php');
?>
<nav class="navbar navbar-dark bg-dark">
    <div class="modal"></div>
    <?php if (isset($_SESSION['login']) == false) : ?>
    <ul class="main_ul">
        <li class="top_link"><a href="../user_login/user_top.php">app</a></li>
        <li><a href="../user_login/user_login.php">ログイン</a></li>
        <li><a href="../user/user_add.php">新規登録</a></li>
        <?php
    else :
        if (isset($_GET['page_id']) && $_GET['page_id'] != 'current_user') {
            $user = new User($_GET['page_id']);
            $current_user = $user->get_user();
        } else {
            $user = new User($_SESSION['user_id']);
            $current_user = $user->get_user();
        }
        ?>
        <ul class="main_ul">
            <li class="top_link"><a href="../user_login/user_top.php?type=main&page_id=current_user">app</a></li>
            <li class="top_link prof_page"><a class="prof_modal" href="#"><img
                        src="/user/image/<?= $current_user['image'] ?>" class="user_image"></a></li>
            <li class="header_menu"><a href="../chat/chat_top.php">チャットルーム</a></li>
            <li class="header_menu_wide"><a href="../user/user_list.php?type=all">ユーザー一覧</a></li>
            <li class="header_menu_narrow"><a class="post_modal" href="#">投稿</a></li>
            <li class="header_menu_wide"><a href="../post/post_index.php?type=all">投稿一覧</a></li>
            <li class="header_menu"><a href="../message/message_top.php">
                    メッセージ<?php
                                $message = new Message();
                                if (current($user->get_user_count('message_relation')) != 0) {
                                    if (current($message->message_count(($user->id))) != 0) {
                                        print '' . current($message->message_count(($user->id))) . '';
                                    }
                                }
                                ?>
                </a></li>
            <li class="header_menu_wide"><a href="../user_login/user_logout.php">ログアウト</a></li>
            <li class="header_menu_wide"><a class="withdraw" href="#">退会</a></li>
            <li class="show_menu">メニュー
                <div class="slide_menu">
                    <a class="modal_close" href="#">
                        <p><i class="fas fa-angle-left"></i></p>
                    </a>
                    <ul>
                        <a href="../chat/chat_top.php">
                            <li>チャットルーム</li>
                        </a>
                        <a href="../user/user_list.php?type=all">
                            <li>ユーザー一覧</li>
                        </a>
                        <a href="../post/post_index.php?type=all">
                            <li>投稿一覧</li>
                        </a>
                        <a href="../message/message_top.php">
                            <li class="slide_menu_message">
                                メッセージ<?php
                                            // if (current(get_user_count('message_relation', $_SESSION['user_id'])) != 0) {
                                            //     if (current(message_count(($_SESSION['user_id']))) != 0) {
                                            //         print '' . current(message_count(($_SESSION['user_id']))) . '';
                                            //     }
                                            // } 
                                            $message = new Message();
                                            if (current($user->get_user_count('message_relation')) != 0) {
                                                if (current($message->message_count(($user->id))) != 0) {
                                                    print '' . current($message->message_count(($user->id))) . '';
                                                }
                                            }
                                            ?>
                            </li>
                        </a>
                        <a href="../user_login/user_logout.php">
                            <li>ログアウト</li>
                        </a>
                        <a href="/withdraw.php">
                            <li>退会</li>
                        </a>
                    </ul>
                </div>
            </li>
        </ul>
        <?php
    endif;
        ?>
</nav>
<p class="flash">
    <?php
    if (isset($flash_messages)) :
        foreach ((array)$flash_messages as $message) :
            print '<span class="flash_message">' . $message . '</span>';
        endforeach;
    endif;
    ?>
</p>