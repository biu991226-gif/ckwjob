<?php
session_start();

$jobs = [
    ["id" => 1, "title" => "販売スタッフ", "area" => "東京", "date" => "2026/03/01"],
    ["id" => 2, "title" => "事務アシスタント", "area" => "大阪", "date" => "2026/03/01"],
];

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人管理</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="index.php">トップ</a>
<a href="add_job.php">求人登録</a>
<br>
<hr><br>

<p><a href="add_job.php">新規登録へ</a></p>

<center>
<table class="x1 table-like">
<tr><th colspan="5" class="x2"><center>求人管理</center></th></tr>
<tr>
    <th>タイトル</th>
    <th>勤務地</th>
    <th>登録日</th>
    <th>応募者</th>
    <th>削除</th>
</tr>
<?php foreach ($jobs as $job) { ?>
<tr>
    <td><?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($job["area"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($job["date"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><a href="job_applicants.php?id=<?php echo $job["id"]; ?>">一覧</a></td>
    <td><input type="button" value="削除" class="x4"></td>
</tr>
<?php } ?>
</table>
</center>
</body>
</html>
