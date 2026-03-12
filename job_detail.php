<?php
session_start();

$jobId = 0;
if (isset($_GET["id"])) {
    $jobId = (int) $_GET["id"];
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

$isLoggedIn = $currentName !== "" && $currentRole !== "";
$isAlreadyApplied = false;

$job = null;
$dbError = "";

if ($jobId <= 0) {
    $dbError = "求人IDが不正です。";
}

if ($dbError === "") {
    $config = require __DIR__ . "/config.php";
    $conn = @new mysqli(
        $config["db_host"],
        $config["db_user"],
        $config["db_pass"],
        $config["db_name"],
        (int) $config["db_port"]
    );

    if ($conn->connect_error) {
        $dbError = "DB接続に失敗しました。設定値を確認してください。";
    } else {
        $conn->set_charset($config["db_charset"]);

        // 詳細画面は指定IDを1件取得する。
        $stmt = $conn->prepare("SELECT id, title, salary, area, description, status, created_at FROM jobs WHERE id = ? LIMIT 1");
        if ($stmt !== false) {
            $stmt->bind_param("i", $jobId);
            $stmt->execute();
            $result = $stmt->get_result();
            $job = $result->fetch_assoc();
            $result->free();
            $stmt->close();

            if ($job !== null && $currentRole === "job_seeker" && $currentUserId > 0) {
                // 応募済み判定を行い、二重応募のボタン表示を防ぐ。
                $appStmt = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND job_seeker_user_id = ? LIMIT 1");
                if ($appStmt !== false) {
                    $appStmt->bind_param("ii", $jobId, $currentUserId);
                    $appStmt->execute();
                    $appResult = $appStmt->get_result();
                    $isAlreadyApplied = $appResult->fetch_assoc() !== null;
                    $appResult->free();
                    $appStmt->close();
                }
            }
        } else {
            $dbError = "求人詳細の取得に失敗しました。";
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人詳細</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="job-detail-page">
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
    <h1>求人詳細</h1>
    <p>仕事内容・勤務地・給与を確認し、条件が合えば応募してください。</p>
</div>

<?php if ($dbError !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<?php if ($dbError === "" && $job === null) { ?>
<p class="message-error">指定された求人は見つかりませんでした。</p>
<?php } ?>

<?php if ($job !== null) { ?>
<table class="x1 table-like top-table">
<tr><th colspan="2" class="x2">求人詳細</th></tr>
<tr><th>求人タイトル</th><td><?php echo htmlspecialchars((string) $job["title"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>給与</th><td><?php echo htmlspecialchars((string) $job["salary"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>勤務地</th><td><?php echo htmlspecialchars((string) $job["area"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>投稿日</th><td><?php echo htmlspecialchars(date("Y/m/d", strtotime((string) $job["created_at"])), ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>公開状態</th><td><?php echo htmlspecialchars((string) $job["status"], ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>求人説明</th><td class="job-description"><?php echo nl2br(htmlspecialchars((string) $job["description"], ENT_QUOTES, "UTF-8")); ?></td></tr>
<tr>
    <th>応募</th>
    <td>
        <?php if (!$isLoggedIn) { ?>
            応募するには <a href="login.php?tab=login">ログイン</a> してください。
        <?php } elseif ($currentRole !== "job_seeker") { ?>
            企業アカウントでは応募できません。
        <?php } elseif ((string) $job["status"] !== "open") { ?>
            この求人は現在応募を受け付けていません。
        <?php } elseif ($isAlreadyApplied) { ?>
            <span class="status-applied">応募済み</span>です。
        <?php } else { ?>
            <form action="apply.php" method="post">
                <input type="hidden" name="job_id" value="<?php echo (int) $job["id"]; ?>">
                <input type="submit" value="応募する" class="x4">
            </form>
        <?php } ?>
    </td>
</tr>
</table>
<?php } ?>

<p><a href="jobs.php">求人一覧へ戻る</a></p>
</div>
</body>
</html>
