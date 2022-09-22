<?php
require_once('config_1.php');
?>
<!DOCTYPE html>
<html lang="ja">
<meta charset="utf-8">
<title>Chat</title>
<script src="http://code.jquery.com/jquery-2.2.4.js"></script>
<script>
(function($) {
        var destination_user_image = $('.destination_user_image').val();
        var settings = {};

        var methods = {
            init: function(options) {
                settings = $.extend({
                    'uri': 'ws://localhost:8080',
                    'conn': null,
                    'message': '#message',
                    'display': '#display'
                }, options);
                $(settings['message']).keypress(methods['checkEvent']);
                $(this).chat('connect');
            },

            checkEvent: function(event) {
                if (event && event.which == 13) {
                    var message = $(settings['message']).val();
                    if (message && settings['conn']) {
                        settings['conn'].send(message + '');
                        $(this).chat('drawText', message, 'right');
                    }
                }
            },

            connect: function() {
                if (settings['conn'] == null) {
                    settings['conn'] = new WebSocket(settings['uri']);
                    settings['conn'].onopen = methods['onOpen'];
                    settings['conn'].onmessage = methods['onMessage'];
                    settings['conn'].onclose = methods['onClose'];
                    settings['conn'].onerror = methods['onError'];
                }
            },

            onMessage: function(event) {
                if (event && event.data) {
                    $(this).chat('drawText', event.data, 'left');
                }
            },

            onError: function(event) {
                $(this).chat('drawText', 'エラー発生!', 'left');
            },

            drawText: function(message, align = 'left') {
                var box = $('<p class="box"></p>').text(message);
                var message_box = $('<p class="box"></p>');
                var destination_user_image = $('.destination_user_image').val();
                var message_image = $('.my_preview').attr('src');
                var newelm = document.createElement('img');
                newelm.setAttribute('src', message_image);
                if (align === 'left') {
                    var inner = $('<div class="left"></div>').html(box);
                    if (message_image != null) {
                        box[0].appendChild(newelm);
                    }
                    inner.prepend(
                        "<img src='../user/image/" + destination_user_image + "' class='message_user_img'>");
                } else {
                    var inner = $('<div class="mycomment right"></div>').html(box);
                    if (message_image != null) {
                        box[0].appendChild(newelm);
                    }
                    inner.append(
                        "<img src='../user/image/<?= $current_user['image'] ?>' class='message_user_img'>");
                }
                $('#chat').append(inner);
                $('.my_preview').attr('src', null);
            },
        };

        $.fn.chat = function(method) {
            if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
            } else if (typeof method === 'object' || !method) {
                return methods.init.apply(this, arguments);
            } else {
                $.error('Method ' + method + ' does not exist');
            }
        }

        $(document).keypress(function(e) {
                e.stopPropagation();
                if (e.which == 13) {
                    var message = document.getElementById("message").value,
                        destination_user_id = document.getElementById("destination_user_id").value;
                    var formdata = new FormData();
                    var formdata = new FormData(document.getElementById('image'));
                    formdata.append('message', message);
                    formdata.append('destination_user_id', destination_user_id);
                    $.ajax({
                        type: 'POST',
                        url: 'message/ajax_post_message.php',
                        dataType: 'html',
                        data: formdata,
                        cache: false,
                        processData: false,
                        contentType: false
                    }).done(function(data) {
                        document.getElementById("message").value = '';
                        $('.far.fa-times-circle.my_clear').hide();
                        $('.my_preview').hide();
                        $('#my_image').val('');
                    }).fail(function(data) {});

                });

        })(jQuery);

    $(function() {
        $(this).chat({
            'uri': 'ws://localhost:8080',
            'message': '#message',
            'display': '#chat'
        });
    });
</script>
</head>

<body>
    <?php
    $user = new User($_SESSION['user_id']);
    $current_user = $user->get_user();
    $user = new User($_GET['user_id']);
    $destination_user = $user->get_user();
    $message = new Message();
    $messages = $message->get_messages($current_user['id'], $destination_user['id']);
    $bottom_message = $message->get_new_message($current_user['id'], $destination_user['id']);
    $message->reset_message_count($current_user['id'], $destination_user['id']);
    ?>
    <div class="row">
        <div class="col-8 offset-2">
            <h2 class="center"><?= $destination_user['name'] ?></h2>
            <div class="message">
                <?php
                foreach ($messages as $message) :
                ?>
                <div class="my_message">
                    <?php if ($message['user_id'] == $current_user['id']) : ?>
                    <div class="mycomment right">
                        <span class="message_created_at">
                            <?= convert_to_fuzzy_time($message['created_at']) ?>
                        </span>
                        <p><?= $message['text'] ?>
                            <?php if (!empty($message['image'])) : ?>
                            <img src="../message/image/<?= $message['image'] ?>">
                            <?php endif; ?>
                        </p><img src="../user/image/<?= $current_user['image'] ?>" class="message_user_img">
                    </div>
                    <?php else : ?>

                    <div class="left"><img src="../user/image/<?= $destination_user['image'] ?>"
                            class="message_user_img">
                        <div class="says"><?= $message['text'] ?>
                            <?php if (!empty($message['image'])) : ?>
                            <img src="../message/image/<?= $message['image'] ?>">
                            <?php endif; ?>
                        </div><span
                            class="message_created_at"><?= convert_to_fuzzy_time($message['created_at']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach ?>
                </div>
                <div id="chat" class="container">
                </div>
                <input type="text" id="message" size="50" />
                <div class="message_image">
                    <label>
                        <form id="image">
                            <i class="far fa-image"></i>
                            <input type="file" name="image" id="my_image" accept="image/*" multiple>
                        </form>
                    </label>
                </div>
                <div class="message_image_detail">
                    <div><img class="my_preview"></div>
                    <i class="far fa-times-circle my_clear"></i>
                </div>
                <input type="hidden" name="destination_user" class="destination_user_image"
                    value="<?= $destination_user["image"] ?>">
                <input type="hidden" name="destination_user_id" id="destination_user_id"
                    value="<?= $destination_user['id'] ?>">
            </div>
        </div>
</body>
<?php
if (isset($_SESSION['login']) == true) {
    require('profile.php');
}
?>
<script src="/js/user_page.js"></script>

</html>