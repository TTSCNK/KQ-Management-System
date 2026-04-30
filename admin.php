<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$isSuperAdmin = isSuperAdmin();
$activeClassId = 0;

if ($isSuperAdmin && isset($_GET['class_id'])) {
    $activeClassId = (int)$_GET['class_id'];
    $_SESSION['active_class_id'] = $activeClassId;
} elseif ($isSuperAdmin && isset($_SESSION['active_class_id'])) {
    $activeClassId = (int)$_SESSION['active_class_id'];
}

if (!$isSuperAdmin) {
    $activeClassId = (int)$user['class_id'];
}

if (isset($_GET['back_to_classes']) && $isSuperAdmin) {
    unset($_SESSION['active_class_id']);
    $activeClassId = 0;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台管理 - 考勤系统</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #0f0f1a;
    --panel: #16162a;
    --surface: #1e1e35;
    --border: #2d2d4e;
    --paper: #f5f0e8;
    --text: #e8e4d9;
    --muted: #7a7a9a;
    --gold: #f0c040;
    --red: #e05252;
    --amber: #e8a040;
    --teal: #40c8a0;
    --accent: #6060e8;
    --purple: #a060e8;
    --late-bg: rgba(240,192,64,0.15);
    --absent-bg: rgba(224,82,82,0.15);
    --leave-bg: rgba(64,200,160,0.15);
    --early-leave-bg: rgba(160,96,232,0.15);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Noto Serif SC', serif;
    background: var(--ink);
    color: var(--text);
    min-height: 100vh;
  }

  .admin-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    min-height: 100vh;
  }

  .sidebar {
    background: var(--panel);
    border-right: 1px solid var(--border);
    padding: 0;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
  }
  .sidebar-logo {
    padding: 24px 20px;
    border-bottom: 1px solid var(--border);
  }
  .sidebar-logo h2 {
    font-size: 0.9rem;
    letter-spacing: 3px;
    color: var(--gold);
  }
  .sidebar-logo p {
    font-size: 0.65rem;
    color: var(--muted);
    margin-top: 4px;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 1px;
  }
  .sidebar-logo .role-tag {
    display: inline-block;
    margin-top: 6px;
    padding: 2px 8px;
    font-size: 0.65rem;
    letter-spacing: 1px;
    font-weight: 700;
  }
  .role-tag.super { background: rgba(240,192,64,0.15); color: var(--gold); border: 1px solid rgba(240,192,64,0.3); }
  .role-tag.class-m { background: rgba(64,200,160,0.15); color: var(--teal); border: 1px solid rgba(64,200,160,0.3); }
  .sidebar-nav { padding: 16px 0; }
  .nav-section {
    padding: 8px 20px 4px;
    font-size: 0.65rem;
    letter-spacing: 3px;
    color: var(--muted);
    font-family: 'JetBrains Mono', monospace;
  }
  .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 0.88rem;
    letter-spacing: 1px;
    transition: all 0.15s;
    color: var(--muted);
    border-left: 3px solid transparent;
  }
  .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
  .nav-item.active {
    color: var(--gold);
    border-left-color: var(--gold);
    background: rgba(240,192,64,0.08);
  }
  .nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }

  .sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border);
    margin-top: auto;
  }
  .back-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 0.8rem;
    letter-spacing: 1px;
    text-decoration: none;
    transition: color 0.15s;
    margin-top: 6px;
  }
  .back-link:hover { color: var(--text); }

  .main-content {
    padding: 32px;
    overflow-y: auto;
  }
  .page-section { display: none; }
  .page-section.active { display: block; }

  .page-header {
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .page-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text);
  }
  .page-subtitle {
    font-size: 0.75rem;
    letter-spacing: 3px;
    color: var(--muted);
    margin-top: 4px;
    font-family: 'JetBrains Mono', monospace;
  }

  .stat-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }
  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-top: 3px solid;
    padding: 20px;
  }
  .stat-card:nth-child(1) { border-top-color: var(--gold); }
  .stat-card:nth-child(2) { border-top-color: var(--amber); }
  .stat-card:nth-child(3) { border-top-color: var(--red); }
  .stat-card:nth-child(4) { border-top-color: var(--teal); }
  .stat-card:nth-child(5) { border-top-color: var(--purple); }
  .stat-num {
    font-family: 'JetBrains Mono', monospace;
    font-size: 2rem;
    font-weight: 600;
    line-height: 1;
  }
  .stat-label { font-size: 0.72rem; letter-spacing: 2px; color: var(--muted); margin-top: 6px; }

  .filter-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
  }
  .filter-bar input,
  .filter-bar select {
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 14px;
    font-family: 'Noto Serif SC', serif;
    font-size: 0.88rem;
    outline: none;
    transition: border-color 0.15s;
  }
  .filter-bar input:focus,
  .filter-bar select:focus { border-color: var(--gold); }
  .filter-bar select option { background: var(--panel); }
  .btn {
    padding: 8px 20px;
    border: none;
    cursor: pointer;
    font-family: 'Noto Serif SC', serif;
    font-size: 0.85rem;
    letter-spacing: 1px;
    transition: all 0.15s;
  }
  .btn-primary { background: var(--gold); color: var(--ink); font-weight: 700; }
  .btn-primary:hover { background: #ffd060; }
  .btn-danger  { background: var(--red); color: white; }
  .btn-danger:hover { background: #f06060; }
  .btn-edit    { background: var(--accent); color: white; }
  .btn-edit:hover { background: #7070f0; }
  .btn-sm { padding: 4px 12px; font-size: 0.8rem; }

  .admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
  }
  .admin-table th {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 12px 16px;
    text-align: left;
    font-size: 0.72rem;
    letter-spacing: 2px;
    color: var(--muted);
    font-family: 'JetBrains Mono', monospace;
    font-weight: 500;
  }
  .admin-table td {
    border: 1px solid var(--border);
    padding: 11px 16px;
    vertical-align: middle;
    background: var(--panel);
    transition: background 0.1s;
  }
  .admin-table tr:hover td { background: var(--surface); }
  .admin-table .actions { display: flex; gap: 6px; }

  .tag {
    display: inline-block;
    padding: 2px 10px;
    font-size: 0.75rem;
    letter-spacing: 1px;
    font-weight: 700;
    border-radius: 0;
  }
  .tag.late        { background: var(--late-bg); color: var(--amber); border: 1px solid rgba(240,192,64,0.4); }
  .tag.absent      { background: var(--absent-bg); color: var(--red); border: 1px solid rgba(224,82,82,0.4); }
  .tag.leave       { background: var(--leave-bg); color: var(--teal); border: 1px solid rgba(64,200,160,0.4); }
  .tag.early_leave { background: var(--early-leave-bg); color: var(--purple); border: 1px solid rgba(160,96,232,0.4); }

  .pagination {
    display: flex;
    gap: 6px;
    margin-top: 16px;
    align-items: center;
    justify-content: flex-end;
  }
  .page-btn {
    padding: 6px 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.82rem;
    transition: all 0.15s;
  }
  .page-btn:hover, .page-btn.active { background: var(--gold); color: var(--ink); border-color: var(--gold); }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 200;
    display: none;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: var(--panel);
    border: 1px solid var(--border);
    border-top: 3px solid var(--gold);
    width: 520px;
    max-width: 95vw;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    animation: slideIn 0.2s ease;
  }
  @keyframes slideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
  }
  .modal h3 {
    font-size: 1.1rem;
    letter-spacing: 2px;
    margin-bottom: 24px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
    color: var(--gold);
  }
  .form-group { margin-bottom: 18px; }
  .form-group label {
    display: block;
    font-size: 0.72rem;
    letter-spacing: 2px;
    color: var(--muted);
    margin-bottom: 6px;
    font-family: 'JetBrains Mono', monospace;
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'Noto Serif SC', serif;
    font-size: 0.92rem;
    outline: none;
    transition: border-color 0.15s;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus { border-color: var(--gold); }
  .form-group textarea { height: 80px; resize: vertical; }
  .form-group select option { background: var(--panel); }
  .modal-actions { display: flex; gap: 10px; margin-top: 24px; justify-content: flex-end; }
  .btn-cancel { background: var(--surface); color: var(--muted); border: 1px solid var(--border); }
  .btn-cancel:hover { color: var(--text); }

  .toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    z-index: 999;
    font-size: 0.88rem;
    letter-spacing: 1px;
    display: none;
    animation: fadeIn 0.2s ease;
  }
  .toast.success { background: var(--teal); color: var(--ink); }
  .toast.error   { background: var(--red); color: white; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } }

  .empty-tip {
    text-align: center;
    padding: 48px;
    color: var(--muted);
    font-size: 0.9rem;
    letter-spacing: 2px;
  }

  .class-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }
  .class-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-top: 3px solid var(--gold);
    padding: 24px;
    cursor: pointer;
    transition: all 0.2s;
  }
  .class-card:hover {
    border-color: var(--gold);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(240,192,64,0.1);
  }
  .class-card-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
  }
  .class-card-info {
    font-size: 0.8rem;
    color: var(--muted);
    line-height: 1.8;
    font-family: 'JetBrains Mono', monospace;
  }
  .class-card-info span { color: var(--gold); font-weight: 600; }
  .class-card-action {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    font-size: 0.82rem;
    color: var(--gold);
    letter-spacing: 1px;
  }
  .class-card-actions {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .period-checks {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 4px;
  }
  .period-checks label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 6px 12px;
    border: 1.5px solid var(--border);
    transition: all 0.15s;
    font-family: 'Noto Serif SC', serif;
  }
  .period-checks input:checked + label,
  .period-checks label:hover {
    border-color: var(--gold);
    background: rgba(240,192,64,0.1);
    color: var(--gold);
  }
  .period-checks input { display: none; }

  @media (max-width: 768px) {
    .admin-layout { grid-template-columns: 1fr; }
    .sidebar { position: static; height: auto; }
    .stat-row { grid-template-columns: repeat(2, 1fr); }
    .class-cards { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div class="admin-layout">

  <div class="sidebar">
    <div class="sidebar-logo">
      <h2>⚙ 后台管理</h2>
      <p>ADMIN CONSOLE</p>
      <span class="role-tag <?= $isSuperAdmin ? 'super' : 'class-m' ?>">
        <?= $isSuperAdmin ? '★ 总管理者' : '◆ 班级管理者' ?>
      </span>
      <p style="margin-top:6px;font-size:0.65rem;color:var(--text)"><?= htmlspecialchars($user['username']) ?></p>
    </div>
    <div class="sidebar-nav">
      <?php if ($isSuperAdmin && !$activeClassId): ?>
      <div class="nav-section">OVERVIEW</div>
      <div class="nav-item active" onclick="switchTab('classes')">
        <span class="icon">🏫</span> 班级列表
      </div>
      <div class="nav-section" style="margin-top:8px">SETTINGS</div>
      <div class="nav-item" onclick="switchTab('config')">
        <span class="icon">⚙</span> 学期配置
      </div>
      <div class="nav-item" onclick="switchTab('addclass')">
        <span class="icon">➕</span> 新增班级
      </div>
      <?php else: ?>
      <div class="nav-section">MAIN</div>
      <?php if ($isSuperAdmin): ?>
      <div class="nav-item" onclick="location.href='admin.php?back_to_classes=1'">
        <span class="icon">◀</span> 返回班级列表
      </div>
      <?php endif; ?>
      <div class="nav-item active" onclick="switchTab('dashboard')">
        <span class="icon">📊</span> 数据总览
      </div>
      <div class="nav-item" onclick="switchTab('attendance')">
        <span class="icon">📋</span> 考勤记录
      </div>
      <div class="nav-section" style="margin-top:8px">MANAGE</div>
      <div class="nav-item" onclick="switchTab('students')">
        <span class="icon">👤</span> 学生管理
      </div>
      <div class="nav-item" onclick="switchTab('add')">
        <span class="icon">➕</span> 手动录入
      </div>
      <?php if ($isSuperAdmin): ?>
      <div class="nav-section" style="margin-top:8px">SETTINGS</div>
      <div class="nav-item" onclick="switchTab('config')">
        <span class="icon">⚙</span> 学期配置
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="sidebar-footer" style="position:absolute;bottom:0;width:100%;padding:16px 20px;border-top:1px solid var(--border)">
      <a href="<?= $activeClassId ? 'index.php?class_id='.$activeClassId : 'index.php' ?>" class="back-link">← 前台录入</a>
      <a href="login.php?logout=1" class="back-link" style="color:var(--red)">⏻ 退出登录</a>
    </div>
  </div>

  <div class="main-content">

    <?php if ($isSuperAdmin && !$activeClassId): ?>

    <div class="page-section active" id="section-classes">
      <div class="page-header">
        <div>
          <div class="page-title">班级列表</div>
          <div class="page-subtitle">CLASS LIST · SELECT TO MANAGE</div>
        </div>
      </div>
      <div class="class-cards" id="classCards">
        <div class="empty-tip">加载中...</div>
      </div>
    </div>

    <div class="page-section" id="section-addclass">
      <div class="page-header">
        <div>
          <div class="page-title">新增班级</div>
          <div class="page-subtitle">ADD NEW CLASS</div>
        </div>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);padding:32px;max-width:520px">
        <div class="form-group">
          <label>班级名称 *</label>
          <input type="text" id="nc_name" placeholder="例如：计算机2403班">
        </div>
        <div class="form-group">
          <label>指定管理者（从已有账号选择）</label>
          <select id="nc_manager">
            <option value="0">暂不指定</option>
          </select>
        </div>
        <button class="btn btn-primary" style="width:100%;padding:12px;font-size:1rem;letter-spacing:3px" onclick="addClass()">创 建 班 级</button>
      </div>
    </div>

    <?php else: ?>

    <div class="page-section active" id="section-dashboard">
      <div class="page-header">
        <div>
          <div class="page-title">数据总览</div>
          <div class="page-subtitle">DASHBOARD · ALL WEEKS</div>
        </div>
      </div>
      <div id="dashWarning" style="display:none;background:var(--red);color:white;padding:12px 20px;margin-bottom:20px;font-size:0.88rem;letter-spacing:1px">
        ⚠ 学期配置已过期，请点击「学期配置」更新开学日期。
      </div>
      <div class="stat-row" id="dashStats">
        <div class="stat-card"><div class="stat-num" id="ds-students">—</div><div class="stat-label">学生总数</div></div>
        <div class="stat-card"><div class="stat-num" id="ds-late" style="color:var(--amber)">—</div><div class="stat-label">迟到次数</div></div>
        <div class="stat-card"><div class="stat-num" id="ds-absent" style="color:var(--red)">—</div><div class="stat-label">旷课次数</div></div>
        <div class="stat-card"><div class="stat-num" id="ds-leave" style="color:var(--teal)">—</div><div class="stat-label">请假次数</div></div>
        <div class="stat-card"><div class="stat-num" id="ds-early_leave" style="color:var(--purple)">—</div><div class="stat-label">早退次数</div></div>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);padding:24px;margin-top:8px">
        <div style="font-size:0.72rem;letter-spacing:3px;color:var(--muted);margin-bottom:16px;font-family:monospace">WEEKLY BREAKDOWN</div>
        <div id="weeklyChart" style="overflow-x:auto"></div>
      </div>
    </div>

    <div class="page-section" id="section-attendance">
      <div class="page-header">
        <div>
          <div class="page-title">考勤记录管理</div>
          <div class="page-subtitle">ATTENDANCE RECORDS · CRUD</div>
        </div>
      </div>
      <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="搜索学号或姓名..." style="width:200px">
        <select id="filterWeek">
          <option value="">全部周次</option>
          <script>for(let i=1;i<=18;i++) document.write(`<option value="${i}">第${i}周</option>`);</script>
        </select>
        <select id="filterStatus">
          <option value="">全部状态</option>
          <option value="late">迟到</option>
          <option value="absent">旷课</option>
          <option value="leave">请假</option>
          <option value="early_leave">早退</option>
        </select>
        <button class="btn btn-primary" onclick="loadAttendance(1)">搜 索</button>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>学号</th>
            <th>姓名</th>
            <th>日期</th>
            <th>周次/星期</th>
            <th>节次</th>
            <th>状态</th>
            <th>备注</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody id="attendanceTbody">
          <tr><td colspan="9" class="empty-tip">加载中...</td></tr>
        </tbody>
      </table>
      <div class="pagination" id="attendancePagination"></div>
    </div>

    <div class="page-section" id="section-students">
      <div class="page-header">
        <div>
          <div class="page-title">学生管理</div>
          <div class="page-subtitle">STUDENT MANAGEMENT</div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" onclick="openAddStudentModal()">+ 添加学生</button>
          <button class="btn btn-edit" onclick="exportStudents()">📤 导出JSON</button>
          <button class="btn btn-edit" onclick="openImportModal()">📥 导入JSON</button>
        </div>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>学号</th>
            <th>姓名</th>
            <th>班级</th>
            <th>考勤次数</th>
            <th>创建时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody id="studentsTbody">
          <tr><td colspan="6" class="empty-tip">加载中...</td></tr>
        </tbody>
      </table>
    </div>

    <div class="page-section" id="section-add">
      <div class="page-header">
        <div>
          <div class="page-title">手动录入考勤</div>
          <div class="page-subtitle">MANUAL ENTRY</div>
        </div>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);padding:32px;max-width:500px">
        <div class="form-group">
          <label>学号</label>
          <select id="add_student_id" onchange="onStudentSelect()">
            <option value="">-- 选择学生 --</option>
          </select>
        </div>
        <div class="form-group">
          <label>姓名（自动填充）</label>
          <input type="text" id="add_student_name" readonly style="opacity:0.7">
        </div>
        <div class="form-group">
          <label>日期</label>
          <input type="date" id="add_date">
        </div>
        <div class="form-group">
          <label>节次（可多选）</label>
          <div class="period-checks">
            <input type="checkbox" id="add_p1" value="1"><label for="add_p1">第一节</label>
            <input type="checkbox" id="add_p2" value="2"><label for="add_p2">第二节</label>
            <input type="checkbox" id="add_p3" value="3"><label for="add_p3">第三节</label>
            <input type="checkbox" id="add_p4" value="4"><label for="add_p4">第四节</label>
            <input type="checkbox" id="add_p5" value="5"><label for="add_p5">晚自习</label>
          </div>
        </div>
        <div class="form-group">
          <label>考勤状态</label>
          <select id="add_status">
            <option value="late">迟到</option>
            <option value="absent">旷课</option>
            <option value="leave">请假</option>
            <option value="early_leave">早退</option>
          </select>
        </div>
        <div class="form-group">
          <label>备注（选填）</label>
          <textarea id="add_reason" placeholder="原因或备注..."></textarea>
        </div>
        <button class="btn btn-primary" style="width:100%;padding:12px;font-size:1rem;letter-spacing:3px" onclick="adminAddAttendance()">录 入</button>
      </div>
    </div>

    <?php endif; ?>

    <div class="page-section" id="section-config">
      <div class="page-header">
        <div>
          <div class="page-title">学期配置</div>
          <div class="page-subtitle">SEMESTER SETTINGS · SUPER ADMIN ONLY</div>
        </div>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);padding:32px;max-width:520px">
        <div style="font-size:0.72rem;letter-spacing:3px;color:var(--muted);margin-bottom:20px;font-family:monospace">当前学期参数（共18周固定）</div>
        <div class="form-group">
          <label>学期开始日期（第1周周一）</label>
          <input type="date" id="cfg_start" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:10px 14px;width:100%;font-family:monospace;font-size:0.95rem;outline:none">
          <div style="font-size:0.72rem;color:var(--muted);margin-top:6px;font-family:monospace">⚠ 修改此日期后，系统将重新计算所有周数</div>
        </div>
        <div class="form-group" style="margin-top:20px">
          <label>当前周数手动覆盖（留空=自动计算）</label>
          <input type="number" id="cfg_override" min="1" max="18" placeholder="留空则根据日期自动推算" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:10px 14px;width:100%;font-family:monospace;font-size:0.95rem;outline:none">
          <div style="font-size:0.72rem;color:var(--muted);margin-top:6px;font-family:monospace">自动计算当前周: <span id="cfg_auto_week" style="color:var(--gold)">计算中...</span></div>
        </div>
        <div style="margin-top:8px;padding:14px;background:rgba(240,192,64,0.08);border:1px solid rgba(240,192,64,0.2)">
          <div style="font-size:0.75rem;color:var(--gold);letter-spacing:1px;margin-bottom:6px">学期周次预览</div>
          <div id="cfg_preview" style="font-size:0.8rem;color:var(--muted);font-family:monospace;line-height:1.8"></div>
        </div>
        <button class="btn btn-primary" style="margin-top:24px;padding:12px 32px;letter-spacing:3px" onclick="saveConfig()">保 存 配 置</button>
      </div>
    </div>

  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h3>✎ 编辑考勤记录</h3>
    <input type="hidden" id="edit_id">
    <div class="form-group">
      <label>学生</label>
      <input type="text" id="edit_student" readonly style="opacity:0.7">
    </div>
    <div class="form-group">
      <label>日期</label>
      <input type="date" id="edit_date">
    </div>
    <div class="form-group">
      <label>节次</label>
      <select id="edit_period">
        <option value="1">第一节</option>
        <option value="2">第二节</option>
        <option value="3">第三节</option>
        <option value="4">第四节</option>
        <option value="5">晚自习</option>
      </select>
    </div>
    <div class="form-group">
      <label>考勤状态</label>
      <select id="edit_status">
        <option value="late">迟到</option>
        <option value="absent">旷课</option>
        <option value="leave">请假</option>
        <option value="early_leave">早退</option>
      </select>
    </div>
    <div class="form-group">
      <label>备注</label>
      <textarea id="edit_reason"></textarea>
    </div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="closeModal('editModal')">取消</button>
      <button class="btn btn-primary" onclick="saveEdit()">保 存</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addStudentModal">
  <div class="modal">
    <h3>+ 添加学生</h3>
    <div class="form-group">
      <label>学号 *</label>
      <input type="text" id="ns_id" placeholder="例如：2024006">
    </div>
    <div class="form-group">
      <label>姓名 *</label>
      <input type="text" id="ns_name" placeholder="学生姓名">
    </div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="closeModal('addStudentModal')">取消</button>
      <button class="btn btn-primary" onclick="addStudent()">添 加</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="importModal">
  <div class="modal">
    <h3>📥 导入学生 (JSON)</h3>
    <div class="form-group">
      <label>JSON 数据</label>
      <textarea id="import_json" style="height:200px" placeholder='格式：[{"student_id":"2024001","name":"张三"},{"student_id":"2024002","name":"李四"}]'></textarea>
    </div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="closeModal('importModal')">取消</button>
      <button class="btn btn-primary" onclick="importStudents()">导 入</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editClassModal">
  <div class="modal">
    <h3>✎ 编辑班级</h3>
    <input type="hidden" id="ec_id">
    <div class="form-group">
      <label>班级名称</label>
      <input type="text" id="ec_name">
    </div>
    <div class="form-group">
      <label>班级管理者</label>
      <select id="ec_manager">
        <option value="0">不指定</option>
      </select>
    </div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="closeModal('editClassModal')">取消</button>
      <button class="btn btn-primary" onclick="saveEditClass()">保 存</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const API = 'api.php';
let students = [];
let allManagers = [];
let currentPage = 1;
const isSuperAdmin = <?= $isSuperAdmin ? 'true' : 'false' ?>;
const activeClassId = <?= $activeClassId ?>;

const PERIOD_LABELS = {1:'第一节',2:'第二节',3:'第三节',4:'第四节',5:'晚自习'};

function apiQuery(params) {
    if (activeClassId && !params.includes('class_id=')) {
        params += '&class_id=' + activeClassId;
    }
    return params;
}

window.onload = () => {
    if (isSuperAdmin && !activeClassId) {
        loadClassCards();
        loadManagers();
    } else {
        loadDashboard();
        loadStudents();
        const today = new Date();
        document.getElementById('add_date').value = today.toISOString().split('T')[0];
    }
    if (isSuperAdmin) loadConfig();
};

async function loadManagers() {
    const res = await fetch(`${API}?action=get_class_managers`);
    const data = await res.json();
    if (data.success) allManagers = data.data || [];
    populateManagerSelects();
}

function populateManagerSelects() {
    const unassigned = allManagers.filter(m => !m.class_id);
    const ncSel = document.getElementById('nc_manager');
    if (ncSel) {
        ncSel.innerHTML = '<option value="0">暂不指定</option>' +
            unassigned.map(m => `<option value="${m.id}">${m.username}（未关联）</option>`).join('');
    }
}

function switchTab(tab) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById(`section-${tab}`).classList.add('active');
    if (event && event.currentTarget) event.currentTarget.classList.add('active');
    if (tab === 'attendance') loadAttendance(1);
    if (tab === 'students') renderStudentsTable();
    if (tab === 'config') loadConfig();
    if (tab === 'classes') loadClassCards();
}

async function loadClassCards() {
    const res = await fetch(`${API}?action=get_classes`);
    const data = await res.json();
    const classes = data.data || [];
    const el = document.getElementById('classCards');
    if (!classes.length) {
        el.innerHTML = '<div class="empty-tip">暂无班级，请点击左侧「新增班级」添加</div>';
        return;
    }
    el.innerHTML = classes.map(c => `
        <div class="class-card">
            <div class="class-card-name" onclick="selectClass(${c.id})">🏫 ${c.class_name}</div>
            <div class="class-card-info">
                学生人数: <span>${c.student_count || 0}</span> 人<br>
                管理者账号: <span>${c.manager_username || '未设置'}</span>
            </div>
            <div class="class-card-actions">
                <button class="btn btn-primary btn-sm" onclick="selectClass(${c.id})">进入管理 →</button>
                <button class="btn btn-edit btn-sm" onclick="openEditClassModal(${c.id}, '${c.class_name}', ${c.manager_user_id || 0})">编辑</button>
                <button class="btn btn-danger btn-sm" onclick="deleteClassFromCard(${c.id}, '${c.class_name}')">删除</button>
            </div>
        </div>
    `).join('');
}

function selectClass(classId) {
    location.href = 'admin.php?class_id=' + classId;
}

function openEditClassModal(id, name, managerUserId) {
    document.getElementById('ec_id').value = id;
    document.getElementById('ec_name').value = name;
    const sel = document.getElementById('ec_manager');
    sel.innerHTML = '<option value="0">不指定</option>' +
        allManagers.map(m => {
            const tag = m.class_id && m.class_id != id ? `（已关联#${m.class_id}）` : (m.class_id == id ? '（当前）' : '（未关联）');
            return `<option value="${m.id}" ${m.id == managerUserId ? 'selected' : ''}>${m.username} ${tag}</option>`;
        }).join('');
    document.getElementById('editClassModal').classList.add('open');
}

async function saveEditClass() {
    const class_id = document.getElementById('ec_id').value;
    const class_name = document.getElementById('ec_name').value.trim();
    const manager_user_id = document.getElementById('ec_manager').value;
    if (!class_name) { showToast('班级名称不能为空', 'error'); return; }
    const res = await fetch(`${API}?action=update_class`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ class_id: parseInt(class_id), class_name, manager_user_id: parseInt(manager_user_id) })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        closeModal('editClassModal');
        loadClassCards();
        loadManagers();
    }
}

async function deleteClassFromCard(id, name) {
    if (!confirm(`确定删除班级"${name}"？\n⚠️ 该班级的所有学生和考勤记录也将被删除！`)) return;
    const res = await fetch(`${API}?action=delete_class`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ class_id: id })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) { loadClassCards(); loadManagers(); }
}

async function addClass() {
    const class_name = document.getElementById('nc_name').value.trim();
    const manager_user_id = document.getElementById('nc_manager').value;
    if (!class_name) { showToast('请填写班级名称', 'error'); return; }
    const res = await fetch(`${API}?action=add_class`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ class_name, manager_user_id: parseInt(manager_user_id) })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        document.getElementById('nc_name').value = '';
        loadClassCards();
        loadManagers();
    }
}

async function loadDashboard() {
    loadConfig();
    const url = `${API}?${apiQuery('action=get_stats')}`;
    const res = await fetch(url);
    const data = await res.json();
    const counts = { late: 0, absent: 0, leave: 0, early_leave: 0 };
    (data.stats||[]).forEach(s => counts[s.status] = parseInt(s.cnt));
    document.getElementById('ds-students').textContent = data.students || 0;
    document.getElementById('ds-late').textContent = counts.late;
    document.getElementById('ds-absent').textContent = counts.absent;
    document.getElementById('ds-leave').textContent = counts.leave;
    document.getElementById('ds-early_leave').textContent = counts.early_leave;
    loadWeeklyChart();
}

async function loadWeeklyChart() {
    const url = `${API}?${apiQuery('action=get_weekly_summary')}`;
    const res = await fetch(url);
    const data = await res.json();
    const weeks = data.data || [];
    const rows = weeks.map(w => `
        <tr>
            <td style="font-family:monospace;color:var(--muted);padding:8px 12px;border-bottom:1px solid rgba(45,45,78,0.5)">第${w.week}周</td>
            <td style="color:var(--amber);padding:8px 12px;border-bottom:1px solid rgba(45,45,78,0.5);text-align:center">${w.late || 0}</td>
            <td style="color:var(--red);padding:8px 12px;border-bottom:1px solid rgba(45,45,78,0.5);text-align:center">${w.absent || 0}</td>
            <td style="color:var(--teal);padding:8px 12px;border-bottom:1px solid rgba(45,45,78,0.5);text-align:center">${w.leave || 0}</td>
            <td style="color:var(--purple);padding:8px 12px;border-bottom:1px solid rgba(45,45,78,0.5);text-align:center">${w.early_leave || 0}</td>
            <td style="font-family:monospace;font-weight:600;padding:8px 12px;border-bottom:1px solid rgba(45,45,78,0.5);text-align:center">${w.total || 0}</td>
        </tr>
    `).join('');
    document.getElementById('weeklyChart').innerHTML = rows.length ? `
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem">
            <thead>
                <tr>
                    <th style="text-align:left;padding:8px 12px;color:var(--muted);font-size:0.72rem;letter-spacing:2px;font-family:monospace;border-bottom:1px solid var(--border)">周次</th>
                    <th style="padding:8px 12px;color:var(--amber);font-size:0.72rem;letter-spacing:2px;border-bottom:1px solid var(--border);text-align:center">迟到</th>
                    <th style="padding:8px 12px;color:var(--red);font-size:0.72rem;letter-spacing:2px;border-bottom:1px solid var(--border);text-align:center">旷课</th>
                    <th style="padding:8px 12px;color:var(--teal);font-size:0.72rem;letter-spacing:2px;border-bottom:1px solid var(--border);text-align:center">请假</th>
                    <th style="padding:8px 12px;color:var(--purple);font-size:0.72rem;letter-spacing:2px;border-bottom:1px solid var(--border);text-align:center">早退</th>
                    <th style="padding:8px 12px;color:var(--muted);font-size:0.72rem;letter-spacing:2px;border-bottom:1px solid var(--border);text-align:center">合计</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    ` : '<div style="color:var(--muted);padding:24px;text-align:center;letter-spacing:2px">暂无数据</div>';
}

async function loadAttendance(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const week = document.getElementById('filterWeek').value;
    const status = document.getElementById('filterStatus').value;
    let query = `action=get_all&page=${page}&search=${encodeURIComponent(search)}&week=${week}&status=${status}`;
    query = apiQuery(query);
    const res = await fetch(`${API}?${query}`);
    const data = await res.json();
    renderAttendanceTable(data.data || []);
    renderPagination(data.total, data.page, data.limit);
}

const DAY_NAMES = ['', '周一','周二','周三','周四','周五','周六','周日'];
const STATUS_LABELS = { late: '迟到', absent: '旷课', leave: '请假', early_leave: '早退' };

function renderAttendanceTable(records) {
    const tbody = document.getElementById('attendanceTbody');
    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-tip">暂无记录</td></tr>';
        return;
    }
    tbody.innerHTML = records.map(r => `
        <tr>
            <td style="font-family:monospace;color:var(--muted)">#${r.id}</td>
            <td style="font-family:monospace">${r.student_id}</td>
            <td style="font-weight:600">${r.student_name}</td>
            <td style="font-family:monospace">${r.record_date}</td>
            <td style="font-family:monospace">第${r.week_number}周 ${DAY_NAMES[r.day_of_week]}</td>
            <td>${PERIOD_LABELS[r.period] || '第'+r.period+'节'}</td>
            <td><span class="tag ${r.status}">${STATUS_LABELS[r.status]}</span></td>
            <td style="color:var(--muted);max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.reason||'—'}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-edit btn-sm" onclick="openEditModal(${r.id})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteAttendance(${r.id})">删除</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination(total, page, limit) {
    const pages = Math.ceil(total / limit);
    const el = document.getElementById('attendancePagination');
    if (pages <= 1) { el.innerHTML = ''; return; }
    let html = `<span style="color:var(--muted);font-size:0.8rem;margin-right:8px">共 ${total} 条</span>`;
    for (let i = 1; i <= pages; i++) {
        html += `<button class="page-btn ${i===page?'active':''}" onclick="loadAttendance(${i})">${i}</button>`;
    }
    el.innerHTML = html;
}

async function openEditModal(id) {
    const res = await fetch(`${API}?action=get_one&id=${id}`);
    const data = await res.json();
    if (!data.success) { showToast('获取记录失败', 'error'); return; }
    const r = data.data;
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_student').value = `${r.student_name} (${r.student_id})`;
    document.getElementById('edit_date').value = r.record_date;
    document.getElementById('edit_period').value = r.period || 1;
    document.getElementById('edit_status').value = r.status;
    document.getElementById('edit_reason').value = r.reason || '';
    document.getElementById('editModal').classList.add('open');
}

async function saveEdit() {
    const id     = document.getElementById('edit_id').value;
    const date   = document.getElementById('edit_date').value;
    const period = document.getElementById('edit_period').value;
    const status = document.getElementById('edit_status').value;
    const reason = document.getElementById('edit_reason').value;
    const res = await fetch(`${API}?action=update_attendance`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, record_date: date, period: parseInt(period), status, reason })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        closeModal('editModal');
        loadAttendance(currentPage);
    }
}

async function deleteAttendance(id) {
    if (!confirm(`确定删除记录 #${id}？此操作不可撤销。`)) return;
    const res = await fetch(`${API}?action=delete_attendance`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) loadAttendance(currentPage);
}

async function loadStudents() {
    const url = `${API}?${apiQuery('action=get_students')}`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) {
        students = data.data;
        populateStudentSelects();
    }
}

function renderStudentsTable() {
    const tbody = document.getElementById('studentsTbody');
    if (!students.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-tip">暂无学生</td></tr>';
        return;
    }
    tbody.innerHTML = students.map(s => `
        <tr>
            <td style="font-family:monospace">${s.student_id}</td>
            <td style="font-weight:600">${s.name}</td>
            <td style="color:var(--muted)">${s.class_name||'—'}</td>
            <td>—</td>
            <td style="font-family:monospace;color:var(--muted);font-size:0.8rem">${s.created_at?.split('T')[0]||s.created_at||''}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteStudent('${s.student_id}','${s.name}')">删除</button>
            </td>
        </tr>
    `).join('');
}

function populateStudentSelects() {
    const opts = students.map(s => `<option value="${s.student_id}" data-name="${s.name}">${s.student_id} · ${s.name}</option>`).join('');
    document.getElementById('add_student_id').innerHTML = '<option value="">-- 选择学生 --</option>' + opts;
}

function onStudentSelect() {
    const sel = document.getElementById('add_student_id');
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('add_student_name').value = opt.dataset.name || '';
}

function openAddStudentModal() {
    document.getElementById('ns_id').value = '';
    document.getElementById('ns_name').value = '';
    document.getElementById('addStudentModal').classList.add('open');
}

async function addStudent() {
    const student_id = document.getElementById('ns_id').value.trim();
    const name = document.getElementById('ns_name').value.trim();
    if (!student_id || !name) { showToast('请填写学号和姓名', 'error'); return; }
    const res = await fetch(`${API}?action=add_student`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id, name, class_id: activeClassId })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        closeModal('addStudentModal');
        await loadStudents();
        renderStudentsTable();
    }
}

async function deleteStudent(id, name) {
    if (!confirm(`确定删除学生"${name}"（${id}）？\n⚠️ 该学生的所有考勤记录也将一并删除！`)) return;
    const res = await fetch(`${API}?action=delete_student`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: id })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) { await loadStudents(); renderStudentsTable(); }
}

async function exportStudents() {
    const url = `${API}?${apiQuery('action=export_students')}`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) { showToast(data.msg, 'error'); return; }
    const exportData = {
        class_name: data.class_name,
        class_id: data.class_id,
        count: data.count,
        students: data.data
    };
    const json = JSON.stringify(exportData, null, 2);
    const blob = new Blob([json], {type: 'application/json'});
    const url2 = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url2;
    a.download = `${data.class_name || 'students'}_${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url2);
    showToast(`导出成功：${data.count}名学生`, 'success');
}

function openImportModal() {
    document.getElementById('import_json').value = '';
    document.getElementById('importModal').classList.add('open');
}

async function importStudents() {
    const jsonStr = document.getElementById('import_json').value.trim();
    if (!jsonStr) { showToast('请粘贴JSON数据', 'error'); return; }
    let importData;
    try {
        importData = JSON.parse(jsonStr);
    } catch(e) {
        showToast('JSON格式错误，请检查', 'error'); return;
    }
    const studentsArr = importData.students || importData;
    if (!Array.isArray(studentsArr)) { showToast('数据格式错误，需要数组', 'error'); return; }
    const res = await fetch(`${API}?action=import_students`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ class_id: activeClassId, students: studentsArr })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        closeModal('importModal');
        await loadStudents();
        renderStudentsTable();
    }
}

function getSelectedPeriods() {
    const periods = [];
    for (let i = 1; i <= 5; i++) {
        if (document.getElementById(`add_p${i}`).checked) periods.push(i);
    }
    return periods;
}

async function adminAddAttendance() {
    const student_id   = document.getElementById('add_student_id').value;
    const student_name = document.getElementById('add_student_name').value;
    const record_date  = document.getElementById('add_date').value;
    const status       = document.getElementById('add_status').value;
    const reason       = document.getElementById('add_reason').value;
    const periods      = getSelectedPeriods();
    if (!student_id) { showToast('请选择学生', 'error'); return; }
    if (!record_date) { showToast('请选择日期', 'error'); return; }
    if (!periods.length) { showToast('请至少选择一个节次', 'error'); return; }
    const res = await fetch(`${API}?action=add_attendance`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id, student_name, record_date, status, reason, periods })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        document.getElementById('add_reason').value = '';
        for (let i = 1; i <= 5; i++) document.getElementById(`add_p${i}`).checked = false;
        loadDashboard();
    }
}

async function loadConfig() {
    const res = await fetch(`${API}?action=get_semester_config`);
    const data = await res.json();
    if (!data.success) return;
    const cfg = data.data;
    document.getElementById('cfg_start').value = cfg.semester_start || '';
    document.getElementById('cfg_override').value = cfg.current_week_override || '';
    const weekEl = document.getElementById('cfg_auto_week');
    if (cfg.out_of_range) {
        weekEl.textContent = `第 ${cfg.current_week_auto} 周（⚠ 已超出18周！）`;
        weekEl.style.color = 'var(--red)';
        const warn = document.getElementById('dashWarning');
        if (warn) warn.style.display = 'block';
    } else {
        weekEl.textContent = `第 ${cfg.current_week_auto} 周`;
        weekEl.style.color = 'var(--gold)';
    }
    renderWeekPreview(cfg.semester_start);
}

function renderWeekPreview(startDate) {
    if (!startDate) return;
    const start = new Date(startDate);
    const dow = start.getDay();
    const offset = dow === 0 ? -6 : 1 - dow;
    start.setDate(start.getDate() + offset);
    let html = '';
    for (let w = 1; w <= 18; w++) {
        const weekStart = new Date(start);
        weekStart.setDate(start.getDate() + (w-1)*7);
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 4);
        const fmt = d => `${d.getMonth()+1}/${d.getDate()}`;
        html += `第${w}周 ${fmt(weekStart)}—${fmt(weekEnd)}  `;
        if (w % 3 === 0) html += '<br>';
    }
    document.getElementById('cfg_preview').innerHTML = html;
}

document.addEventListener('change', function(e) {
    if (e.target.id === 'cfg_start') renderWeekPreview(e.target.value);
});

async function saveConfig() {
    const semester_start = document.getElementById('cfg_start').value;
    const override = document.getElementById('cfg_override').value;
    if (!semester_start) { showToast('请填写学期开始日期', 'error'); return; }
    const res = await fetch(`${API}?action=save_semester_config`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ semester_start, current_week_override: override || 0 })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) loadConfig();
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type}`;
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}
</script>
</body>
</html>
