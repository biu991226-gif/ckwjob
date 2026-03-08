<?php
session_start();

$keyword = "";
if (isset($_GET["keyword"])) {
    $keyword = trim($_GET["keyword"]);
}

$displayJobs = [];
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
        $sql = "SELECT id, title, area, salary FROM jobs WHERE status = 'open' ORDER BY created_at DESC LIMIT 10";
        $result = $conn->query($sql);
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                $displayJobs[] = $row;
            }
            $result->free();
        } else {
            $dbError = "求人データの取得に失敗しました。";
        }
    } else {
        $sql = "SELECT id, title, area, salary FROM jobs WHERE status = 'open' AND (title LIKE ? OR area LIKE ? OR salary LIKE ? OR description LIKE ?) ORDER BY created_at DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        if ($stmt !== false) {
            $likeKeyword = "%" . $keyword . "%";
            $stmt->bind_param("ssss", $likeKeyword, $likeKeyword, $likeKeyword, $likeKeyword);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $displayJobs[] = $row;
            }
            $result->free();
            $stmt->close();
        } else {
            $dbError = "検索処理の準備に失敗しました。";
        }
    }

    $conn->close();
}

$currentName = "";
if (isset($_SESSION["name"])) {
    $currentName = trim((string) $_SESSION["name"]);
}

$currentRole = "";
if (isset($_SESSION["role"])) {
    $currentRole = (string) $_SESSION["role"];
}

$isLoggedIn = $currentName !== "" && $currentRole !== "";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>トップページ</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="top-page">
<div class="page-wrap">
<div class="top-nav">
    <div class="top-nav-left">
        <a href="index.php">トップ</a>
        <a href="jobs.php">求人一覧</a>
        <?php if (!$isLoggedIn) { ?>
            <a href="login.php">ログイン / 会員登録</a>
        <?php } ?>
        <?php if ($currentRole === "job_seeker") { ?>
            <a href="my.php">個人ページ</a>
        <?php } ?>
        <?php if ($currentRole === "company") { ?>
            <a href="manage_jobs.php">求人管理</a>
        <?php } ?>
    </div>
    <div class="top-nav-right">
        <?php if ($isLoggedIn) { ?>
            <span class="nav-user-text">
                ログイン中: <?php echo htmlspecialchars($currentName, ENT_QUOTES, "UTF-8"); ?>
                （<?php echo htmlspecialchars($currentRole, ENT_QUOTES, "UTF-8"); ?>）
            </span>
            <form action="logout.php" method="post" class="logout-form">
                <input type="submit" value="logout" class="logout-btn">
            </form>
        <?php } ?>
    </div>
</div>

<div class="top-hero">
    <h1>求人サイト</h1>
    <p>エリア・職種・給与から、自分に合う仕事を探せます。</p>
</div>

<?php if (!$isLoggedIn) { ?>
<p class="notice">未ログインです。応募や管理機能を使う場合はログインしてください。</p>
<?php } ?>

<?php if ($dbError !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th colspan="2" class="x2">求人検索</th></tr>
<tr>
    <th>検索</th>
    <td>
        <form action="index.php" method="get" class="top-search-form">
            <label for="keyword">キーワード</label><br>
            <input type="text" name="keyword" id="keyword" class="xx" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, "UTF-8"); ?>">
            <input type="submit" value="検索" class="x4 top-search-btn">
        </form>
    </td>
</tr>
<tr>
    <th>新着求人（最大10件）</th>
    <td>
        <?php if (count($displayJobs) === 0) { ?>
            該当する求人はありません。<br>
        <?php } ?>
        <?php foreach ($displayJobs as $job) { ?>
        <div class="job-item">
            <a href="job_detail.php?id=<?php echo (int) $job["id"]; ?>" class="job-title">
                <?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?>
            </a>
            <span class="job-meta"><?php echo htmlspecialchars($job["area"], ENT_QUOTES, "UTF-8"); ?></span>
            <span class="job-meta"><?php echo htmlspecialchars($job["salary"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>
        <?php } ?>
    </td>
</tr>
</table>
</div>
</body>
</html>
