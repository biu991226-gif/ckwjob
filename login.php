<?php
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mode = "";
    $name = "";
    $role = "";

    if (isset($_POST["mode"])) {
        $mode = $_POST["mode"];
    }
    if (isset($_POST["name"])) {
        $name = trim($_POST["name"]);
    }
    if (isset($_POST["role"])) {
        $role = $_POST["role"];
    }

    $_SESSION["name"] = $name !== "" ? $name : "仮ユーザー";
    $_SESSION["role"] = $role !== "" ? $role : "job_seeker";
    $message = $mode === "register" ? "仮登録しました。" : "仮ログインしました。";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ログイン / 会員登録</title>
<link rel="stylesheet" href="common.css">
</head>
<body>
<a href="index.php">トップ</a>
<a href="jobs.php">求人一覧</a>
<br>
<hr><br>

<?php if ($message !== "") { ?>
<p><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<center>
<table class="x1 table-like">
<tr><th colspan="2" class="x2"><center>ログイン / 会員登録</center></th></tr>
<tr>
    <td colspan="2">
        <form action="login.php" method="post">
            <div>
                氏名<br>
                <input type="text" name="name" class="xx">
            </div>
            <br>
            <div>
                メールアドレス<br>
                <input type="email" name="email" class="xx">
            </div>
            <br>
            <div>
                パスワード<br>
                <input type="password" name="password" class="xx">
            </div>
            <br>
            <div>
                電話番号<br>
                <input type="text" name="phone" class="xx">
            </div>
            <br>
            <div>
                ロール<br>
                <select name="role" class="xx">
                    <option value="job_seeker">求職者</option>
                    <option value="company">企業</option>
                </select>
            </div>
            <br>
            <input type="submit" name="mode" value="register" class="x4">
            <input type="submit" name="mode" value="login" class="x4">
        </form>
    </td>
</tr>
</table>
</center>
</body>
</html>
