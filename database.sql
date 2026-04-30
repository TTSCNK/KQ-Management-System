-- =============================================
-- 考勤管理系统 · 建表脚本（重构版）
-- 使用方法：在 phpMyAdmin 中选中你已有的数据库，
-- 然后导入此文件（无需创建新数据库）
-- =============================================

-- 班级表
CREATE TABLE IF NOT EXISTS `classes` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL UNIQUE COMMENT '班级名称',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 用户表（密码明文存储，方便直接从数据库修改）
CREATE TABLE IF NOT EXISTS `users` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '登录账号',
    password VARCHAR(255) NOT NULL COMMENT '登录密码（明文）',
    role ENUM('super_admin','class_manager') NOT NULL COMMENT '角色:super_admin=总管理者,class_manager=班级管理者',
    class_id INT NULL COMMENT '关联班级ID（班级管理者必填，总管理者为NULL）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 学生表
CREATE TABLE IF NOT EXISTS `students` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE COMMENT '学号',
    name VARCHAR(50) NOT NULL COMMENT '姓名',
    class_id INT NOT NULL COMMENT '所属班级ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB;

-- 考勤记录表
CREATE TABLE IF NOT EXISTS `attendance` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL COMMENT '学号',
    student_name VARCHAR(50) NOT NULL COMMENT '姓名',
    class_id INT NOT NULL COMMENT '所属班级ID',
    record_date DATE NOT NULL COMMENT '日期',
    week_number INT NOT NULL COMMENT '第几周',
    day_of_week TINYINT NOT NULL COMMENT '星期几(1=周一,5=周五)',
    period TINYINT NOT NULL DEFAULT 1 COMMENT '第几节课(1=第一节,2=第二节,3=第三节,4=第四节,5=晚自习)',
    status ENUM('late','absent','leave','early_leave') NOT NULL COMMENT '状态:late迟到,absent旷课,leave请假,early_leave早退',
    reason TEXT COMMENT '原因/备注',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (record_date),
    INDEX idx_week (week_number),
    INDEX idx_student (student_id),
    INDEX idx_class (class_id),
    INDEX idx_period (period),
    UNIQUE KEY uk_student_date_period (student_id, record_date, period)
) ENGINE=InnoDB;

-- 插入示例班级
INSERT INTO classes (id, class_name) VALUES
(1, '计算机2401班'),
(2, '计算机2402班'),
(3, '软件工程2401班');

-- 插入示例总管理者账号
INSERT INTO users (username, password, role, class_id) VALUES
('college_admin', '123456', 'super_admin', NULL),
('student_union', '123456', 'super_admin', NULL);

-- 插入示例班级管理者账号
INSERT INTO users (username, password, role, class_id) VALUES
('jsj2401', '123456', 'class_manager', 1),
('jsj2402', '123456', 'class_manager', 2),
('rj2401', '123456', 'class_manager', 3);

-- 插入示例学生数据
INSERT INTO students (student_id, name, class_id) VALUES
('2024001', '张三', 1),
('2024002', '李四', 1),
('2024003', '王五', 1),
('2024004', '赵六', 1),
('2024005', '陈七', 1),
('2024006', '刘一', 2),
('2024007', '孙二', 2),
('2024008', '周三', 2),
('2024009', '吴四', 3),
('2024010', '郑五', 3);

-- 插入示例考勤数据
INSERT INTO attendance (student_id, student_name, class_id, record_date, week_number, day_of_week, period, status, reason) VALUES
('2024001', '张三', 1, '2025-02-24', 1, 1, 1, 'late', '睡过头了'),
('2024002', '李四', 1, '2025-02-25', 1, 2, 2, 'absent', '无故缺席'),
('2024003', '王五', 1, '2025-02-26', 1, 3, 1, 'leave', '生病请假'),
('2024001', '张三', 1, '2025-03-03', 2, 1, 1, 'late', '交通堵塞'),
('2024004', '赵六', 1, '2025-03-05', 2, 3, 3, 'absent', ''),
('2024002', '李四', 1, '2025-03-10', 3, 1, 1, 'leave', '家庭原因'),
('2024005', '陈七', 1, '2025-03-12', 3, 3, 5, 'early_leave', '提前离校'),
('2024001', '张三', 1, '2025-03-18', 4, 2, 2, 'absent', '无故旷课'),
('2024006', '刘一', 2, '2025-02-24', 1, 1, 1, 'late', '迟到'),
('2024007', '孙二', 2, '2025-02-25', 1, 2, 5, 'early_leave', '早退'),
('2024009', '吴四', 3, '2025-02-26', 1, 3, 1, 'leave', '请假');

-- =============================================
-- 如果是从旧版升级，执行以下SQL添加period字段：
-- =============================================
-- ALTER TABLE attendance ADD COLUMN period TINYINT NOT NULL DEFAULT 1 COMMENT '第几节课(1=第一节,2=第二节,3=第三节,4=第四节,5=晚自习)' AFTER day_of_week;
-- ALTER TABLE attendance ADD INDEX idx_period (period);
-- ALTER TABLE attendance ADD UNIQUE KEY uk_student_date_period (student_id, record_date, period);
-- ALTER TABLE attendance DROP INDEX IF EXISTS uk_student_date;
