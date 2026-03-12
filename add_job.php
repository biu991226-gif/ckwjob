<?php
session_start();

$currentName = "";
if (isset($_SESSION["name"])) {
    $currentName = trim((string) $_SESSION["name"]);
}

$currentRole = "";
if (isset($_SESSION["role"])) {
    $currentRole = (string) $_SESSION["role"];
}

$currentUserId = 0;
if (isset($_SESSION["user_id"])) {
    $currentUserId = (int) $_SESSION["user_id"];
}

$isLoggedIn = $currentName !== "" && $currentRole !== "" && $currentUserId > 0;

$title = "";
$salary = "";
$area = "";
$description = "";
$message = "";
$error = "";

if (!$isLoggedIn) {
    $error = "求人登録を利用するにはログインしてください。";
} elseif ($currentRole !== "company") {
    $error = "求人登録は企業アカウントのみ利用できます。";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["title"])) {
        $title = trim((string) $_POST["title"]);
    }
    if (isset($_POST["salary"])) {
        $salary = trim((string) $_POST["salary"]);
    }
    if (isset($_POST["area"])) {
        $area = trim((string) $_POST["area"]);
    }
    if (isset($_POST["description"])) {
        $description = trim((string) $_POST["description"]);
    }

    // 必須入力を確認してから登録する。
    if ($title === "" || $salary === "" || $area === "" || $description === "") {
        $error = "必須項目をすべて入力してください。";
    } else {
        $config = require __DIR__ . "/config.php";
        $conn = @new mysqli(
            $config["db_host"],
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"],
            (int) $config["db_port"]
        );

        if ($conn->connect_error) {
            $error = "DB接続に失敗しました。設定値を確認してください。";
        } else {
            $conn->set_charset($config["db_charset"]);

            $sql = "INSERT INTO jobs (company_user_id, title, salary, area, description, status) VALUES (?, ?, ?, ?, ?, 'open')";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $error = "求人登録の準備に失敗しました。";
            } else {
                $stmt->bind_param("issss", $currentUserId, $title, $salary, $area, $description);
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    $message = "求人を登録しました。";
                    $title = "";
                    $salary = "";
                    $area = "";
                    $description = "";
                } else {
                    $error = "求人登録に失敗しました。";
                }
            }

            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人登録</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="manage-page">
<div class="page-wrap">
<div class="top-nav">
    <div class="top-nav-left">
        <a href="index.php">トップ</a>
        <a href="jobs.php">求人一覧</a>
        <?php if (!$isLoggedIn) { ?>
            <a href="login.php">ログイン / 会員登録</a>
        <?php } ?>
        <?php if ($isLoggedIn) { ?>
            <a href="my.php">個人ページ</a>
        <?php } ?>
        <?php if ($currentRole === "company") { ?>
            <a href="manage_jobs.php">求人管理</a>
            <a href="add_job.php">求人登録</a>
        <?php } ?>
    </div>
    <div class="top-nav-right">
        <?php if ($isLoggedIn) { ?>
            <span class="nav-user-text">ログイン中: <?php echo htmlspecialchars($currentName, ENT_QUOTES, "UTF-8"); ?></span>
            <a href="logout.php">logout</a>
        <?php } ?>
    </div>
</div>

<div class="top-hero">
    <h1>求人登録</h1>
    <p>新しい求人を登録できます。登録後は求人管理画面で確認してください。</p>
</div>

<?php if ($error !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>
<?php if ($message !== "") { ?>
<p class="message-success"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th colspan="2" class="x2">求人登録</th></tr>
<tr>
    <td colspan="2">
        <form action="add_job.php" method="post">
            <div class="form-row">
                求人タイトル<br>
                <input type="text" name="title" class="xx" value="<?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                給与<br>
                <input type="text" name="salary" class="xx" value="<?php echo htmlspecialchars($salary, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                勤務地<br>
                <input type="text" name="area" class="xx" value="<?php echo htmlspecialchars($area, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                求人説明<br>
                <textarea name="description" rows="6" class="xx"><?php echo htmlspecialchars($description, ENT_QUOTES, "UTF-8"); ?></textarea>
            </div>
            <div class="form-actions">
                <input type="submit" value="登録" class="x4">
                <a href="manage_jobs.php">求人管理へ戻る</a>
            </div>
        </form>
    </td>
</tr>
</table>
</div>
</body>
</html>
