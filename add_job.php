<?php
session_start();

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = "初期版のため、まだ保存処理は未実装です。";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人登録</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="manage_jobs.php">求人管理</a>
<a href="index.php">トップ</a>
<br>
<hr><br>

<?php if ($message !== "") { ?>
<p><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<center>
<table class="x1 table-like">
<tr><th colspan="2" class="x2"><center>求人登録</center></th></tr>
<tr>
    <td colspan="2">
        <form action="add_job.php" method="post">
            求人タイトル<br>
            <input type="text" name="title" class="xx"><br><br>
            給与<br>
            <input type="text" name="salary" class="xx"><br><br>
            勤務地<br>
            <input type="text" name="area" class="xx"><br><br>
            求人説明<br>
            <textarea name="description" rows="6" class="xx"></textarea><br><br>
            <input type="submit" value="登録" class="x4">
        </form>
    </td>
</tr>
</table>
</center>
</body>
</html>
