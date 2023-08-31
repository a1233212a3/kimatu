<?php
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');
if (isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合
  $image_filename = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // アップロードされた画像がある場合
    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      // アップロードされたものが画像ではなかった場合
      header("HTTP/1.1 302 Found");
      header("Location: ./kimatu2.php");
    }
    // 元のファイル名から拡張子を取得
    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];
    // 新しいファイル名を決める。他の投稿の画像ファイルと重複しないように時間+乱数で決める。
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
    $filepath =  '/var/www/upload/image/' . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }
  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);
  // 処理が終わったらリダイレクトする
  // リダイレクトしないと，リロード時にまた同じ内容でPOSTすることになる
  header("HTTP/1.1 302 Found");
  header("Location: ./kimatu2.php");
  return;
}

$select_sth = null;
if (isset($_GET['query'])) {
  // urlクエリパラメータ query がある場合
  $select_sth = $dbh->prepare('SELECT * FROM bbs_entries WHERE body LIKE :query ORDER BY created_at DESC');
  $select_sth->execute([
    ':query' => '%' . $_GET['query'] . '%',

  ]);
} else {
  // ない場合
  $select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
  $select_sth->execute();
}1
?>
<!-- フォームのPOST先はこのファイル自身にする -->
<!DOCTYPE html>
<html>
  <head>
    <title>bbs</title>
    <meta charset="utf-8">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.0/dist/browser-image-compression.js"></script>
    <style>
    @media (max-width: 768px) {
  input[type="text"], input[type="submit"] {
    width: 100%;
  }
}
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }
    .content {
  padding: 10px;
}

form {
  margin-bottom: 20px;
}
img {
  max-width: 100%;
  height: auto;
}
button, input[type="submit"] {
  background-color: #007bff;
  color: #fff;
  padding: 10px 20px;
  border: none;
  cursor: pointer;
}
</style>
  </head>
  <body>
  <form method="POST" action="./kimatu2.php" enctype="multipart/form-data">   
  <textarea name="body"></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image" id="imageInput">
  </div>
  <button type="submit">送信</button>
    <div class="content">
      <h2>画像圧縮してみる</h2>
    <input type="file" accept="image/*" name="image2" id="imageInput" onchange="handleImageUpload(event);">
  </div>
<button type="submit">送信</button>
</form>  
    <div id="original-size"></div>
      <div id="compressed-size"></div>
      <p id="compressed-image"></p>
</div>
  </body>
</html>
<hr>
<form method="GET" action="./kimatu2.php">
  <?php if(isset($_GET['query'])): ?>
    <input type="text" name="query" value="<?= $_GET['query'] ?>">
  <?php else: ?>
    <input type="text" name="query">
  <?php endif ?>
  <button type="submit">検索</button>
</form>
<?php if(isset($_GET['query'])): ?>
 現在「<?= $_GET['query'] ?>」で検索中。
  <a href="./kimatu2.php">検索解除</a>
<?php endif; ?>
<hr>
<?php foreach($select_sth as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd>
      <a href="./view.php?id=<?= $entry['id'] ?>"><?= $entry['id'] ?></a>
    </dd>
    <dt>日時</dt>
    <dd><?= $entry['created_at'] ?></dd>
    <dt>内容</dt>
    <dd>
      <?= nl2br(htmlspecialchars($entry['body'])) // 必ず htmlspecialchars() すること ?>
      <?php if(!empty($entry['image_filename'])): // 画像がある場合は img 要素を使って表示 ?>
      <div>
        <img src="/image/<?= $entry['image_filename'] ?>" style="max-height: 10em;">
      </div>
      <?php endif; ?>
    </dd>
  </dl>
<?php endforeach ?>

<script>
const originalSize = document.getElementById('original-size');
const compressedSize = document.getElementById('compressed-size');
const compressedImage = document.getElementById('compressed-image');

function handleImageUpload(event) {
  const imageFile = event.target.files[0];

  // reset content
  originalSize.textContent = '';
  compressedSize.textContent = '';
  compressedImage.innerHTML = '';

  const options = {
    maxSizeMB: 4,
    maxWidthOrHeight: 1920
  }

  imageCompression(imageFile, options)
    .then(function (compressedFile) {
	    	const img = URL.createObjectURL(compressedFile);
      		originalSize.textContent = `元画像のサイズ: ${(imageFile.size / 1024 / 1024).toFixed(2)} MB`;
      		compressedSize.textContent = `圧縮した画像のサイズ: ${(compressedFile.size / 1024 / 1024).toFixed(2)} MB`;

            compressedImage.innerHTML += `
          <a href="${img}" target="_blank">
            <img src="${img}" width="400" alt="">
          </a>`

 
    })
    .catch(function (error) {
      console.log(error.message);
    });
}
document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  imageInput.addEventListener("change", () => {
    if (imageInput.files.length < 1) {
      // 未選択の場合
      return;
    }
    if (imageInput.files[0].size > 5 * 1024 * 1024) {
      // ファイルが5MBより多い場合
      alert("5MB以下のファイルを選択してください。");
      imageInput.value = "compressed-image";
    }
  });
});
</script>
