<?php
session_start();

function connectDB() {
    $host = 'localhost';
    $dbname = 'asah_otak';
    $user = 'postgres';
    $password = '1234';

    $conn_string = "host=$host dbname=$dbname user=$user password=$password";
    $conn = pg_connect($conn_string);
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    return $conn;
}

function executeQuery($conn, $query, $params = []) {
    $result = pg_query_params($conn, $query, $params);
    if (!$result) {
        die("Query failed: " . pg_last_error($conn));
    }
    return pg_fetch_all($result);
}

$conn = connectDB();

if (!isset($_SESSION['current_word'])) {
    $result = executeQuery($conn, "SELECT * FROM master_kata ORDER BY RANDOM() LIMIT 1", []);
    if ($result && count($result) > 0) {
        $word = $result[0];
        $_SESSION['current_word'] = $word['kata'];
        $_SESSION['clue'] = $word['clue'];
        $_SESSION['score'] = 0;
    } else {
        die("No words found in the database.");
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guess'])) {
        $guess = strtoupper(implode('', $_POST['guess']));
        $correct_word = $_SESSION['current_word'];
        $score = 0;
        for ($i = 0; $i < strlen($correct_word); $i++) {
            if ($i == 2 || $i == 6) continue;
            if ($guess[$i] == $correct_word[$i]) {
                $score += 10;
            } else {
                $score -= 2;
            }
        }
        $_SESSION['score'] += $score;
        $message = "Poin yang anda dapat adalah " . $_SESSION['score'];
    } elseif (isset($_POST['save'])) {
        $name = pg_escape_string($conn, $_POST['name']);
        executeQuery($conn, "INSERT INTO point_game (nama_user, total_point) VALUES ($1, $2)", [$name, $_SESSION['score']]);
        session_destroy();
        header("Location: index.php");
        exit;
    } elseif (isset($_POST['retry'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permainan Tebak Kata</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold mb-4">Permainan Tebak Kata</h1>
        <p class="mb-4"><strong>Clue:</strong> <?php echo htmlspecialchars($_SESSION['clue']); ?></p>
        <form method="post" class="mb-4">
            <?php
            $word = $_SESSION['current_word'];
            for ($i = 0; $i < strlen($word); $i++) {
                $readonly = ($i == 2 || $i == 6) ? 'readonly' : '';
                $value = ($i == 2 || $i == 6) ? $word[$i] : '';
                echo "<input type='text' name='guess[]' maxlength='1' class='w-8 h-8 text-center border border-gray-300 rounded mr-1 mb-2' $readonly value='" . htmlspecialchars($value) . "' required>";
            }
            ?>
            <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Jawab</button>
        </form>
        <?php if ($message): ?>
            <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
            <form method="post" class="mb-4">
                <input type="text" name="name" placeholder="Masukkan nama Anda" class="w-full px-3 py-2 border border-gray-300 rounded mb-2" required>
                <button type="submit" name="save" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 mr-2">Simpan Poin</button>
                <button type="submit" name="retry" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Ulangi</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>