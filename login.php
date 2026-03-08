<?php
session_start();

$message = "";
$errors = [];

$mode = "";
$name = "";
$email = "";
$phone = "";
$role = "job_seeker";
$password = "";

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // フォーム入力を受け取り、必要な項目だけ trim する。
    if (isset($_POST["mode"])) {
        $mode = trim((string) $_POST["mode"]);
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
                                $_SESSION["name"] = $name;
                                $_SESSION["role"] = $role;
                                $message = "登録してログインしました。";
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
                            $_SESSION["name"] = (string) $user["name"];
                            $_SESSION["role"] = (string) $user["role"];
                            $message = "ログインしました。";
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

<center>
<table class="x1 table-like">
<tr><th colspan="2" class="x2"><center>ログイン / 会員登録</center></th></tr>
<tr>
    <td colspan="2">
        <form action="login.php" method="post">
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
            <input type="submit" name="mode" value="register" class="x4">
            <input type="submit" name="mode" value="login" class="x4">
            </div>
        </form>
    </td>
</tr>
</table>
</center>
</body>
</html>
