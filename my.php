<?php
session_start();

$name = "ゲスト";
if (isset($_SESSION["name"]) && $_SESSION["name"] !== "") {
    $name = $_SESSION["name"];
}

$role = "未ログイン";
if (isset($_SESSION["role"]) && $_SESSION["role"] !== "") {
    $role = $_SESSION["role"];
}

$history = [
    ["title" => "販売スタッフ", "date" => "2026/03/01", "status" => "応募済み"],
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>個人ページ</title>
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
<tr><th colspan="3" class="x2"><center>個人ページ</center></th></tr>
<tr><th>氏名</th><td colspan="2"><?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>ロール</th><td colspan="2"><?php echo htmlspecialchars($role, ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr>
    <th>求人タイトル</th>
    <th>応募日</th>
    <th>状態</th>
</tr>
<?php foreach ($history as $item) { ?>
<tr>
    <td><?php echo htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($item["date"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars($item["status"], ENT_QUOTES, "UTF-8"); ?></td>
</tr>
<?php } ?>
</table>
</center>
</body>
</html>
