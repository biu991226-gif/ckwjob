<?php
session_start();

$jobId = 0;
if (isset($_GET["id"])) {
    $jobId = (int) $_GET["id"];
}

$job = [
    "id" => $jobId,
    "title" => "販売スタッフ",
    "salary" => "時給 1200円",
    "area" => "東京",
    "description" => "初期版のため仮の求人説明です。\n仕事内容や条件は後で追加します。",
];

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人詳細</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="jobs.php">求人一覧</a>
<a href="login.php">ログイン</a>
<br>
<hr><br>

<center>
<table class="x1 table-like">
<tr><th colspan="2" class="x2"><center>求人詳細</center></th></tr>
<tr><th>求人タイトル</th><td><?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>給与</th><td><?php echo htmlspecialchars($job["salary"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>勤務地</th><td><?php echo htmlspecialchars($job["area"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>求人説明</th><td><?php echo nl2br(htmlspecialchars($job["description"], ENT_QUOTES, "UTF-8")); ?></td></tr>
<tr>
    <th>応募</th>
    <td>
        <form action="apply.php" method="post">
            <input type="hidden" name="job_id" value="<?php echo $job["id"]; ?>">
            <input type="submit" value="応募する" class="x4">
        </form>
    </td>
</tr>
</table>
</center>

<p><a href="jobs.php">求人一覧へ戻る</a></p>
</body>
</html>
