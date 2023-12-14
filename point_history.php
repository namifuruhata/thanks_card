<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// ポイント履歴を表示する関数
function displayPointHistory($name, $email) {
    $file = fopen('data/points_history.csv', 'r');
    if ($file === false) {
        echo "ポイント履歴ファイルを開けませんでした。";
        return;
    }

    $history = array();
    while ($row = fgetcsv($file)) {
        if ($row[0] == $name && $row[2] == $email) { // 名前とメールアドレスの両方をチェック
            $history[] = $row;
        }
    }
    fclose($file);

    // 履歴がある場合は表示、ない場合はメッセージを表示
    if (count($history) > 0) {
        echo '<div class="card">';
        echo "<h2>{$name}さんのポイント履歴</h2>";
        echo "<ul>";
        foreach ($history as $entry) {
            echo "<li>日時: " . htmlspecialchars($entry[3]) . ", ポイント: " . htmlspecialchars($entry[1]) . "</li>";
        }
        echo "</ul>";
        echo '</div>';
    } else {
        echo '<div class="card">';
        echo "{$name}さんの履歴はありません。";
        echo '</div>';
    }
}

// ポイント履歴表示用フォームからのデータを取得
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userName = $_POST['name'] ?? '';
    $userEmail = $_POST['email'] ?? '';
    displayPointHistory($userName, $userEmail);

}

if (isset($_POST['action']) && $_POST['action'] == 'fetch_completed_cards') {
    $email = $_POST['email'];
    $completedCards = [];

    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if ($row[1] == $email && intval($row[2]) >= 18) { // ポイントが18以上のカードのみ抽出
            $completedCards[] = $row[0];
        }
    }
    fclose($file);

    echo json_encode($completedCards); // JSON形式でクライアントに送信
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'exchange_card') {
    $cardName = $_POST['cardName'];
    $email = $_POST['email'];

    // CSVファイルを読み込み、更新する
    $tempFile = 'data/users_temp.csv';
    $file = fopen('data/users.csv', 'r');
    $temp = fopen($tempFile, 'w');
    while ($row = fgetcsv($file)) {
        if ($row[0] == $cardName && $row[1] == $email) {
            $row[2] = 0; // ポイントをリセット
        }
        fputcsv($temp, $row);
    }
    fclose($file);
    fclose($temp);
    rename($tempFile, 'data/users.csv');

    echo "カード「{$cardName}」が交換されました。";
    exit;
}


// ユーザーのメールアドレスを取得
$loggedInEmail = '';
if (isset($_POST['email'])) {
    $loggedInEmail = $_POST['email'];
}

// CSVファイルを読み込み、ログインしたユーザーのアドレスと一致するカードを探す
$userCards = [];
if ($loggedInEmail != '') {
    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if (isset($row[3]) && $row[3] == $loggedInEmail) {
            $userCards[] = ['name' => $row[0], 'email' => $row[1], 'point' => $row[2]]; // カードの名前とアドレスを配列に追加
        }
    }
    fclose($file);
}

// HTMLでカード一覧を表示
foreach ($userCards as $card) {
 
    // カードの詳細を表示するためのリンクまたはボタンをここに配置
    echo '</div>';
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ポイント履歴の表示</title>
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

      document.addEventListener('DOMContentLoaded', (event) => {
        firebase.auth().onAuthStateChanged((user) => {
            if (user) {
                var emailField = document.getElementById('email-field');
                if (emailField) {
                    emailField.value = user.email; // メールアドレスをセット
                    fetchUserCards(user.email); // ユーザーカードを取得
                    fetchCompletedCards(user.email); // 18ポイントに達したカードを取得
                }
            } else {
                window.location.href = 'top.php';
            }
        });
      });


function fetchUserCards(email) {
    fetch('fetch_user_cards.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(cards => {
        var userCardsList = document.getElementById('user-cards-list');
        userCardsList.innerHTML = '';
        cards.forEach(function(card) {
            var cardItem = document.createElement('li');
            cardItem.textContent = '名前: ' + card.name + ', アドレス: ' + card.email + ', ポイント: ' + card.point;
            userCardsList.appendChild(cardItem);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
}



function fetchCompletedCards(email) {
    fetch('fetch_completed_cards.php', {  // URLを新しいPHPファイルに変更
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=fetch_completed_cards&email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(cards => {
        var completedCardsList = document.getElementById('completed-cards-list');
        completedCardsList.innerHTML = '';
        cards.forEach(function(cardName) {
            var cardItem = document.createElement('li');
            var exchangeButton = document.createElement('button');
            exchangeButton.textContent = '交換完了';
            exchangeButton.onclick = function() { exchangeCard(cardName, email); };
            cardItem.textContent = cardName;
            cardItem.appendChild(exchangeButton);
            completedCardsList.appendChild(cardItem);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function exchangeCard(cardName, email) {
    fetch('point_history.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=exchange_card&cardName=' + encodeURIComponent(cardName) + '&email=' + encodeURIComponent(email)
    })
    .then(response => response.text())
    .then(result => {
        console.log(result); // 交換処理の結果をログ出力
        fetchCompletedCards(email); // 交換後のカード一覧を再取得
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

document.addEventListener('DOMContentLoaded', (event) => {
    // 確認: この関数がFirebase Authの状態変更後に実行されていることを確認
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
});

</script>
</head>
<body>

<form action="point_history.php" method="post">
    名前: <input type="text" name="name"><br>
    メールアドレス: <input type="email" name="email" id="email-field" readonly><br>
    <input type="submit" value="履歴表示">
</form>
<div class="card">
    <h2>18ポイント貯まったカード</h2>
    <ul id="completed-cards-list">
        <!-- ここに動的に18ポイントに達したカードが表示されます -->
    </ul>
</div>
<div class="card">
    <h2>ユーザーカード一覧</h2>
    <ul id="user-cards-list">
        <?php foreach ($userCards as $card): ?>
            <li>
                名前: <?php echo htmlspecialchars($card['name']); ?>,
                アドレス: <?php echo htmlspecialchars($card['email']); ?>,
                ポイント: <?php echo htmlspecialchars($card['point']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
</html>
