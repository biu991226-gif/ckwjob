<?php
session_start();

$keyword = "";
if (isset($_GET["keyword"])) {
    $keyword = trim((string) $_GET["keyword"]);
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
$appliedJobMap = [];

$jobs = [];
$dbError = "";

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

    if ($keyword === "") {
        $sql = "SELECT id, title, salary, area, status, created_at FROM jobs ORDER BY created_at DESC LIMIT 50";
        $result = $conn->query($sql);
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                $jobs[] = $row;
            }
            $result->free();
        } else {
            $dbError = "求人一覧の取得に失敗しました。";
        }
    } else {
        // キーワード検索時は部分一致でタイトルなどを絞り込む。
        $sql = "SELECT id, title, salary, area, status, created_at FROM jobs WHERE (title LIKE ? OR area LIKE ? OR salary LIKE ? OR description LIKE ?) ORDER BY created_at DESC LIMIT 50";
        $stmt = $conn->prepare($sql);
        if ($stmt !== false) {
            $likeKeyword = "%" . $keyword . "%";
            $stmt->bind_param("ssss", $likeKeyword, $likeKeyword, $likeKeyword, $likeKeyword);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $jobs[] = $row;
            }
            $result->free();
            $stmt->close();
        } else {
            $dbError = "検索処理の準備に失敗しました。";
        }
    }

    if ($dbError === "" && $currentRole === "job_seeker" && $currentUserId > 0 && count($jobs) > 0) {
        // 一覧表示用に、当該ユーザーの応募済み求人IDを取得する。
        $appStmt = $conn->prepare("SELECT job_id FROM applications WHERE job_seeker_user_id = ?");
        if ($appStmt !== false) {
            $appStmt->bind_param("i", $currentUserId);
            $appStmt->execute();
            $appResult = $appStmt->get_result();
            while ($row = $appResult->fetch_assoc()) {
                $appliedJobMap[(int) $row["job_id"]] = true;
            }
            $appResult->free();
            $appStmt->close();
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人一覧</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="jobs-page">
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
            <a href="logout.php">ログアウト</a>
        <?php } ?>
    </div>
</div>

<div class="top-hero">
    <h1>求人一覧</h1>
    <p>条件に合う求人を検索し、詳細画面から応募できます。</p>
</div>

<?php if ($dbError !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th colspan="6" class="x2">求人一覧</th></tr>
<tr>
    <td colspan="6">
        <form action="jobs.php" method="get" class="top-search-form">
            <label for="keyword">キーワード</label><br>
            <input type="text" name="keyword" id="keyword" class="xx" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, "UTF-8"); ?>">
            <input type="submit" value="検索" class="x4">
        </form>
    </td>
</tr>
<tr>
    <th>タイトル</th>
    <th>給与</th>
    <th>勤務地</th>
    <th>募集状態</th>
    <th>投稿日</th>
    <th>応募状況</th>
</tr>
<?php if (count($jobs) === 0) { ?>
<tr>
    <td colspan="6">該当する求人はありません。</td>
</tr>
<?php } ?>
<?php foreach ($jobs as $job) { ?>
<tr>
    <td>
        <a href="job_detail.php?id=<?php echo (int) $job["id"]; ?>">
            <?php echo htmlspecialchars((string) $job["title"], ENT_QUOTES, "UTF-8"); ?>
        </a>
    </td>
    <td><?php echo htmlspecialchars((string) $job["salary"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars((string) $job["area"], ENT_QUOTES, "UTF-8"); ?></td>
    <td>
        <?php if ((string) $job["status"] === "open") { ?>
            <span class="status-open">募集中</span>
        <?php } else { ?>
            <span class="status-closed">已结束</span>
        <?php } ?>
    </td>
    <td><?php echo htmlspecialchars(date("Y/m/d", strtotime((string) $job["created_at"])), ENT_QUOTES, "UTF-8"); ?></td>
    <td>
        <?php if ($currentRole === "job_seeker" && $currentUserId > 0) { ?>
            <?php if (isset($appliedJobMap[(int) $job["id"]])) { ?>
                <span class="status-applied">応募済み</span>
            <?php } else { ?>
                未応募
            <?php } ?>
        <?php } else { ?>
            -
        <?php } ?>
    </td>
</tr>
<?php } ?>
</table>
</div>
</body>
</html>
