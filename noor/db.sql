-- قاعدة بيانات مشروع نظام إدارة المستخدمين
-- تم إنشاؤها بواسطة: نظام إدارة المستخدمين v1.0.0

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS `project_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `project_db`;

-- جدول المستخدمين
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول رموز التذكر
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(32) NOT NULL,
  `validator_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول محاولات تسجيل الدخول
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `last_attempt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blocked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج مستخدم مدير افتراضي
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ملاحظة: كلمة المرور الافتراضية للمدير هي "password"
-- يجب تغييرها فوراً بعد التثبيت لأسباب أمنية
