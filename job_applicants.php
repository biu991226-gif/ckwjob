<?php
session_start();

$jobId = 0;
if (isset($_GET["id"])) {
    $jobId = (int) $_GET["id"];
}

$applicants = [
    ["name" => "山田 太郎", "email" => "yamada@example.com", "phone" => "090-1111-2222", "date" => "2026/03/01"],
    ["name" => "佐藤 花子", "email" => "sato@example.com", "phone" => "090-3333-4444", "date" => "2026/03/01"],
];

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>応募者一覧</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="manage_jobs.php">求人管理</a>
<a href="index.php">トップ</a>
<br>
<hr><br>

<p>対象求人ID: <?php echo $jobId; ?></p>

<center>
<table class="x1 table-like">
<tr><th colspan="4" class="x2"><center>応募者一覧</center></th></tr>
<tr>
    <th>氏名</th>
    <th>メールアドレス</th>
    <th>電話番号</th>
    <th>応募日</th>
</tr>
<?php foreach ($applicants as $applicant) { ?>
<tr>
    <td><?php echo htmlspecialchars($applicant["name"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($applicant["email"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($applicant["phone"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($applicant["date"], ENT_QUOTES, "UTF-8"); ?></td>
</tr>
<?php } ?>
</table>
</center>

<p><a href="manage_jobs.php">求人管理へ戻る</a></p>
</body>
</html>
