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

$history = [];
$error = "";

if (!$isLoggedIn) {
    $error = "個人ページを表示するにはログインしてください。";
} elseif ($currentRole !== "job_seeker") {
    $error = "このページは求職者アカウント専用です。";
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

        // 応募履歴を新しい順で表示する。
        $sql = "SELECT j.id AS job_id, j.title, a.applied_at, a.status FROM applications a INNER JOIN jobs j ON a.job_id = j.id WHERE a.job_seeker_user_id = ? ORDER BY a.applied_at DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = "応募履歴の取得に失敗しました。";
        } else {
            $stmt->bind_param("i", $currentUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            $result->free();
            $stmt->close();
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

$roleLabels = [
    "job_seeker" => "求職者",
    "company" => "企業",
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>個人ページ</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="my-page">
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
    <h1>個人ページ</h1>
    <p>応募した求人の履歴と状態を確認できます。</p>
</div>

<?php if ($error !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th colspan="3" class="x2">個人ページ</th></tr>
<tr><th>氏名</th><td colspan="2"><?php echo htmlspecialchars($currentName !== "" ? $currentName : "ゲスト", ENT_QUOTES, "UTF-8"); ?></td></tr>
<tr><th>ロール</th><td colspan="2">
    <?php
        $displayRole = "未ログイン";
        if ($currentRole !== "") {
            $displayRole = isset($roleLabels[$currentRole]) ? $roleLabels[$currentRole] : $currentRole;
        }
        echo htmlspecialchars($displayRole, ENT_QUOTES, "UTF-8");
    ?>
</td></tr>
<tr>
    <th>求人タイトル</th>
    <th>応募日</th>
    <th>状態</th>
</tr>
<?php if (count($history) === 0) { ?>
<tr>
    <td colspan="3">応募履歴はまだありません。</td>
</tr>
<?php } ?>
<?php foreach ($history as $item) { ?>
<tr>
    <td>
        <a href="job_detail.php?id=<?php echo (int) $item["job_id"]; ?>">
            <?php echo htmlspecialchars((string) $item["title"], ENT_QUOTES, "UTF-8"); ?>
        </a>
    </td>
    <td><?php echo htmlspecialchars(date("Y/m/d", strtotime((string) $item["applied_at"])), ENT_QUOTES, "UTF-8"); ?></td>
    <td>
        <?php
            $statusKey = (string) $item["status"];
            $statusLabel = isset($statusLabels[$statusKey]) ? $statusLabels[$statusKey] : $statusKey;
            echo htmlspecialchars($statusLabel, ENT_QUOTES, "UTF-8");
        ?>
    </td>
</tr>
<?php } ?>
</table>
</div>
</body>
</html>
