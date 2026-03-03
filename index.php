<?php
session_start();

$jobs = [
    ["id" => 1, "title" => "販売スタッフ", "area" => "東京", "salary" => "時給 1200円"],
    ["id" => 2, "title" => "事務アシスタント", "area" => "大阪", "salary" => "月給 22万円"],
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>トップページ</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="index.php">トップ</a>
<a href="jobs.php">求人一覧</a>
<a href="login.php">ログイン</a>
<a href="my.php">個人ページ</a>
<a href="manage_jobs.php">求人管理</a>
<br>
<hr><br>

<center>
<table class="x1 table-like">
<tr><th colspan="2" class="x2"><center>求人サイト</center></th></tr>
<tr>
    <th>検索</th>
    <td>
        <form action="jobs.php" method="get">
            <input type="text" name="keyword" class="xx">
            <input type="submit" value="検索" class="x4">
        </form>
    </td>
</tr>
<tr>
    <th>新着求人</th>
    <td>
        <?php foreach ($jobs as $job) { ?>
            <a href="job_detail.php?id=<?php echo $job["id"]; ?>">
                <?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?>
            </a>
            / <?php echo htmlspecialchars($job["area"], ENT_QUOTES, "UTF-8"); ?>
            / <?php echo htmlspecialchars($job["salary"], ENT_QUOTES, "UTF-8"); ?>
            <br>
        <?php } ?>
    </td>
</tr>
</table>
</center>

<p class="notice">初期版のため表示内容は仮データです。</p>
</body>
</html>
