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

$isLoggedIn = $currentName !== "" && $currentRole !== "" && $currentUserId > 0;

$jobTitle = "";
$applicants = [];
$error = "";

if ($jobId <= 0) {
    $error = "求人IDが不正です。";
} elseif (!$isLoggedIn) {
    $error = "応募者一覧を表示するにはログインしてください。";
} elseif ($currentRole !== "company") {
    $error = "応募者一覧は企業アカウントのみ利用できます。";
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

        // 他社求人の応募者一覧を見られないよう、所有者チェックを行う。
        $jobStmt = $conn->prepare("SELECT title FROM jobs WHERE id = ? AND company_user_id = ? LIMIT 1");
        if ($jobStmt === false) {
            $error = "求人情報の確認に失敗しました。";
        } else {
            $jobStmt->bind_param("ii", $jobId, $currentUserId);
            $jobStmt->execute();
            $jobResult = $jobStmt->get_result();
            $jobRow = $jobResult->fetch_assoc();
            $jobResult->free();
            $jobStmt->close();

            if ($jobRow === null) {
                $error = "対象求人が見つからないか、閲覧権限がありません。";
            } else {
                $jobTitle = (string) $jobRow["title"];

                $sql = "SELECT u.name, u.email, u.phone, a.applied_at, a.status FROM applications a INNER JOIN users u ON a.job_seeker_user_id = u.id WHERE a.job_id = ? ORDER BY a.applied_at DESC";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "応募者一覧の取得に失敗しました。";
                } else {
                    $stmt->bind_param("i", $jobId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $applicants[] = $row;
                    }
                    $result->free();
                    $stmt->close();
                }
            }
        }

        $conn->close();
    }
}

$statusLabels = [
    "applied" => "応募済み",
    "screening" => "選考中",
    "rejected" => "不採用",
    "accepted" => "採用",
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>応募者一覧</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="applicants-page">
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
    <h1>応募者一覧</h1>
    <p>対象求人: <?php echo htmlspecialchars($jobTitle !== "" ? $jobTitle : (string) $jobId, ENT_QUOTES, "UTF-8"); ?></p>
</div>

<?php if ($error !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th colspan="5" class="x2">応募者一覧</th></tr>
<tr>
    <th>氏名</th>
    <th>メールアドレス</th>
    <th>電話番号</th>
    <th>応募日</th>
    <th>状態</th>
</tr>
<?php if ($error === "" && count($applicants) === 0) { ?>
<tr>
    <td colspan="5">応募者はまだいません。</td>
</tr>
<?php } ?>
<?php foreach ($applicants as $applicant) { ?>
<tr>
    <td><?php echo htmlspecialchars((string) $applicant["name"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars((string) $applicant["email"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars((string) $applicant["phone"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars(date("Y/m/d", strtotime((string) $applicant["applied_at"])), ENT_QUOTES, "UTF-8"); ?></td>
    <td>
        <?php
            $statusKey = (string) $applicant["status"];
            $statusLabel = isset($statusLabels[$statusKey]) ? $statusLabels[$statusKey] : $statusKey;
            echo htmlspecialchars($statusLabel, ENT_QUOTES, "UTF-8");
        ?>
    </td>
</tr>
<?php } ?>
</table>

<p><a href="manage_jobs.php">求人管理へ戻る</a></p>
</div>
</body>
</html>
