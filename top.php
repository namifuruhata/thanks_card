<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>login</title>
     <link rel="stylesheet" href="style.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Firebase SDK の読み込み -->
    <script src="https://www.gstatic.com/firebasejs/8.6.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.1/firebase-auth.js"></script>
    <script>
      // Firebaseプロジェクトの設定
      var firebaseConfig = {
        apiKey: 
        authDomain: "point-card-5b97a.firebaseapp.com",
        // ... その他の設定
      };
      firebase.initializeApp(firebaseConfig);
    

      var provider = new firebase.auth.GoogleAuthProvider();

   function googleLogin() {
        firebase.auth().signInWithPopup(provider).then((result) => {
          // ログイン成功後に main2.php にリダイレクト
          window.location.href = 'main2.php';
        }).catch((error) => {
          console.error("ログインエラー", error);
        });
      }

      firebase.auth().onAuthStateChanged((user) => {
        if (user) {
          console.log("ログイン中のユーザー:", user);
        } else {
          console.log("ユーザーはログアウトしています。");
        }
      });
    </script>

    
</head>
<body>
    <div class="login_group">
    <div class="login">
    <button onclick="googleLogin()"><img src="img/login.png" alt="グーグルログイン"></button>
</div>
<div class="usagi">
  <img src="img/usagi.gif" alt="うさぎ" width=200px>
  </div>
    </div>
</body>
</html>
