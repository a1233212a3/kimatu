<?php
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['body'])) {
        $image_filename = null;

        if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
            if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
                header("HTTP/1.1 302 Found");
                header("Location: ./3.php");
                exit();
            }

            $pathinfo = pathinfo($_FILES['image']['name']);
            $extension = $pathinfo['extension'];

            $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
            $filepath =  '/var/www/upload/image/' . $image_filename;
            move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
        }

        $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
        $insert_sth->execute([
            ':body' => $_POST['body'],
            ':image_filename' => $image_filename,
        ]);

        header("HTTP/1.1 302 Found");
        header("Location: ./3.php");
        exit();
    }
}

$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>

<form method="POST" action="./3.php" enctype="multipart/form-data">
    <textarea name="body"></textarea>
    <div style="margin: 1em 0;">
        <input type="file" accept="image/*" name="image" id="imageInput">
    </div>
    <button type="submit">送信</button>
</form>

<hr>

<?php foreach($select_sth as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <!-- 这部分与你之前提供的代码相同 -->
  </dl>
<?php endforeach ?>

<script src="https://cdn.jsdelivr.net/npm/image-compression@1.0.0/browser/image-compression.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const imageInput = document.getElementById("imageInput");
    imageInput.addEventListener("change", async () => {
        if (imageInput.files.length < 1) {
            return;
        }

        document.addEventListener("DOMContentLoaded", () => {
    const imageInput = document.querySelector('input[name="image"]');
    const form = document.querySelector('form');

    imageInput.addEventListener("change", async () => {
        if (imageInput.files.length < 1) {
            return;
        }

        const maxFileSizeMB = 1;
        const maxSizeMB = 0.5; // 压缩后的最大文件大小，单位为MB

        const originalImage = imageInput.files[0];

        if (originalImage.size > maxFileSizeMB * 1024 * 1024) {
            alert(`请选择不超过 ${maxFileSizeMB} MB 的图像文件。`);
            imageInput.value = "";
            return;
        }

        try {
            const compressedImage = await imageCompression(originalImage, {
                maxSizeMB: maxSizeMB,
            });

            // 将压缩后的图像数据附加到表单中
            const formData = new FormData(form);
            formData.set('image', compressedImage, compressedImage.name);

            // 使用 fetch 或类似的方法将数据发送到服务器
            fetch(form.action, {
                method: 'POST',
                body: formData,
            }).then(response => {
                if (response.ok) {
                    // 成功上传后可以进行一些反馈
                } else {
                    alert('上传失败，请重试。');
                }
            });
        } catch (error) {
            console.error(error);
            alert('图像压缩时出错，请重试。');
        }
    });
});
    });
});
</script>
	    

