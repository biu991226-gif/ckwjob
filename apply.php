<?php
session_start();

$jobId = 0;
if (isset($_POST["job_id"])) {
    $jobId = (int) $_POST["job_id"];
}

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

$message = "";
$error = "";
$jobTitle = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $error = "不正なアクセスです。";
} elseif ($jobId <= 0) {
    $error = "求人IDが不正です。";
} elseif (!$isLoggedIn) {
    $error = "応募するにはログインが必要です。";
} elseif ($currentRole !== "job_seeker") {
    $error = "企業アカウントでは応募できません。";
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

        // 応募前に求人の存在と公開状態を確認する。
        $jobStmt = $conn->prepare("SELECT title, status FROM jobs WHERE id = ? LIMIT 1");
        if ($jobStmt === false) {
            $error = "求人情報の確認に失敗しました。";
        } else {
            $jobStmt->bind_param("i", $jobId);
            $jobStmt->execute();
            $result = $jobStmt->get_result();
            $job = $result->fetch_assoc();
            $result->free();
            $jobStmt->close();

            if ($job === null) {
                $error = "指定された求人は存在しません。";
            } elseif ((string) $job["status"] !== "open") {
                $error = "この求人は現在応募できません。";
            } else {
                $jobTitle = (string) $job["title"];

                $insertStmt = $conn->prepare("INSERT INTO applications (job_id, job_seeker_user_id, status) VALUES (?, ?, 'applied')");
                if ($insertStmt === false) {
                    $error = "応募処理の準備に失敗しました。";
                } else {
                    $insertStmt->bind_param("ii", $jobId, $currentUserId);
                    $ok = $insertStmt->execute();
                    $dbErrno = $conn->errno;
                    $insertStmt->close();

                    if ($ok) {
                        $message = "「" . $jobTitle . "」に応募しました。";
                    } elseif ($dbErrno === 1062) {
                        $error = "この求人には既に応募済みです。";
                    } else {
                        $error = "応募処理に失敗しました。";
                    }
                }
            }
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>応募処理</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="apply-page">
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
    <h1>応募処理</h1>
    <p>応募結果を確認してください。</p>
</div>

<?php if ($error !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>
<?php if ($message !== "") { ?>
<p class="message-success"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th class="x2">応募処理</th></tr>
<tr>
    <td>
        <div class="apply-result-text">
            <?php if ($message !== "") { ?>
                応募が完了しました。個人ページで応募状況を確認できます。
            <?php } else { ?>
                応募は完了していません。内容を確認してください。
            <?php } ?>
        </div>
        <div class="apply-actions">
            <a href="my.php" class="apply-action-link">個人ページへ</a>
            <a href="jobs.php" class="apply-action-link">求人一覧へ戻る</a>
        </div>
    </td>
</tr>
</table>
</div>
</body>
</html>
