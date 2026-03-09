<?php
session_start();

$jobId = 0;
if (isset($_POST["job_id"])) {
    $jobId = (int) $_POST["job_id"];
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>応募処理</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="jobs.php">求人一覧</a>
<a href="my.php">個人ページ</a>
<br>
<hr><br>

<center>
<table class="x1 table-like">
<tr><th class="x2"><center>応募処理</center></th></tr>
<tr><td>求人 ID <?php echo $jobId; ?> に対する応募処理の仮画面です。</td></tr>
<tr><td>この初期版では、応募データの保存はまだ実装していません。</td></tr>
</table>
</center>
</body>
</html>
