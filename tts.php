<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>项目管理者 - 系统管理</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #0f0f1a;
    --panel: #16162a;
    --surface: #1e1e35;
    --border: #2d2d4e;
    --text: #e8e4d9;
    --muted: #7a7a9a;
    --gold: #f0c040;
    --red: #e05252;
    --teal: #40c8a0;
    --accent: #6060e8;
    --purple: #a060e8;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Noto Serif SC', serif;
    background: var(--ink);
    color: var(--text);
    min-height: 100vh;
  }

  .tts-layout {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px;
  }

  .page-header {
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .page-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--gold);
    letter-spacing: 3px;
  }
  .page-subtitle {
    font-size: 0.72rem;
    letter-spacing: 3px;
    color: var(--muted);
    margin-top: 4px;
    font-family: 'JetBrains Mono', monospace;
  }

  .section {
    background: var(--panel);
    border: 1px solid var(--border);
    border-top: 3px solid var(--gold);
    padding: 24px;
    margin-bottom: 24px;
  }
  .section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gold);
    letter-spacing: 2px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
  }

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
  .admin-table .actions { display: flex; gap: 6px; flex-wrap: wrap; }

  .tag {
    display: inline-block;
    padding: 2px 10px;
    font-size: 0.75rem;
    letter-spacing: 1px;
    font-weight: 700;
  }
  .tag.super { background: rgba(240,192,64,0.15); color: var(--gold); border: 1px solid rgba(240,192,64,0.4); }
  .tag.class-m { background: rgba(64,200,160,0.15); color: var(--teal); border: 1px solid rgba(64,200,160,0.4); }
  .tag.unassigned { background: rgba(127,127,154,0.15); color: var(--muted); border: 1px solid rgba(127,127,154,0.4); }

  .add-form {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
    padding: 20px;
    background: var(--surface);
    border: 1px solid var(--border);
  }
  .form-group { margin-bottom: 0; }
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
    background: var(--panel);
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
  .form-group select option { background: var(--panel); }
  .form-group textarea { height: 80px; resize: vertical; }

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
  .modal .form-group { margin-bottom: 18px; }
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

  .warning-box {
    background: rgba(224,82,82,0.1);
    border: 1px solid rgba(224,82,82,0.3);
    border-left: 3px solid var(--red);
    padding: 14px 18px;
    margin-bottom: 24px;
    font-size: 0.82rem;
    color: var(--muted);
    line-height: 1.8;
  }
  .warning-box strong { color: var(--red); }

  @media (max-width: 768px) {
    .tts-layout { padding: 16px; }
    .add-form { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div class="tts-layout">
  <div class="page-header">
    <div>
      <div class="page-title">🔐 项目管理者</div>
      <div class="page-subtitle">SYSTEM ADMIN · FULL MANAGEMENT</div>
    </div>
  </div>

  <div class="warning-box">
    <strong>⚠ 安全提示</strong>：此页面无需登录即可访问，部署后请通过服务器配置限制访问IP或修改文件名。<br>
    密码以明文存储在数据库中，可直接在数据库中修改。
  </div>

  <div class="section">
    <div class="section-title">➕ 添加账号</div>
    <div class="add-form">
      <div class="form-group">
        <label>账号</label>
        <input type="text" id="nu_username" placeholder="登录账号">
      </div>
      <div class="form-group">
        <label>密码</label>
        <input type="text" id="nu_password" placeholder="登录密码">
      </div>
      <div class="form-group">
        <label>角色</label>
        <select id="nu_role">
          <option value="super_admin">总管理者</option>
          <option value="class_manager">班级管理者</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <button class="btn btn-primary" onclick="addUser()">添 加</button>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">🏫 班级管理</div>
    <div class="add-form" style="margin-bottom:20px">
      <div class="form-group">
        <label>班级名称</label>
        <input type="text" id="nc_name" placeholder="例如：计算机2403班">
      </div>
      <div class="form-group">
        <label>指定管理者（选填）</label>
        <select id="nc_manager">
          <option value="0">暂不指定</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <button class="btn btn-primary" onclick="addClass()">创建班级</button>
      </div>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>班级名称</th>
          <th>学生人数</th>
          <th>管理者账号</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody id="classesTbody">
        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted)">加载中...</td></tr>
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">📋 所有用户账号</div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>账号</th>
          <th>密码</th>
          <th>角色</th>
          <th>关联班级</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody id="usersTbody">
        <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="editUserModal">
  <div class="modal">
    <h3>✎ 编辑用户</h3>
    <input type="hidden" id="eu_id">
    <div class="form-group">
      <label>账号</label>
      <input type="text" id="eu_username">
    </div>
    <div class="form-group">
      <label>新密码（留空不修改）</label>
      <input type="text" id="eu_password" placeholder="输入新密码或留空">
    </div>
    <div class="form-group">
      <label>角色</label>
      <select id="eu_role">
        <option value="super_admin">总管理者</option>
        <option value="class_manager">班级管理者</option>
      </select>
    </div>
    <div class="form-group">
      <label>关联班级（班级管理者必选）</label>
      <select id="eu_class_id">
        <option value="0">无</option>
      </select>
    </div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="closeModal('editUserModal')">取消</button>
      <button class="btn btn-primary" onclick="saveEditUser()">保 存</button>
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
let allClasses = [];
let allManagers = [];

window.onload = () => {
    loadUsers();
    loadClasses();
    loadManagers();
};

async function loadManagers() {
    const res = await fetch(`${API}?action=get_class_managers`);
    const data = await res.json();
    if (data.success) allManagers = data.data || [];
    populateManagerSelects();
}

function populateManagerSelects() {
    const unassigned = allManagers.filter(m => !m.class_id);
    const makeOpts = (selVal) => '<option value="0">不指定</option>' +
        allManagers.map(m => {
            const tag = m.class_id ? `（已关联班级#${m.class_id}）` : '（未关联）';
            return `<option value="${m.id}" ${m.id == selVal ? 'selected' : ''}>${m.username} ${tag}</option>`;
        }).join('');

    document.getElementById('nc_manager').innerHTML = '<option value="0">暂不指定</option>' +
        unassigned.map(m => `<option value="${m.id}">${m.username}（未关联）</option>`).join('');
}

async function loadUsers() {
    const res = await fetch(`${API}?action=get_users`);
    const data = await res.json();
    if (!data.success) return;
    const tbody = document.getElementById('usersTbody');
    const users = data.data || [];
    if (!users.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">暂无用户</td></tr>';
        return;
    }
    tbody.innerHTML = users.map(u => `
        <tr>
            <td style="font-family:monospace;color:var(--muted)">#${u.id}</td>
            <td style="font-weight:600">${u.username}</td>
            <td style="font-family:monospace;color:var(--gold)">${u.password}</td>
            <td><span class="tag ${u.role === 'super_admin' ? 'super' : 'class-m'}">${u.role === 'super_admin' ? '总管理者' : '班级管理者'}</span></td>
            <td style="color:var(--muted)">${u.class_name || '—'}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-edit btn-sm" onclick="openEditUser(${u.id}, '${u.username}', '${u.password}', '${u.role}', ${u.class_id || 0})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id}, '${u.username}')">删除</button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function loadClasses() {
    const res = await fetch(`${API}?action=get_classes`);
    const data = await res.json();
    if (!data.success) return;
    allClasses = data.data || [];
    const tbody = document.getElementById('classesTbody');
    if (!allClasses.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted)">暂无班级</td></tr>';
        return;
    }
    tbody.innerHTML = allClasses.map(c => `
        <tr>
            <td style="font-family:monospace;color:var(--muted)">#${c.id}</td>
            <td style="font-weight:600">${c.class_name}</td>
            <td style="font-family:monospace">${c.student_count || 0}</td>
            <td>${c.manager_username ? `<span class="tag class-m">${c.manager_username}</span>` : '<span class="tag unassigned">未设置</span>'}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-edit btn-sm" onclick="openEditClass(${c.id}, '${c.class_name}', ${c.manager_user_id || 0})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteClass(${c.id}, '${c.class_name}')">删除</button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function addUser() {
    const username = document.getElementById('nu_username').value.trim();
    const password = document.getElementById('nu_password').value.trim();
    const role = document.getElementById('nu_role').value;
    if (!username || !password) { showToast('请填写账号和密码', 'error'); return; }
    const res = await fetch(`${API}?action=add_user`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, role, class_id: 0 })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        document.getElementById('nu_username').value = '';
        document.getElementById('nu_password').value = '';
        loadUsers();
        loadManagers();
    }
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
        loadClasses();
        loadUsers();
        loadManagers();
    }
}

function openEditClass(id, name, managerUserId) {
    document.getElementById('ec_id').value = id;
    document.getElementById('ec_name').value = name;
    const sel = document.getElementById('ec_manager');
    sel.innerHTML = '<option value="0">不指定</option>' +
        allManagers.map(m => {
            const tag = m.class_id && m.class_id != id ? `（已关联班级#${m.class_id}）` : (m.class_id == id ? '（当前）' : '（未关联）');
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
        loadClasses();
        loadUsers();
        loadManagers();
    }
}

async function deleteClass(id, name) {
    if (!confirm(`确定删除班级"${name}"？\n⚠️ 该班级的所有学生和考勤记录也将被删除！`)) return;
    const res = await fetch(`${API}?action=delete_class`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ class_id: id })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        loadUsers();
        loadClasses();
        loadManagers();
    }
}

function openEditUser(id, username, password, role, classId) {
    document.getElementById('eu_id').value = id;
    document.getElementById('eu_username').value = username;
    document.getElementById('eu_password').value = '';
    document.getElementById('eu_role').value = role;
    const classSelect = document.getElementById('eu_class_id');
    classSelect.innerHTML = '<option value="0">无</option>' +
        allClasses.map(c => `<option value="${c.id}" ${c.id === classId ? 'selected' : ''}>${c.class_name}</option>`).join('');
    document.getElementById('editUserModal').classList.add('open');
}

async function saveEditUser() {
    const id = document.getElementById('eu_id').value;
    const username = document.getElementById('eu_username').value.trim();
    const password = document.getElementById('eu_password').value.trim();
    const role = document.getElementById('eu_role').value;
    const class_id = document.getElementById('eu_class_id').value;
    if (!username) { showToast('账号不能为空', 'error'); return; }
    const body = { id, username, role, class_id: parseInt(class_id) };
    if (password) body.password = password;
    const res = await fetch(`${API}?action=update_user`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) {
        closeModal('editUserModal');
        loadUsers();
        loadClasses();
        loadManagers();
    }
}

async function deleteUser(id, username) {
    if (!confirm(`确定删除用户"${username}"？`)) return;
    const res = await fetch(`${API}?action=delete_user`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    const data = await res.json();
    showToast(data.msg, data.success ? 'success' : 'error');
    if (data.success) { loadUsers(); loadClasses(); loadManagers(); }
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
