<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ff_ttscn_top');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    if ($conn->connect_error) {
        die(json_encode(['error' => '数据库连接失败: ' . $conn->connect_error]));
    }
    return $conn;
}

define('TOTAL_WEEKS', 18);

function getSemesterConfig() {
    $configFile = __DIR__ . '/semester_config.json';
    $default = [
        'semester_start' => '2025-02-24',
        'current_week_override' => null,
    ];
    if (file_exists($configFile)) {
        $data = json_decode(file_get_contents($configFile), true);
        if ($data) return array_merge($default, $data);
    }
    return $default;
}

function saveSemesterConfig($config) {
    $configFile = __DIR__ . '/semester_config.json';
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getCurrentWeek() {
    $cfg = getSemesterConfig();
    if (!empty($cfg['current_week_override'])) {
        return (int)$cfg['current_week_override'];
    }
    $info = getWeekInfo(date('Y-m-d'), $cfg['semester_start']);
    return max(1, $info['week']);
}

function getWeekInfo($date, $semesterStart = null) {
    if ($semesterStart === null) {
        $cfg = getSemesterConfig();
        $semesterStart = $cfg['semester_start'];
    }
    $startDt = new DateTime($semesterStart);
    $startDow = (int)$startDt->format('N');
    if ($startDow > 1) {
        $startDt->modify('-' . ($startDow - 1) . ' days');
    }
    $currentDt = new DateTime($date);
    if ($currentDt < $startDt) {
        return ['week' => 1, 'dow' => (int)$currentDt->format('N')];
    }
    $diff = $startDt->diff($currentDt);
    $days = (int)$diff->days;
    $week = (int)floor($days / 7) + 1;
    $dow  = (int)$currentDt->format('N');
    return ['week' => $week, 'dow' => $dow];
}

define('CURRENT_WEEK', getCurrentWeek());

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
        'class_id' => $_SESSION['class_id'],
    ];
}

function isSuperAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'super_admin';
}

function getUserClassId() {
    $user = getCurrentUser();
    return $user ? (int)$user['class_id'] : 0;
}

function loginUser($username, $password) {
    $conn = getDB();
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);
    $result = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$password' LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['username']  = $row['username'];
        $_SESSION['role']      = $row['role'];
        $_SESSION['class_id']  = $row['class_id'];
        $conn->close();
        return true;
    }
    $conn->close();
    return false;
}

function logoutUser() {
    $_SESSION = [];
    session_destroy();
    session_start();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>
