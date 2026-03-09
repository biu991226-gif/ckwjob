<?php
session_start();

$message = "";
$errors = [];

$mode = "";
$activeTab = "login";
$name = "";
$email = "";
$phone = "";
$role = "job_seeker";
$password = "";
$redirectUrl = "";
$redirectWaitSeconds = 2;

$config = require __DIR__ . "/config.php";
$conn = @new mysqli(
    $config["db_host"],
    $config["db_user"],
    $config["db_pass"],
    $config["db_name"],
    (int) $config["db_port"]
);

if ($conn->connect_error) {
    $errors[] = "DB接続に失敗しました。設定値を確認してください。";
} else {
    $conn->set_charset($config["db_charset"]);
}

if (isset($_GET["tab"])) {
    $tab = (string) $_GET["tab"];
    if ($tab === "login" || $tab === "register") {
        $activeTab = $tab;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // フォーム入力を受け取り、必要な項目だけ trim する。
    if (isset($_POST["mode"])) {
        $mode = trim((string) $_POST["mode"]);
    }
    if ($mode === "login" || $mode === "register") {
        $activeTab = $mode;
    }
    if (isset($_POST["name"])) {
        $name = trim((string) $_POST["name"]);
    }
    if (isset($_POST["email"])) {
        $email = trim((string) $_POST["email"]);
    }
    if (isset($_POST["phone"])) {
        $phone = trim((string) $_POST["phone"]);
    }
    if (isset($_POST["password"])) {
        $password = (string) $_POST["password"];
    }
    if (isset($_POST["role"])) {
        $role = (string) $_POST["role"];
    }

    if ($mode !== "register" && $mode !== "login") {
        $errors[] = "操作区分が不正です。";
    }

    // MVP段階のため、まずは必須と形式の最小バリデーションを実施する。
    if ($email === "") {
        $errors[] = "メールアドレスを入力してください。";
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = "メールアドレスの形式が正しくありません。";
    }
    if ($password === "") {
        $errors[] = "パスワードを入力してください。";
    }

    if ($mode === "register") {
        if ($name === "") {
            $errors[] = "氏名を入力してください。";
        }
        if ($role !== "job_seeker" && $role !== "company") {
            $errors[] = "ロールが不正です。";
        }
    }

    if (count($errors) === 0) {
        if (!($conn instanceof mysqli) || $conn->connect_error) {
            $errors[] = "DB接続が利用できません。";
        } else {
            if ($mode === "register") {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                if ($checkStmt === false) {
                    $errors[] = "登録前チェックに失敗しました。";
                } else {
                    $checkStmt->bind_param("s", $email);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $existsUser = $result->fetch_assoc();
                    $result->free();
                    $checkStmt->close();

                    if ($existsUser !== null) {
                        $errors[] = "このメールアドレスは既に登録済みです。";
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $insertSql = "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)";
                        $insertStmt = $conn->prepare($insertSql);
                        if ($insertStmt === false) {
                            $errors[] = "会員登録に失敗しました。";
                        } else {
                            $insertStmt->bind_param("sssss", $name, $email, $passwordHash, $phone, $role);
                            $insertOk = $insertStmt->execute();
                            $insertStmt->close();
                            if (!$insertOk) {
                                $errors[] = "会員登録に失敗しました。";
                            } else {
                                $newUserId = (int) $conn->insert_id;
                                $_SESSION["name"] = $name;
                                $_SESSION["role"] = $role;
                                $_SESSION["user_id"] = $newUserId;
                                $_SESSION["email"] = $email;
                                $message = "会員登録が完了しました。";
                                $redirectUrl = "my.php";
                            }
                        }
                    }
                }
            } else {
                $loginStmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
                if ($loginStmt === false) {
                    $errors[] = "ログイン処理の準備に失敗しました。";
                } else {
                    $loginStmt->bind_param("s", $email);
                    $loginStmt->execute();
                    $result = $loginStmt->get_result();
                    $user = $result->fetch_assoc();
                    $result->free();
                    $loginStmt->close();

                    if ($user === null) {
                        $errors[] = "メールアドレスまたはパスワードが正しくありません。";
                    } else {
                        $isValidPassword = password_verify($password, (string) $user["password"]) || $password === (string) $user["password"];
                        if (!$isValidPassword) {
                            $errors[] = "メールアドレスまたはパスワードが正しくありません。";
                        } else {
                            $loginUserId = (int) $user["id"];
                            $_SESSION["name"] = (string) $user["name"];
                            $_SESSION["role"] = (string) $user["role"];
                            $_SESSION["user_id"] = $loginUserId;
                            $_SESSION["email"] = $email;
                            $message = "ログインが完了しました。";
                            $redirectUrl = "my.php";
                        }
                    }
                }
            }
        }
    }
}

if ($conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

if ($redirectUrl !== "") {
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ログイン成功</title>
<meta http-equiv="refresh" content="<?php echo (int) $redirectWaitSeconds; ?>;url=<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, "UTF-8"); ?>">
<link rel="stylesheet" href="common.css">
</head>
<body class="login-page">
<div class="page-wrap">
<table class="x1 table-like top-table">
<tr><th class="x2">ログイン成功</th></tr>
<tr>
    <td>
        <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?><br>
        <?php echo (int) $redirectWaitSeconds; ?>秒後に個人ページへ移動します。<br>
        すぐに移動する場合は <a href="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, "UTF-8"); ?>">こちら</a> を押してください。
    </td>
</tr>
</table>
</div>
</body>
</html>
<?php
    exit;
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
<title>ログイン / 会員登録</title>
<link rel="stylesheet" href="common.css">
</head>
<body class="login-page">
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
            <a href="logout.php">logout</a>
        <?php } ?>
    </div>
</div>

<div class="top-hero">
    <h1>ログイン / 会員登録</h1>
    <p>求職者・企業のどちらでも、ここから利用を開始できます。</p>
</div>

<?php if (count($errors) > 0) { ?>
<div class="message-error">
    <?php foreach ($errors as $error) { ?>
    <div><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
    <?php } ?>
</div>
<?php } ?>

<?php if ($message !== "") { ?>
<p class="message-success"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
<?php } ?>

<table class="x1 table-like top-table">
<tr>
    <th colspan="2" class="x2">
        <div class="auth-header-tabs">
            <a href="login.php?tab=login" class="auth-tab-title <?php echo $activeTab === "login" ? "is-active" : ""; ?>">ログイン</a>
            <a href="login.php?tab=register" class="auth-tab-title <?php echo $activeTab === "register" ? "is-active" : ""; ?>">会員登録</a>
        </div>
    </th>
</tr>
<tr>
    <td colspan="2">
        <?php if ($activeTab === "register") { ?>
        <form action="login.php?tab=register" method="post">
            <input type="hidden" name="mode" value="register">
            <div class="form-row">
                氏名<br>
                <input type="text" name="name" class="xx" value="<?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                メールアドレス<br>
                <input type="email" name="email" class="xx" value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                パスワード<br>
                <input type="password" name="password" class="xx">
            </div>
            <div class="form-row">
                電話番号<br>
                <input type="text" name="phone" class="xx" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                ロール<br>
                <select name="role" class="xx">
                    <option value="job_seeker" <?php echo $role === "job_seeker" ? "selected" : ""; ?>>求職者</option>
                    <option value="company" <?php echo $role === "company" ? "selected" : ""; ?>>企業</option>
                </select>
            </div>
            <div class="form-actions">
                <input type="submit" value="会員登録" class="x4">
            </div>
        </form>
        <?php } ?>

        <?php if ($activeTab === "login") { ?>
        <form action="login.php?tab=login" method="post">
            <input type="hidden" name="mode" value="login">
            <div class="form-row">
                メールアドレス<br>
                <input type="email" name="email" class="xx" value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="form-row">
                パスワード<br>
                <input type="password" name="password" class="xx">
            </div>
            <div class="form-actions">
                <input type="submit" value="ログイン" class="x4">
            </div>
        </form>
        <?php } ?>
    </td>
</tr>
</table>
</div>
</body>
</html>
