<?php
ini_set('display_errors', 'On');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = ''; // メッセージを格納する変数
$userPoints = 0; // ユーザーのポイント
$found = false; // ユーザーが見つかったかどうか
$registerMessage = ''; // ユーザー登録時のメッセージ

// ユーザーカード一覧取得
$userCards = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        // ユーザー登録の処理
        $name = $_POST['name'];
        $email = $_POST['email'];
         $notificationEmail = $_POST['notification_email']; // 通知用メールアドレスを取得

        // 重複チェック
        $isDuplicate = false;
        $file = fopen('data/users.csv', 'r');
        while ($row = fgetcsv($file)) {
            if ($row[0] == $name && $row[1] == $email) {
                $isDuplicate = true;
                break;
            }
        }
        fclose($file);

        if ($isDuplicate) {
            $registerMessage = "同じ名前のユーザーがこのメールアドレスで既に存在します。<br>";
        } else {
            // CSVファイルに追加
            $file = fopen('data/users.csv', 'a');
            fputcsv($file, [$name, $email, 0, $notificationEmail]);  // 初期ポイントは0
            fclose($file);

            $registerMessage = "登録が完了しました。<br>";
        }
    
} elseif (isset($_POST['action']) && $_POST['action'] == 'add_points') {
    // ポイント追加の処理
    $name = $_POST['name'];
    $email = $_POST['email']; // メールアドレスもパラメータとして使用
    $pointsToAdd = $_POST['points'];

    $usersFile = 'data/users.csv';
    $historyFile = 'data/points_history.csv';
    $tempFile = 'data/users_temp.csv';
    $updated = false;

    // 一時ファイルを開く
    $temp = fopen($tempFile, 'w');

    // ユーザーファイルを読み込み、更新する
    $file = fopen($usersFile, 'r');
    while ($row = fgetcsv($file)) {
        if ($row[0] == $name && $row[1] == $email) {
            $row[2] += $pointsToAdd; // ポイントを加算
            $updated = true;
            // 履歴に記録
            $history = fopen($historyFile, 'a');
            fputcsv($history, [$name, $pointsToAdd, $email, date('Y-m-d H:i:s')]);
            fclose($history);
        }
        fputcsv($temp, $row);
    }
    fclose($file);
    fclose($temp);

    // 元のファイルを更新
    rename($tempFile, $usersFile);
if ($updated) {
    // CSVファイルから通知用メールアドレスを取得
    $notificationEmail = $row[3]; // CSVの4番目のカラムに通知用メールアドレスがあると仮定

    // ポイントが追加されたときにメール送信
    $to = $notificationEmail; // 通知を受け取るメールアドレス
$subject = 'ポイント加算通知';
$message = "ユーザー: $name\nポイントが追加されました。現在のポイント: " . $row[2];
$headers = 'From: nami.furuhata@horizon-cg.com' . "\r\n"; // 送信者のメールアドレスを設定


    if (!mail($notificationEmail, $subject, $message)) {
        error_log("メール送信に失敗しました。宛先: $notificationEmail");
    }
}
}
}
// ユーザーカード一覧を取得
if (!empty($_POST['email'])) {
    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if ($row[1] == $_POST['email']) {
            $userCards[] = $row[0]; // カードの名前を配列に追加
        }
    }
    fclose($file);
}

// ポイント情報を取得する処理を追加
if (isset($_POST['action']) && $_POST['action'] == 'check_balance') {
    $name = $_POST['name'];
    $email = $_POST['email'];

    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if ($row[0] == $name && $row[1] == $email) {
            $userPoints = $row[2];
            $found = true;
            break;
        }
    }
    fclose($file);

    if ($found) {
        // ユーザーのポイント情報を返す
        echo json_encode(['points' => $userPoints]);
        exit;
    }
}
if (isset($_POST['action']) && $_POST['action'] == 'fetch_cards') {
    $email = $_POST['email'];
    $userCards = [];

    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if ($row[1] == $email && $row[2] < 18) { // ポイントが18未満のカードのみを取得
            $userCards[] = $row[0];
        }
    }
    fclose($file);

    echo json_encode($userCards);
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ポイントカード</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://www.gstatic.com/firebasejs/8.6.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.1/firebase-auth.js"></script>
    <script>
        var firebaseConfig = {
            apiKey: 
            authDomain: "point-card-5b97a.firebaseapp.com",
            // ... その他の設定
        };
        firebase.initializeApp(firebaseConfig);

        window.onload = function() {
            firebase.auth().onAuthStateChanged((user) => {
                if (user) {
                    var emailField = document.getElementById('email-field');
                    if (emailField) {
                        emailField.value = user.email;
                        fetchUserCards(user.email);
                    }
                } else {
                    window.location.href = 'top.php';
                }
            });
        };

        function fetchUserCards(email) {
            // カード一覧を取得して表示する関数
            fetch('main2.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fetch_cards&email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(cards => {
                var userCardsList = document.getElementById('user-cards-list');
                userCardsList.innerHTML = '';
                cards.forEach(function(cardName) {
                    var cardItem = document.createElement('li');
                    var cardLink = document.createElement('a');
                    cardLink.href = 'javascript:void(0);';
                    cardLink.className = 'check-card';
                    cardLink.setAttribute('data-name', cardName);
                    cardLink.textContent = cardName;
                    cardItem.appendChild(cardLink);
                    userCardsList.appendChild(cardItem);
                });
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('check-card')) {
                var name = event.target.getAttribute('data-name');
                var email = document.getElementById('email-field').value;

                fetch('main2.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=check_balance&name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    updatePointCardVisual(data.points, name, email);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });

function updatePointCardVisual(points, name, email) {
    var pointCardHtml = '<div class="point-card">';
    pointCardHtml += '<div class="card-name">' + name + 'カード</div>'; // カード名のコンテナを追加
    pointCardHtml += '<div class="point-grid">'; // スタンプのグリッド用のコンテナ

    for (var i = 1; i <= 18; i++) {
        pointCardHtml += '<div class="point-slot ' + (i <= points ? 'stamped' : '') + '" data-point-number="' + i + '" data-name="' + name + '" data-email="' + email + '">' + i + '</div>';
    }
    pointCardHtml += '</div></div>'; // point-gridとpoint-cardの閉じタグ

    document.querySelector('#point-card-container').innerHTML = pointCardHtml;

    if (points >= 18) {
        showModal();
        createNewCard(name, email);
    }
    // ポイントスロットにイベントリスナーを追加
    var slots = document.querySelectorAll('.point-slot');
    slots.forEach(slot => {
        slot.addEventListener('click', function(event) {
            if (!event.target.classList.contains('stamped')) {
                addPoint(name, email, 1); // 1ポイントを加算
            }
        });
    });
}


       function addPoint(name, email, pointsToAdd) {
    fetch('main2.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_points&name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email) + '&points=' + pointsToAdd
    })
    .then(response => response.text())
    .then(data => {
        console.log(data); // レスポンスのログを出力
        // ポイント加算後にポイント情報を再取得
        fetch('main2.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_balance&name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            updatePointCardVisual(data.points, name, email); // ポイントカードのビジュアルを更新
            if (data.points >= 18) {
                showModal();
                createNewCard(name, email);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function showModal() {
    var modal = document.getElementById("modal");
    var span = document.getElementsByClassName("close")[0];
    modal.style.display = "block";
    span.onclick = function() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
}


    </script>
</head>
<body>
    <div class="point_group_ue">
    <div class="card">
        <h2>ポイントカード登録</h2>
        <form action="main2.php" method="post">
            <input type="hidden" name="action" value="register">
            カードの名前: <input type="text" name="name"><br>
            あなたのメールアドレス: <input type="email" name="email" id="email-field" readonly><br>
            相手のメールアドレス: <input type="email" name="notification_email"><br>
            <input type="submit" value="登録">
        </form>
        <?php echo $registerMessage; ?>
    </div>

    <div class="card">
        <h2>ポイント履歴確認</h2>
        <a href="point_history.php" class="button-link">履歴を見る</a>
    </div>
</div>

    <div class="card">
        <h2>ポイントカード一覧</h2>
        <ul id="user-cards-list">
            <!-- ここにユーザーカード一覧が動的に追加されます -->
        </ul>
    </div>
    <div id="point-card-container">
    <!-- ここに動的にポイントカードが表示されます -->
    </div>

    
<!-- モーダルポップアップ -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <p>おめでとう！次のカードを作成してね。<br>
        （同じ名前のカードは作れないので、カード名の後に何枚目か番号をつけてね。）
        </p>
    </div>
</div>
</body>
</html>
