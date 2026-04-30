<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$input = [];
if ($method === 'PUT' || $method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) $input = $data;
}

$conn = getDB();

function getClassFilter() {
    $user = getCurrentUser();
    if (!$user) return 0;
    if ($user['role'] === 'super_admin') {
        return (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
    }
    return (int)$user['class_id'];
}

function requireAuth() {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'msg' => '请先登录', 'need_login' => true]);
        exit;
    }
}

function requireSuper() {
    if (!isSuperAdmin()) {
        echo json_encode(['success' => false, 'msg' => '权限不足，需要总管理者权限']);
        exit;
    }
}

switch ($action) {

    case 'login':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = $postData['username'] ?? '';
        $password = $postData['password'] ?? '';
        if (!$username || !$password) {
            echo json_encode(['success' => false, 'msg' => '请输入账号和密码']);
            break;
        }
        if (loginUser($username, $password)) {
            $user = getCurrentUser();
            echo json_encode(['success' => true, 'msg' => '登录成功', 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'msg' => '账号或密码错误']);
        }
        break;

    case 'logout':
        logoutUser();
        break;

    case 'get_current_user':
        if (isLoggedIn()) {
            echo json_encode(['success' => true, 'user' => getCurrentUser()]);
        } else {
            echo json_encode(['success' => false, 'msg' => '未登录']);
        }
        break;

    case 'get_students':
        requireAuth();
        $classId = getClassFilter();
        $where = $classId ? "WHERE s.class_id = $classId" : '';
        $result = $conn->query("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id $where ORDER BY s.student_id");
        $students = [];
        while ($row = $result->fetch_assoc()) $students[] = $row;
        echo json_encode(['success' => true, 'data' => $students]);
        break;

    case 'add_attendance':
        requireAuth();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $student_id   = $conn->real_escape_string($postData['student_id'] ?? '');
        $student_name = $conn->real_escape_string($postData['student_name'] ?? '');
        $record_date  = $conn->real_escape_string($postData['record_date'] ?? '');
        $status       = $conn->real_escape_string($postData['status'] ?? '');
        $reason       = $conn->real_escape_string($postData['reason'] ?? '');
        $periods      = $postData['periods'] ?? [1];

        if (!$student_id || !$record_date || !$status) {
            echo json_encode(['success' => false, 'msg' => '请填写必要信息']);
            break;
        }

        $dow = (int)date('N', strtotime($record_date));
        if ($dow > 5) {
            echo json_encode(['success' => false, 'msg' => '周末不记录考勤']);
            break;
        }

        $info = getWeekInfo($record_date);
        $week_number = $info['week'];

        if ($week_number < 1 || $week_number > TOTAL_WEEKS) {
            echo json_encode(['success' => false, 'msg' => "日期超出本学期范围（第1-".TOTAL_WEEKS."周）"]);
            break;
        }

        $stuRes = $conn->query("SELECT class_id FROM students WHERE student_id='$student_id' LIMIT 1");
        $classId = 0;
        if ($stuRow = $stuRes->fetch_assoc()) {
            $classId = (int)$stuRow['class_id'];
        }

        if (!is_array($periods)) $periods = [$periods];

        $inserted = 0;
        $skipped = 0;
        foreach ($periods as $p) {
            $p = (int)$p;
            if ($p < 1 || $p > 5) continue;
            $check = $conn->query("SELECT id FROM attendance WHERE student_id='$student_id' AND record_date='$record_date' AND period=$p LIMIT 1");
            if ($check->num_rows > 0) {
                $skipped++;
                continue;
            }
            $sql = "INSERT INTO attendance (student_id, student_name, class_id, record_date, week_number, day_of_week, period, status, reason)
                    VALUES ('$student_id', '$student_name', $classId, '$record_date', $week_number, $dow, $p, '$status', '$reason')";
            if ($conn->query($sql)) $inserted++;
        }

        $msg = "考勤录入成功（第{$week_number}周），新增{$inserted}条记录";
        if ($skipped > 0) $msg .= "，{$skipped}条已存在跳过";
        echo json_encode(['success' => true, 'msg' => $msg, 'inserted' => $inserted, 'skipped' => $skipped]);
        break;

    case 'get_overview':
        requireAuth();
        $week = (int)($_GET['week'] ?? CURRENT_WEEK);
        $classId = getClassFilter();
        $where = ["week_number = $week"];
        if ($classId) $where[] = "class_id = $classId";
        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $result = $conn->query("SELECT * FROM attendance $whereStr ORDER BY day_of_week, period, student_id");
        $records = [];
        while ($row = $result->fetch_assoc()) $records[] = $row;
        echo json_encode(['success' => true, 'data' => $records, 'week' => $week]);
        break;

    case 'get_all':
        requireAuth();
        $page  = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $conn->real_escape_string($_GET['search'] ?? '');
        $filter_week = (int)($_GET['week'] ?? 0);
        $filter_status = $conn->real_escape_string($_GET['status'] ?? '');
        $classId = getClassFilter();

        $where = [];
        if ($search) $where[] = "(student_id LIKE '%$search%' OR student_name LIKE '%$search%')";
        if ($filter_week) $where[] = "week_number = $filter_week";
        if ($filter_status) $where[] = "status = '$filter_status'";
        if ($classId) $where[] = "class_id = $classId";
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $conn->query("SELECT COUNT(*) as cnt FROM attendance $whereStr")->fetch_assoc()['cnt'];
        $result = $conn->query("SELECT * FROM attendance $whereStr ORDER BY record_date DESC, student_id, period LIMIT $limit OFFSET $offset");
        $records = [];
        while ($row = $result->fetch_assoc()) $records[] = $row;
        echo json_encode(['success' => true, 'data' => $records, 'total' => $total, 'page' => $page, 'limit' => $limit]);
        break;

    case 'get_one':
        requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $result = $conn->query("SELECT * FROM attendance WHERE id = $id LIMIT 1");
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'msg' => '记录不存在']);
        }
        break;

    case 'update_attendance':
        requireAuth();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id      = (int)($postData['id'] ?? 0);
        $status  = $conn->real_escape_string($postData['status'] ?? '');
        $reason  = $conn->real_escape_string($postData['reason'] ?? '');
        $record_date = $conn->real_escape_string($postData['record_date'] ?? '');
        $period = (int)($postData['period'] ?? 0);

        if (!$id) { echo json_encode(['success' => false, 'msg' => '缺少记录ID']); break; }

        $sets = [];
        if ($status) $sets[] = "status='$status'";
        if ($reason !== null) $sets[] = "reason='$reason'";
        if ($period > 0 && $period <= 5) $sets[] = "period=$period";
        if ($record_date) {
            $info = getWeekInfo($record_date);
            $dow = (int)date('N', strtotime($record_date));
            $sets[] = "record_date='$record_date'";
            $sets[] = "week_number={$info['week']}";
            $sets[] = "day_of_week=$dow";
        }

        if (empty($sets)) { echo json_encode(['success' => false, 'msg' => '没有要更新的字段']); break; }

        $sql = "UPDATE attendance SET " . implode(',', $sets) . " WHERE id=$id";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'msg' => '更新成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '更新失败: ' . $conn->error]);
        }
        break;

    case 'delete_attendance':
        requireAuth();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($postData['id'] ?? $_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'msg' => '缺少记录ID']); break; }
        if ($conn->query("DELETE FROM attendance WHERE id=$id")) {
            echo json_encode(['success' => true, 'msg' => '删除成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '删除失败']);
        }
        break;

    case 'add_student':
        requireAuth();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $student_id  = $conn->real_escape_string($postData['student_id'] ?? '');
        $name        = $conn->real_escape_string($postData['name'] ?? '');
        $class_id    = (int)($postData['class_id'] ?? 0);
        if (!$class_id) $class_id = getUserClassId();
        if (!$student_id || !$name) { echo json_encode(['success' => false, 'msg' => '请填写学号和姓名']); break; }
        if (!$class_id) { echo json_encode(['success' => false, 'msg' => '缺少班级信息']); break; }
        $sql = "INSERT INTO students (student_id, name, class_id) VALUES ('$student_id','$name',$class_id)";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'msg' => '学生添加成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '添加失败，学号可能已存在']);
        }
        break;

    case 'delete_student':
        requireAuth();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $student_id = $conn->real_escape_string($postData['student_id'] ?? '');
        if (!$student_id) { echo json_encode(['success' => false, 'msg' => '缺少学号']); break; }
        $conn->query("DELETE FROM attendance WHERE student_id='$student_id'");
        if ($conn->query("DELETE FROM students WHERE student_id='$student_id'")) {
            echo json_encode(['success' => true, 'msg' => '删除成功（含该学生所有考勤记录）']);
        } else {
            echo json_encode(['success' => false, 'msg' => '删除失败']);
        }
        break;

    case 'export_students':
        requireAuth();
        $classId = getClassFilter();
        if (!$classId) { echo json_encode(['success' => false, 'msg' => '请指定班级']); break; }
        $result = $conn->query("SELECT student_id, name FROM students WHERE class_id=$classId ORDER BY student_id");
        $students = [];
        while ($row = $result->fetch_assoc()) $students[] = $row;
        $classNameRes = $conn->query("SELECT class_name FROM classes WHERE id=$classId LIMIT 1");
        $className = $classNameRes->fetch_assoc()['class_name'] ?? '';
        echo json_encode(['success' => true, 'data' => $students, 'class_name' => $className, 'class_id' => $classId, 'count' => count($students)]);
        break;

    case 'import_students':
        requireAuth();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $class_id = (int)($postData['class_id'] ?? 0);
        $importData = $postData['students'] ?? [];
        if (!$class_id) $class_id = getUserClassId();
        if (!$class_id) { echo json_encode(['success' => false, 'msg' => '缺少班级信息']); break; }
        if (!is_array($importData) || empty($importData)) { echo json_encode(['success' => false, 'msg' => '无有效数据']); break; }

        $inserted = 0;
        $skipped = 0;
        foreach ($importData as $s) {
            $sid = $conn->real_escape_string($s['student_id'] ?? '');
            $name = $conn->real_escape_string($s['name'] ?? '');
            if (!$sid || !$name) { $skipped++; continue; }
            $check = $conn->query("SELECT id FROM students WHERE student_id='$sid' LIMIT 1");
            if ($check->num_rows > 0) { $skipped++; continue; }
            $sql = "INSERT INTO students (student_id, name, class_id) VALUES ('$sid','$name',$class_id)";
            if ($conn->query($sql)) $inserted++; else $skipped++;
        }
        echo json_encode(['success' => true, 'msg' => "导入完成：新增{$inserted}人，跳过{$skipped}人", 'inserted' => $inserted, 'skipped' => $skipped]);
        break;

    case 'get_stats':
        requireAuth();
        $week = (int)($_GET['week'] ?? 0);
        $classId = getClassFilter();
        $whereArr = [];
        if ($week) $whereArr[] = "week_number = $week";
        if ($classId) $whereArr[] = "class_id = $classId";
        $whereWeek = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';
        $stats = $conn->query("SELECT status, COUNT(*) as cnt FROM attendance $whereWeek GROUP BY status")->fetch_all(MYSQLI_ASSOC);
        $totalRecords = $conn->query("SELECT COUNT(*) as cnt FROM attendance $whereWeek")->fetch_assoc()['cnt'];
        $stuWhere = $classId ? "WHERE class_id = $classId" : '';
        $totalStudents = $conn->query("SELECT COUNT(*) as cnt FROM students $stuWhere")->fetch_assoc()['cnt'];
        echo json_encode(['success' => true, 'stats' => $stats, 'total' => $totalRecords, 'students' => $totalStudents]);
        break;

    case 'get_weekly_summary':
        requireAuth();
        $classId = getClassFilter();
        $rows = [];
        for ($w = 1; $w <= TOTAL_WEEKS; $w++) {
            $classWhere = $classId ? " AND class_id=$classId" : '';
            $res = $conn->query("SELECT status, COUNT(*) as cnt FROM attendance WHERE week_number=$w $classWhere GROUP BY status");
            $c = ['late' => 0, 'absent' => 0, 'leave' => 0, 'early_leave' => 0];
            while ($r = $res->fetch_assoc()) $c[$r['status']] = (int)$r['cnt'];
            $rows[] = ['week' => $w, 'late' => $c['late'], 'absent' => $c['absent'], 'leave' => $c['leave'], 'early_leave' => $c['early_leave'],
                       'total' => $c['late'] + $c['absent'] + $c['leave'] + $c['early_leave']];
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'get_semester_config':
        requireAuth();
        $cfg = getSemesterConfig();
        $currentWeek = getCurrentWeek();
        $cfg['current_week_auto'] = $currentWeek;
        $cfg['total_weeks'] = TOTAL_WEEKS;
        $cfg['out_of_range'] = ($currentWeek > TOTAL_WEEKS);
        echo json_encode(['success' => true, 'data' => $cfg]);
        break;

    case 'save_semester_config':
        requireSuper();
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $cfg = getSemesterConfig();
        if (!empty($postData['semester_start'])) {
            $dt = DateTime::createFromFormat('Y-m-d', $postData['semester_start']);
            if (!$dt) { echo json_encode(['success' => false, 'msg' => '日期格式错误']); break; }
            $cfg['semester_start'] = $postData['semester_start'];
        }
        if (array_key_exists('current_week_override', $postData)) {
            $ov = (int)$postData['current_week_override'];
            $cfg['current_week_override'] = ($ov >= 1 && $ov <= TOTAL_WEEKS) ? $ov : null;
        }
        if (saveSemesterConfig($cfg) !== false) {
            echo json_encode(['success' => true, 'msg' => '配置保存成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '保存失败，请检查文件权限']);
        }
        break;

    case 'get_classes':
        $result = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) as student_count, u.username as manager_username, u.id as manager_user_id FROM classes c LEFT JOIN users u ON u.class_id = c.id AND u.role = 'class_manager' ORDER BY c.id");
        $classes = [];
        while ($row = $result->fetch_assoc()) $classes[] = $row;
        echo json_encode(['success' => true, 'data' => $classes]);
        break;

    case 'add_class':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $class_name = $conn->real_escape_string($postData['class_name'] ?? '');
        $manager_user_id = (int)($postData['manager_user_id'] ?? 0);

        if (!$class_name) { echo json_encode(['success' => false, 'msg' => '请填写班级名称']); break; }

        $checkClass = $conn->query("SELECT id FROM classes WHERE class_name='$class_name' LIMIT 1");
        if ($checkClass->num_rows > 0) {
            echo json_encode(['success' => false, 'msg' => '班级名称已存在']);
            break;
        }

        if ($conn->query("INSERT INTO classes (class_name) VALUES ('$class_name')")) {
            $newClassId = $conn->insert_id;
            if ($manager_user_id) {
                $conn->query("UPDATE users SET class_id=$newClassId, role='class_manager' WHERE id=$manager_user_id");
                echo json_encode(['success' => true, 'msg' => '班级创建成功，已关联管理者', 'class_id' => $newClassId]);
            } else {
                echo json_encode(['success' => true, 'msg' => '班级创建成功', 'class_id' => $newClassId]);
            }
        } else {
            echo json_encode(['success' => false, 'msg' => '创建失败: ' . $conn->error]);
        }
        break;

    case 'update_class':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $class_id = (int)($postData['class_id'] ?? 0);
        if (!$class_id) { echo json_encode(['success' => false, 'msg' => '缺少班级ID']); break; }

        $sets = [];
        if (!empty($postData['class_name'])) {
            $class_name = $conn->real_escape_string($postData['class_name']);
            $checkClass = $conn->query("SELECT id FROM classes WHERE class_name='$class_name' AND id!=$class_id LIMIT 1");
            if ($checkClass->num_rows > 0) {
                echo json_encode(['success' => false, 'msg' => '班级名称已存在']);
                break;
            }
            $sets[] = "class_name='$class_name'";
        }
        if (array_key_exists('manager_user_id', $postData)) {
            $oldMgrRes = $conn->query("SELECT id FROM users WHERE class_id=$class_id AND role='class_manager' LIMIT 1");
            while ($oldMgr = $oldMgrRes->fetch_assoc()) {
                $conn->query("UPDATE users SET class_id=NULL WHERE id=" . (int)$oldMgr['id']);
            }
            $manager_user_id = (int)$postData['manager_user_id'];
            if ($manager_user_id > 0) {
                $conn->query("UPDATE users SET class_id=$class_id, role='class_manager' WHERE id=$manager_user_id");
            }
        }

        if (!empty($sets)) {
            $sql = "UPDATE classes SET " . implode(',', $sets) . " WHERE id=$class_id";
            $conn->query($sql);
        }

        echo json_encode(['success' => true, 'msg' => '班级更新成功']);
        break;

    case 'delete_class':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $class_id = (int)($postData['class_id'] ?? 0);
        if (!$class_id) { echo json_encode(['success' => false, 'msg' => '缺少班级ID']); break; }
        $conn->query("DELETE FROM attendance WHERE class_id=$class_id");
        $conn->query("DELETE FROM students WHERE class_id=$class_id");
        $conn->query("UPDATE users SET class_id=NULL WHERE class_id=$class_id AND role='class_manager'");
        if ($conn->query("DELETE FROM classes WHERE id=$class_id")) {
            echo json_encode(['success' => true, 'msg' => '班级及相关数据已删除']);
        } else {
            echo json_encode(['success' => false, 'msg' => '删除失败']);
        }
        break;

    case 'get_class_managers':
        $result = $conn->query("SELECT id, username, class_id FROM users WHERE role='class_manager' ORDER BY id");
        $managers = [];
        while ($row = $result->fetch_assoc()) $managers[] = $row;
        echo json_encode(['success' => true, 'data' => $managers]);
        break;

    case 'get_users':
        $result = $conn->query("SELECT u.*, c.class_name FROM users u LEFT JOIN classes c ON u.class_id = c.id ORDER BY u.role, u.id");
        $users = [];
        while ($row = $result->fetch_assoc()) $users[] = $row;
        echo json_encode(['success' => true, 'data' => $users]);
        break;

    case 'add_user':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = $conn->real_escape_string($postData['username'] ?? '');
        $password = $conn->real_escape_string($postData['password'] ?? '');
        $role     = $conn->real_escape_string($postData['role'] ?? 'class_manager');
        $class_id = (int)($postData['class_id'] ?? 0) ?: 'NULL';

        if (!$username || !$password) { echo json_encode(['success' => false, 'msg' => '请填写账号和密码']); break; }

        $check = $conn->query("SELECT id FROM users WHERE username='$username' LIMIT 1");
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'msg' => '账号已存在']);
            break;
        }

        $sql = "INSERT INTO users (username, password, role, class_id) VALUES ('$username', '$password', '$role', $class_id)";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'msg' => '用户添加成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '添加失败: ' . $conn->error]);
        }
        break;

    case 'update_user':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($postData['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'msg' => '缺少用户ID']); break; }

        $sets = [];
        if (!empty($postData['username'])) {
            $username = $conn->real_escape_string($postData['username']);
            $sets[] = "username='$username'";
        }
        if (!empty($postData['password'])) {
            $password = $conn->real_escape_string($postData['password']);
            $sets[] = "password='$password'";
        }
        if (!empty($postData['role'])) {
            $role = $conn->real_escape_string($postData['role']);
            $sets[] = "role='$role'";
        }
        if (array_key_exists('class_id', $postData)) {
            $class_id = (int)$postData['class_id'] ?: 'NULL';
            $sets[] = "class_id=$class_id";
        }

        if (empty($sets)) { echo json_encode(['success' => false, 'msg' => '没有要更新的字段']); break; }

        $sql = "UPDATE users SET " . implode(',', $sets) . " WHERE id=$id";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'msg' => '更新成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '更新失败: ' . $conn->error]);
        }
        break;

    case 'delete_user':
        $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($postData['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'msg' => '缺少用户ID']); break; }
        if ($conn->query("DELETE FROM users WHERE id=$id")) {
            echo json_encode(['success' => true, 'msg' => '删除成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '删除失败']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'msg' => '未知操作: ' . $action]);
}

$conn->close();
?>
