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

$jobs = [];
$error = "";
$message = "";

if (!$isLoggedIn) {
    $error = "求人管理を利用するにはログインしてください。";
} elseif ($currentRole !== "company") {
    $error = "求人管理は企業アカウントのみ利用できます。";
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

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_job_id"])) {
            $deleteJobId = (int) $_POST["delete_job_id"];

            // 自社求人のみ削除できるようにする。
            $deleteStmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND company_user_id = ?");
            if ($deleteStmt === false) {
                $error = "削除処理の準備に失敗しました。";
            } else {
                $deleteStmt->bind_param("ii", $deleteJobId, $currentUserId);
                $deleteStmt->execute();
                if ($deleteStmt->affected_rows > 0) {
                    $message = "求人を削除しました。";
                } else {
                    $error = "削除対象の求人が見つかりません。";
                }
                $deleteStmt->close();
            }
        }
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status_job_id"]) && isset($_POST["new_status"])) {
            $updateJobId = (int) $_POST["update_status_job_id"];
            $newStatus = (string) $_POST["new_status"];

            if ($newStatus !== "open" && $newStatus !== "closed") {
                $error = "募集状態の値が不正です。";
            } else {
                // 自社求人のみ募集状態を変更できるようにする。
                $updateStmt = $conn->prepare("UPDATE jobs SET status = ? WHERE id = ? AND company_user_id = ?");
                if ($updateStmt === false) {
                    $error = "募集状態の更新準備に失敗しました。";
                } else {
                    $updateStmt->bind_param("sii", $newStatus, $updateJobId, $currentUserId);
                    $updateStmt->execute();
                    if ($updateStmt->affected_rows > 0) {
                        $message = "募集状態を更新しました。";
                    } elseif ($error === "") {
                        $error = "更新対象の求人が見つかりません。";
                    }
                    $updateStmt->close();
                }
            }
        }

        $sql = "SELECT j.id, j.title, j.area, j.status, j.created_at, COUNT(a.id) AS applicant_count FROM jobs j LEFT JOIN applications a ON j.id = a.job_id WHERE j.company_user_id = ? GROUP BY j.id, j.title, j.area, j.status, j.created_at ORDER BY j.created_at DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            if ($error === "") {
                $error = "求人一覧の取得に失敗しました。";
            }
        } else {
            $stmt->bind_param("i", $currentUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $jobs[] = $row;
            }
            $result->free();
            $stmt->close();
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>求人管理</title>
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
    <h1>求人管理</h1>
    <p>自社が登録した求人の確認・応募者一覧確認・削除ができます。</p>
</div>

<?php if ($error !== "") { ?>
<p class="message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>
<?php if ($message !== "") { ?>
<p class="message-success"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr><th colspan="7" class="x2">求人管理</th></tr>
<tr>
    <th>タイトル</th>
    <th>勤務地</th>
    <th>募集状態</th>
    <th>登録日</th>
    <th>応募者数</th>
    <th>応募者</th>
    <th>削除</th>
</tr>
<?php if (count($jobs) === 0) { ?>
<tr>
    <td colspan="7">登録済みの求人はありません。</td>
</tr>
<?php } ?>
<?php foreach ($jobs as $job) { ?>
<tr>
    <td><?php echo htmlspecialchars((string) $job["title"], ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo htmlspecialchars((string) $job["area"], ENT_QUOTES, "UTF-8"); ?></td>
    <td>
        <form action="manage_jobs.php" method="post">
            <input type="hidden" name="update_status_job_id" value="<?php echo (int) $job["id"]; ?>">
            <select name="new_status" class="xx status-select">
                <option value="open" <?php echo (string) $job["status"] === "open" ? "selected" : ""; ?>>募集中</option>
                <option value="closed" <?php echo (string) $job["status"] === "closed" ? "selected" : ""; ?>>已结束</option>
            </select>
            <input type="submit" value="更新" class="x4">
        </form>
    </td>
    <td><?php echo htmlspecialchars(date("Y/m/d", strtotime((string) $job["created_at"])), ENT_QUOTES, "UTF-8"); ?></td>
    <td><?php echo (int) $job["applicant_count"]; ?></td>
    <td><a href="job_applicants.php?id=<?php echo (int) $job["id"]; ?>">一覧</a></td>
    <td>
        <form action="manage_jobs.php" method="post" onsubmit="return confirm('この求人を削除しますか？');">
            <input type="hidden" name="delete_job_id" value="<?php echo (int) $job["id"]; ?>">
            <input type="submit" value="削除" class="x4">
        </form>
    </td>
</tr>
<?php } ?>
</table>
</div>
</body>
</html>
