<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$classId = (int)$user['class_id'];
if (isSuperAdmin() && isset($_GET['class_id'])) {
    $classId = (int)$_GET['class_id'];
}
if (!$classId) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>考勤录入系统</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #1a1a2e;
    --paper: #f5f0e8;
    --red: #c0392b;
    --amber: #e67e22;
    --teal: #16a085;
    --gold: #f39c12;
    --purple: #8e44ad;
    --line: #d4c9b0;
    --muted: #7f8c8d;
    --late-bg: #fff3cd;
    --late-border: #f39c12;
    --absent-bg: #fde8e8;
    --absent-border: #c0392b;
    --leave-bg: #d4efdf;
    --leave-border: #16a085;
    --early-leave-bg: #f0e0f8;
    --early-leave-border: #8e44ad;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Noto Serif SC', serif;
    background: var(--paper);
    background-image:
      repeating-linear-gradient(0deg, transparent, transparent 31px, var(--line) 31px, var(--line) 32px),
      repeating-linear-gradient(90deg, transparent, transparent 31px, rgba(212,201,176,0.3) 31px, rgba(212,201,176,0.3) 32px);
    min-height: 100vh;
    color: var(--ink);
  }

  header {
    background: var(--ink);
    color: var(--paper);
    padding: 18px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid var(--gold);
    position: sticky;
    top: 0;
    z-index: 100;
  }
  header h1 {
    font-size: 1.4rem;
    font-weight: 700;
    letter-spacing: 4px;
  }
  header h1 span { color: var(--gold); }
  .header-nav a {
    color: var(--paper);
    text-decoration: none;
    font-size: 0.85rem;
    letter-spacing: 2px;
    opacity: 0.7;
    margin-left: 24px;
    transition: opacity 0.2s;
  }
  .header-nav a:hover { opacity: 1; color: var(--gold); }
  .week-badge {
    background: var(--gold);
    color: var(--ink);
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 4px 12px;
    letter-spacing: 1px;
  }
  .user-badge {
    background: rgba(255,255,255,0.1);
    color: var(--paper);
    font-size: 0.75rem;
    padding: 4px 10px;
    letter-spacing: 1px;
    font-family: 'JetBrains Mono', monospace;
  }

  .semester-warning {
    display: none;
    background: #c0392b;
    color: white;
    padding: 10px 40px;
    font-size: 0.85rem;
    letter-spacing: 1px;
    text-align: center;
  }
  .semester-warning a {
    color: #f9ca24;
    font-weight: 700;
    text-decoration: underline;
    margin-left: 8px;
  }

  .main-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 0;
    min-height: calc(100vh - 65px);
  }

  .entry-panel {
    background: white;
    border-right: 2px solid var(--line);
    padding: 32px 28px;
    position: sticky;
    top: 65px;
    height: calc(100vh - 65px);
    overflow-y: auto;
  }
  .panel-title {
    font-size: 0.7rem;
    letter-spacing: 4px;
    color: var(--muted);
    text-transform: uppercase;
    margin-bottom: 6px;
    font-family: 'JetBrains Mono', monospace;
  }
  .panel-heading {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 28px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--ink);
  }

  .form-group {
    margin-bottom: 20px;
  }
  .form-group label {
    display: block;
    font-size: 0.78rem;
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
    border: 1.5px solid var(--line);
    background: var(--paper);
    font-family: 'Noto Serif SC', serif;
    font-size: 0.95rem;
    color: var(--ink);
    outline: none;
    transition: border-color 0.2s;
    border-radius: 0;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    border-color: var(--ink);
    background: white;
  }
  .form-group textarea { resize: vertical; height: 80px; }

  .status-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-top: 4px;
  }
  .status-option input[type="radio"] { display: none; }
  .status-option label {
    display: block;
    text-align: center;
    padding: 10px 4px;
    border: 2px solid var(--line);
    cursor: pointer;
    font-size: 0.82rem;
    letter-spacing: 1px;
    transition: all 0.15s;
    font-family: 'Noto Serif SC', serif;
  }
  .status-option input[type="radio"][value="late"] + label:hover,
  .status-option input[type="radio"][value="late"]:checked + label {
    border-color: var(--late-border);
    background: var(--late-bg);
    color: var(--amber);
    font-weight: 700;
  }
  .status-option input[type="radio"][value="absent"] + label:hover,
  .status-option input[type="radio"][value="absent"]:checked + label {
    border-color: var(--absent-border);
    background: var(--absent-bg);
    color: var(--red);
    font-weight: 700;
  }
  .status-option input[type="radio"][value="leave"] + label:hover,
  .status-option input[type="radio"][value="leave"]:checked + label {
    border-color: var(--leave-border);
    background: var(--leave-bg);
    color: var(--teal);
    font-weight: 700;
  }
  .status-option input[type="radio"][value="early_leave"] + label:hover,
  .status-option input[type="radio"][value="early_leave"]:checked + label {
    border-color: var(--early-leave-border);
    background: var(--early-leave-bg);
    color: var(--purple);
    font-weight: 700;
  }

  .btn-submit {
    width: 100%;
    padding: 14px;
    background: var(--ink);
    color: var(--gold);
    border: none;
    font-family: 'Noto Serif SC', serif;
    font-size: 1rem;
    letter-spacing: 4px;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
  }
  .btn-submit:hover { background: #2c2c4e; }
  .btn-submit:active { transform: translateY(1px); }

  .alert {
    padding: 10px 14px;
    margin-top: 14px;
    font-size: 0.88rem;
    border-left: 3px solid;
    display: none;
  }
  .alert.success { border-color: var(--teal); background: var(--leave-bg); color: var(--teal); }
  .alert.error   { border-color: var(--red);  background: var(--absent-bg); color: var(--red); }

  .autocomplete-wrap { position: relative; }
  .autocomplete-list {
    position: absolute;
    top: 100%;
    left: 0; right: 0;
    background: white;
    border: 1.5px solid var(--ink);
    z-index: 50;
    display: none;
    max-height: 180px;
    overflow-y: auto;
  }
  .autocomplete-item {
    padding: 8px 14px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.1s;
  }
  .autocomplete-item:hover { background: var(--paper); }

  .overview-panel {
    padding: 32px 32px;
  }
  .overview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
  }
  .week-selector {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .week-btn {
    width: 32px; height: 32px;
    background: var(--ink);
    color: var(--paper);
    border: none;
    cursor: pointer;
    font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s;
  }
  .week-btn:hover { background: var(--gold); color: var(--ink); }
  .week-display {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 2px;
    min-width: 80px;
    text-align: center;
  }

  .legend {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
  }
  .legend-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.8rem; letter-spacing: 1px;
  }
  .legend-dot {
    width: 12px; height: 12px;
    border-radius: 0;
  }
  .legend-dot.late        { background: var(--gold); }
  .legend-dot.absent      { background: var(--red); }
  .legend-dot.leave       { background: var(--teal); }
  .legend-dot.early_leave { background: var(--purple); }

  .table-wrap {
    overflow-x: auto;
  }
  .attendance-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
  }
  .attendance-table th {
    background: var(--ink);
    color: var(--paper);
    padding: 12px 16px;
    text-align: center;
    font-weight: 600;
    letter-spacing: 2px;
    font-size: 0.8rem;
  }
  .attendance-table th:first-child { text-align: left; }
  .attendance-table td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    text-align: center;
    background: white;
  }
  .attendance-table td:first-child { text-align: left; font-weight: 600; }
  .attendance-table tr:hover td { background: var(--paper); }

  .tag {
    display: inline-block;
    padding: 3px 10px;
    font-size: 0.75rem;
    letter-spacing: 1px;
    font-weight: 700;
    border: 1.5px solid;
  }
  .tag.late        { color: var(--amber); border-color: var(--late-border); background: var(--late-bg); }
  .tag.absent      { color: var(--red);   border-color: var(--absent-border); background: var(--absent-bg); }
  .tag.leave       { color: var(--teal);  border-color: var(--leave-border); background: var(--leave-bg); }
  .tag.early_leave { color: var(--purple); border-color: var(--early-leave-border); background: var(--early-leave-bg); }

  .stats-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }
  .stat-card {
    background: white;
    border: 1.5px solid var(--line);
    padding: 14px 20px;
    min-width: 100px;
    border-top: 3px solid var(--ink);
  }
  .stat-card.late-card        { border-top-color: var(--gold); }
  .stat-card.absent-card      { border-top-color: var(--red); }
  .stat-card.leave-card       { border-top-color: var(--teal); }
  .stat-card.early-leave-card { border-top-color: var(--purple); }
  .stat-num {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.8rem;
    font-weight: 600;
    line-height: 1;
  }
  .stat-label {
    font-size: 0.72rem;
    letter-spacing: 2px;
    color: var(--muted);
    margin-top: 4px;
  }

  .empty-tip {
    text-align: center;
    color: var(--muted);
    padding: 48px;
    font-size: 0.9rem;
    letter-spacing: 2px;
  }

  .cell-records { display: flex; flex-direction: column; gap: 4px; align-items: center; }

  .period-checks {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 4px;
  }
  .period-checks label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.82rem;
    cursor: pointer;
    padding: 8px 12px;
    border: 2px solid var(--line);
    transition: all 0.15s;
    font-family: 'Noto Serif SC', serif;
    letter-spacing: 1px;
  }
  .period-checks input:checked + label,
  .period-checks label:hover {
    border-color: var(--ink);
    background: var(--paper);
    font-weight: 700;
  }
  .period-checks input { display: none; }

  @media (max-width: 900px) {
    .main-layout { grid-template-columns: 1fr; }
    .entry-panel { position: static; height: auto; }
    .status-options { grid-template-columns: repeat(2, 1fr); }
  }
</style>
</head>
<body>

<header>
  <h1>📋 考<span>勤</span>录入系统</h1>
  <div class="header-nav">
    <span class="user-badge"><?= htmlspecialchars($user['username']) ?></span>
    <span class="week-badge" id="headerWeek">第9周</span>
    <a href="admin.php">后台管理 →</a>
    <a href="login.php?logout=1">退出</a>
  </div>
</header>

<div id="semesterWarning" class="semester-warning">
  ⚠ 学期配置已过期，当前周数计算不准确，请前往后台更新开学日期。
  <a href="admin.php" target="_blank">立即前往后台 →</a>
</div>

<div class="main-layout">

  <div class="entry-panel">
    <div class="panel-title">ATTENDANCE ENTRY</div>
    <div class="panel-heading">考勤录入</div>

    <div class="form-group">
      <label>学号 / 姓名</label>
      <div class="autocomplete-wrap">
        <input type="text" id="studentSearch" placeholder="输入学号或姓名搜索..." autocomplete="off">
        <div class="autocomplete-list" id="autocompleteList"></div>
      </div>
      <input type="hidden" id="studentId">
      <input type="hidden" id="studentName">
    </div>

    <div class="form-group">
      <label>日期</label>
      <input type="date" id="recordDate">
    </div>

    <div class="form-group">
      <label>节次（可多选）</label>
      <div class="period-checks">
        <input type="checkbox" id="p1" value="1"><label for="p1">第一节</label>
        <input type="checkbox" id="p2" value="2"><label for="p2">第二节</label>
        <input type="checkbox" id="p3" value="3"><label for="p3">第三节</label>
        <input type="checkbox" id="p4" value="4"><label for="p4">第四节</label>
        <input type="checkbox" id="p5" value="5"><label for="p5">晚自习</label>
      </div>
    </div>

    <div class="form-group">
      <label>考勤状态</label>
      <div class="status-options">
        <div class="status-option">
          <input type="radio" name="status" id="s_late" value="late">
          <label for="s_late">⏰ 迟到</label>
        </div>
        <div class="status-option">
          <input type="radio" name="status" id="s_absent" value="absent">
          <label for="s_absent">✗ 旷课</label>
        </div>
        <div class="status-option">
          <input type="radio" name="status" id="s_leave" value="leave">
          <label for="s_leave">📝 请假</label>
        </div>
        <div class="status-option">
          <input type="radio" name="status" id="s_early_leave" value="early_leave">
          <label for="s_early_leave">🚪 早退</label>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>备注 / 原因（选填）</label>
      <textarea id="reason" placeholder="填写原因或补充说明..."></textarea>
    </div>

    <button class="btn-submit" onclick="submitAttendance()">录 入 考 勤</button>

    <div class="alert" id="alertBox"></div>

    <div style="margin-top:32px; padding-top:20px; border-top:1px solid var(--line);">
      <div class="panel-title">QUICK STATS · 本周</div>
      <div id="quickStats" style="margin-top:12px;"></div>
    </div>
  </div>

  <div class="overview-panel">
    <div class="overview-header">
      <div>
        <div class="panel-title">ATTENDANCE OVERVIEW</div>
        <div class="panel-heading" style="margin-bottom:0">考勤总览</div>
      </div>
      <div class="week-selector">
        <button class="week-btn" onclick="changeWeek(-1)">◀</button>
        <div class="week-display" id="weekDisplay">第 9 周</div>
        <button class="week-btn" onclick="changeWeek(1)">▶</button>
      </div>
    </div>

    <div class="stats-bar" id="statsBar"></div>

    <div class="legend">
      <div class="legend-item"><div class="legend-dot late"></div>迟到</div>
      <div class="legend-item"><div class="legend-dot absent"></div>旷课</div>
      <div class="legend-item"><div class="legend-dot leave"></div>请假</div>
      <div class="legend-item"><div class="legend-dot early_leave"></div>早退</div>
    </div>

    <div class="table-wrap">
      <table class="attendance-table" id="overviewTable">
        <thead>
          <tr>
            <th>学生</th>
            <th>周一</th>
            <th>周二</th>
            <th>周三</th>
            <th>周四</th>
            <th>周五</th>
            <th>合计</th>
          </tr>
        </thead>
        <tbody id="overviewBody">
          <tr><td colspan="7" class="empty-tip">加载中...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
const API = 'api.php';
let currentWeek = 1;
let students = [];
const classId = <?= $classId ?>;

function apiQuery(params) {
    if (classId && !params.includes('class_id=')) {
        params += '&class_id=' + classId;
    }
    return params;
}

window.onload = async () => {
  const today = new Date();
  const dow = today.getDay();
  if (dow === 0 || dow === 6) {
    const offset = dow === 0 ? 2 : 1;
    today.setDate(today.getDate() - offset);
  }
  document.getElementById('recordDate').value = today.toISOString().split('T')[0];

  try {
    const cfgRes = await fetch(`${API}?action=get_semester_config`);
    const cfgData = await cfgRes.json();
    if (cfgData.success) {
      const cfg = cfgData.data;
      const rawWeek = cfg.current_week_auto || 1;

      if (cfg.out_of_range) {
        currentWeek = 18;
        document.getElementById('weekDisplay').textContent = `第 18 周`;
        document.getElementById('headerWeek').textContent = `第18周`;
        const banner = document.getElementById('semesterWarning');
        if (banner) banner.style.display = 'block';
      } else {
        currentWeek = rawWeek;
        document.getElementById('weekDisplay').textContent = `第 ${currentWeek} 周`;
        document.getElementById('headerWeek').textContent = `第${currentWeek}周`;
      }
    }
  } catch(e) { currentWeek = 1; }

  await loadStudents();
  loadOverview();
  loadQuickStats();
};

async function loadStudents() {
  const url = `${API}?${apiQuery('action=get_students')}`;
  const res = await fetch(url);
  const data = await res.json();
  if (data.success) students = data.data;
}

document.getElementById('studentSearch').addEventListener('input', function() {
  const q = this.value.trim().toLowerCase();
  const list = document.getElementById('autocompleteList');
  if (!q) { list.style.display = 'none'; return; }
  const filtered = students.filter(s =>
    s.student_id.toLowerCase().includes(q) || s.name.toLowerCase().includes(q)
  );
  if (!filtered.length) { list.style.display = 'none'; return; }
  list.innerHTML = filtered.map(s =>
    `<div class="autocomplete-item" onclick="selectStudent('${s.student_id}','${s.name}','${s.class_name||''}')">
      <strong>${s.student_id}</strong> · ${s.name} <span style="color:var(--muted);font-size:0.8em">${s.class_name||''}</span>
    </div>`
  ).join('');
  list.style.display = 'block';
});
document.addEventListener('click', e => {
  if (!e.target.closest('.autocomplete-wrap'))
    document.getElementById('autocompleteList').style.display = 'none';
});

function selectStudent(id, name, cls) {
  document.getElementById('studentId').value = id;
  document.getElementById('studentName').value = name;
  document.getElementById('studentSearch').value = `${id} · ${name}`;
  document.getElementById('autocompleteList').style.display = 'none';
}

async function submitAttendance() {
  const student_id   = document.getElementById('studentId').value;
  const student_name = document.getElementById('studentName').value;
  const record_date  = document.getElementById('recordDate').value;
  const status       = document.querySelector('input[name="status"]:checked')?.value;
  const reason       = document.getElementById('reason').value;
  const periods      = getSelectedPeriods();

  if (!student_id) { showAlert('请先选择学生', 'error'); return; }
  if (!record_date) { showAlert('请选择日期', 'error'); return; }
  if (!periods.length) { showAlert('请至少选择一个节次', 'error'); return; }
  if (!status) { showAlert('请选择考勤状态', 'error'); return; }

  const res = await fetch(`${API}?action=add_attendance`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ student_id, student_name, record_date, status, reason, periods })
  });
  const data = await res.json();
  showAlert(data.msg, data.success ? 'success' : 'error');
  if (data.success) {
    loadOverview();
    loadQuickStats();
    document.getElementById('reason').value = '';
    document.querySelector('input[name="status"]:checked').checked = false;
    for (let i = 1; i <= 5; i++) document.getElementById(`p${i}`).checked = false;
  }
}

function getSelectedPeriods() {
  const periods = [];
  for (let i = 1; i <= 5; i++) {
    if (document.getElementById(`p${i}`).checked) periods.push(i);
  }
  return periods;
}

function showAlert(msg, type) {
  const el = document.getElementById('alertBox');
  el.textContent = msg;
  el.className = `alert ${type}`;
  el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 3000);
}

function changeWeek(delta) {
  currentWeek = Math.max(1, Math.min(18, currentWeek + delta));
  document.getElementById('weekDisplay').textContent = `第 ${currentWeek} 周`;
  document.getElementById('headerWeek').textContent = `第${currentWeek}周`;
  loadOverview();
}

async function loadOverview() {
  let query = `action=get_overview&week=${currentWeek}`;
  query = apiQuery(query);
  const res = await fetch(`${API}?${query}`);
  const data = await res.json();
  renderOverview(data.data || []);
  renderStatsBar(data.data || []);
}

const PERIOD_LABELS = {1:'一',2:'二',3:'三',4:'四',5:'晚'};

function renderOverview(records) {
  const tbody = document.getElementById('overviewBody');
  if (!records.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="empty-tip">第 ${currentWeek} 周暂无考勤记录</td></tr>`;
    return;
  }

  const byStudent = {};
  records.forEach(r => {
    const key = r.student_id;
    if (!byStudent[key]) byStudent[key] = { name: r.student_name, id: r.student_id, days: {} };
    const d = r.day_of_week;
    if (!byStudent[key].days[d]) byStudent[key].days[d] = [];
    byStudent[key].days[d].push(r);
  });

  const rows = Object.values(byStudent).map(s => {
    const days = [1,2,3,4,5].map(d => {
      if (!s.days[d]) return '<td>—</td>';
      const tags = s.days[d].map(r => {
        const pLabel = PERIOD_LABELS[r.period] || r.period;
        return `<span class="tag ${r.status}" title="${r.reason||''}">${statusLabel(r.status)}${pLabel}</span>`;
      }).join('');
      return `<td><div class="cell-records">${tags}</div></td>`;
    }).join('');
    const total = Object.values(s.days).flat().length;
    return `<tr>
      <td><div style="font-weight:600">${s.name}</div><div style="font-size:0.75em;color:var(--muted);font-family:monospace">${s.id}</div></td>
      ${days}
      <td><strong style="font-family:monospace">${total}</strong></td>
    </tr>`;
  }).join('');
  tbody.innerHTML = rows;
}

function renderStatsBar(records) {
  const counts = { late: 0, absent: 0, leave: 0, early_leave: 0 };
  records.forEach(r => counts[r.status]++);
  document.getElementById('statsBar').innerHTML = `
    <div class="stat-card late-card">
      <div class="stat-num" style="color:var(--amber)">${counts.late}</div>
      <div class="stat-label">迟到</div>
    </div>
    <div class="stat-card absent-card">
      <div class="stat-num" style="color:var(--red)">${counts.absent}</div>
      <div class="stat-label">旷课</div>
    </div>
    <div class="stat-card leave-card">
      <div class="stat-num" style="color:var(--teal)">${counts.leave}</div>
      <div class="stat-label">请假</div>
    </div>
    <div class="stat-card early-leave-card">
      <div class="stat-num" style="color:var(--purple)">${counts.early_leave}</div>
      <div class="stat-label">早退</div>
    </div>
    <div class="stat-card">
      <div class="stat-num">${records.length}</div>
      <div class="stat-label">共计记录</div>
    </div>
  `;
}

async function loadQuickStats() {
  let query = `action=get_stats&week=${currentWeek}`;
  query = apiQuery(query);
  const res = await fetch(`${API}?${query}`);
  const data = await res.json();
  const counts = { late: 0, absent: 0, leave: 0, early_leave: 0 };
  (data.stats||[]).forEach(s => counts[s.status] = s.cnt);
  document.getElementById('quickStats').innerHTML = `
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <span class="tag late">迟到 ${counts.late}</span>
      <span class="tag absent">旷课 ${counts.absent}</span>
      <span class="tag leave">请假 ${counts.leave}</span>
      <span class="tag early_leave">早退 ${counts.early_leave}</span>
    </div>
  `;
}

function statusLabel(s) {
  return { late: '迟到', absent: '旷课', leave: '请假', early_leave: '早退' }[s] || s;
}
</script>
</body>
</html>
