<?php
session_start();

$keyword = "";
if (isset($_GET["keyword"])) {
    $keyword = trim($_GET["keyword"]);
}

$jobs = [
    ["id" => 1, "title" => "販売スタッフ", "area" => "東京", "salary" => "時給 1200円", "date" => "2026/03/01"],
    ["id" => 2, "title" => "事務アシスタント", "area" => "大阪", "salary" => "月給 22万円", "date" => "2026/03/01"],
];

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人一覧</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="index.php">トップ</a>
<a href="jobs.php">求人一覧</a>
<a href="login.php">ログイン</a>
<br>
<hr><br>

<center>
<table class="x1 table-like">
<tr><th colspan="5" class="x2"><center>求人一覧</center></th></tr>
<tr>
    <td colspan="5">
        <form action="jobs.php" method="get">
            <input type="text" name="keyword" class="xx" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, "UTF-8"); ?>">
            <input type="submit" value="再検索" class="x4">
        </form>
    </td>
</tr>
<tr>
    <th>タイトル</th>
    <th>給与</th>
    <th>勤務地</th>
    <th>投稿日</th>
    <th>詳細</th>
</tr>
<?php foreach ($jobs as $job) { ?>
<tr>
    <td><?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($job["salary"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($job["area"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($job["date"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><a href="job_detail.php?id=<?php echo $job["id"]; ?>">見る</a></td>
</tr>
<?php } ?>
</table>
</center>
</body>
</html>
