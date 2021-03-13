<div class="modal_post"></div>
<div class="post_process">
  <h2 class="post_title">投稿</h2>
  <form method="post" action="../post/post_add_done.php" enctype="multipart/form-data">
  <textarea id="post_process_counter" class="textarea form-control" placeholder="投稿内容を入力ください" name="text"></textarea>
  <div class="counter">
                <span class="post_process_count">0</span><span>/300</span>
  </div>
  <div class="post_image">
  <label>
  <i class="far fa-image"></i>
  <input type="file" name="image_name" id="process_image" accept="image/*" multiple>
  </label>
  <p><img class="process_preview"></p>
<input type="button" id="process_clear" value="ファイルをクリアする">
  </div>
  <div class="post_btn">
  <button class="btn btn-outline-danger" type="submit" name="post" value="post" id="post">投稿</button>
  <button class="btn btn-outline-primary modal_close" type="button">キャンセル</button>
  </div>
  </form>
</div>