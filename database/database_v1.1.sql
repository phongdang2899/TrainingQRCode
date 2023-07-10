/*
 Navicat Premium Data Transfer

 Source Server         : Localhost_Docker
 Source Server Type    : MySQL
 Source Server Version : 100505
 Source Host           : localhost:3306
 Source Schema         : lav_reward

 Target Server Type    : MySQL
 Target Server Version : 100505
 File Encoding         : 65001

 Date: 17/07/2021 16:10:37
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for zones
-- ----------------------------
DROP TABLE IF EXISTS `zones`;
CREATE TABLE `zones`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `zones_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `zones_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `zones_name_index`(`name`) USING BTREE,
  CONSTRAINT `zones_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `zones_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for zone_provinces
-- ----------------------------
DROP TABLE IF EXISTS `zone_provinces`;
CREATE TABLE `zone_provinces`  (
  `zone_id` bigint(20) UNSIGNED NOT NULL,
  `province_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`zone_id`, `province_id`) USING BTREE,
  INDEX `zone_provinces_province_id_foreign`(`province_id`) USING BTREE,
  INDEX `zone_provinces_created_by_foreign`(`created_by`) USING BTREE,
  CONSTRAINT `zone_provinces_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `zone_provinces_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `zone_provinces_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for watchers
-- ----------------------------
DROP TABLE IF EXISTS `watchers`;
CREATE TABLE `watchers`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `times` smallint(5) UNSIGNED NOT NULL,
  `previous_time` datetime(0) NULL DEFAULT NULL,
  `total_times` smallint(5) UNSIGNED NOT NULL,
  `phone_number` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `watchers_ip_index`(`ip`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` tinyint(4) NOT NULL DEFAULT 0,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `avatar` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL DEFAULT 3,
  `email_verified_at` timestamp(0) NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `users_role_id_foreign`(`role_id`) USING BTREE,
  INDEX `users_first_name_last_name_index`(`first_name`, `last_name`) USING BTREE,
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'admin', 'Quản trị', 'Hệ thống', 0, '', 'admin@rewardpage.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2021-07-14 05:00:37', 'NIMNKGqWKZ', '2021-07-14 05:00:37', NULL, NULL);

-- ----------------------------
-- Table structure for transactions
-- ----------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions`  (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL,
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `transactions_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `transactions_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `transactions_status_index`(`status`) USING BTREE,
  CONSTRAINT `transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `transactions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transaction_logs
-- ----------------------------
DROP TABLE IF EXISTS `transaction_logs`;
CREATE TABLE `transaction_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `transaction_logs_transaction_id_foreign`(`transaction_id`) USING BTREE,
  CONSTRAINT `transaction_logs_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for transaction_items
-- ----------------------------
DROP TABLE IF EXISTS `transaction_items`;
CREATE TABLE `transaction_items`  (
  `transaction_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_id` bigint(20) UNSIGNED NOT NULL,
  `value` double NOT NULL,
  PRIMARY KEY (`transaction_id`, `code_id`) USING BTREE,
  INDEX `transaction_items_code_id_foreign`(`code_id`) USING BTREE,
  CONSTRAINT `transaction_items_code_id_foreign` FOREIGN KEY (`code_id`) REFERENCES `codes` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `transaction_items_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for third_party_transactions
-- ----------------------------
DROP TABLE IF EXISTS `third_party_transactions`;
CREATE TABLE `third_party_transactions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL,
  `send_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `received_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for system_logs
-- ----------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs`  (
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  `type` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`created_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'admin', 'Quản trị viên', '2021-07-14 05:00:37', NULL, NULL);
INSERT INTO `roles` VALUES (2, 'manager', 'Điều hành viên', '2021-07-14 05:00:37', NULL, NULL);
INSERT INTO `roles` VALUES (3, 'member', 'Thành viên', '2021-07-14 05:00:37', NULL, NULL);

-- ----------------------------
-- Table structure for provinces
-- ----------------------------
DROP TABLE IF EXISTS `provinces`;
CREATE TABLE `provinces`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `lat` double(8, 2) NULL DEFAULT NULL,
  `long` double(8, 2) NULL DEFAULT NULL,
  `status` tinyint(4) NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `provinces_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `provinces_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `provinces_name_index`(`name`) USING BTREE,
  CONSTRAINT `provinces_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `provinces_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 64 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of provinces
-- ----------------------------
INSERT INTO `provinces` VALUES (1, 'An Giang', 'an-giang', 10.39, 105.44, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (2, 'Bà Rịa', 'ba-ria', 10.50, 107.17, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (3, 'Bạc Liêu', 'bac-lieu', 9.29, 105.73, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (4, 'Bắc Kạn', 'bac-kan', 22.15, 105.83, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (5, 'Bắc Giang', 'bac-giang', 21.27, 106.19, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (6, 'Bắc Ninh', 'bac-ninh', 21.19, 106.08, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (7, 'Bến Tre', 'ben-tre', 10.24, 106.38, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (8, 'Bình Dương', 'binh-duong', 10.98, 106.65, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (9, 'Bình Định', 'binh-dinh', 13.78, 109.22, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (10, 'Bình Phước', 'binh-phuoc', 11.65, 106.61, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (11, 'Bình Thuận', 'binh-thuan', 10.93, 108.10, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (12, 'Cà Mau', 'ca-mau', 9.18, 105.15, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (13, 'Cao Bằng', 'cao-bang', 22.67, 106.26, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (14, 'Cần Thơ', 'can-tho', 10.04, 105.79, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (15, 'Đà Nẵng', 'da-nang', 16.07, 108.22, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (16, 'Đắk Lắk', 'dak-lak', 12.67, 108.04, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (17, 'Đắk Nông', 'dak-nong', 12.00, 107.69, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (18, 'Điện Biên', 'dien-bien', 21.39, 103.02, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (19, 'Đồng Nai', 'dong-nai', 10.94, 106.82, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (20, 'Đồng Tháp', 'dong-thap', 10.29, 105.76, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (21, 'Gia Lai', 'gia-lai', 13.98, 108.00, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (22, 'Hà Giang', 'ha-giang', 22.82, 104.98, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (23, 'Hà Nam', 'ha-nam', 20.55, 105.91, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (24, 'Hà Nội', 'ha-noi', 21.02, 105.84, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (25, 'Hà Tĩnh', 'ha-tinh', 18.34, 105.91, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (26, 'Hải Dương', 'hai-duong', 20.94, 106.33, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (27, 'Hải Phòng', 'hai-phong', 20.86, 106.68, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (28, 'Hòa Bình', 'hoa-binh', 20.82, 105.34, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (29, 'Hồ Chí Minh', 'ho-chi-minh', 10.82, 106.63, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (30, 'Hậu Giang', 'hau-giang', 9.78, 105.47, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (31, 'Hưng Yên', 'hung-yen', 20.65, 106.05, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (32, 'Khánh Hòa', 'khanh-hoa', 12.25, 109.19, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (33, 'Kiên Giang', 'kien-giang', 10.01, 105.08, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (34, 'Kon Tum', 'kon-tum', 14.35, 108.01, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (35, 'Lai Châu', 'lai-chau', 22.40, 103.46, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (36, 'Lào Cai', 'lao-cai', 22.49, 103.97, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (37, 'Lạng Sơn', 'lang-son', 21.85, 106.76, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (38, 'Lâm Đồng', 'lam-dong', 11.55, 107.81, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (39, 'Long An', 'long-an', 10.61, 106.67, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (40, 'Nam Định', 'nam-dinh', 20.43, 106.18, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (41, 'Nghệ An', 'nghe-an', 18.67, 105.69, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (42, 'Ninh Bình', 'ninh-binh', 20.26, 105.98, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (43, 'Ninh Thuận', 'ninh-thuan', 11.56, 108.99, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (44, 'Phú Thọ', 'phu-tho', 21.32, 105.40, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (45, 'Phú Yên', 'phu-yen', 13.46, 109.22, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (46, 'Quảng Bình', 'quang-binh', 17.47, 106.62, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (47, 'Quảng Nam', 'quang-nam', 15.57, 108.47, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (48, 'Quảng Ngãi', 'quang-ngai', 15.12, 108.79, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (49, 'Quảng Ninh', 'quang-ninh', 20.95, 107.07, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (50, 'Quảng Trị', 'quang-tri', 16.82, 107.10, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (51, 'Sóc Trăng', 'soc-trang', 9.60, 105.97, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (52, 'Sơn La', 'son-la', 21.33, 103.92, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (53, 'Tây Ninh', 'tay-ninh', 11.31, 106.10, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (54, 'Thái Bình', 'thai-binh', 20.45, 106.34, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (55, 'Thái Nguyên', 'thai-nguyen', 21.59, 105.85, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (56, 'Thanh Hóa', 'thanh-hoa', 19.80, 105.77, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (57, 'Thừa Thiên - Huế', 'thua-thien-hue', 16.46, 107.60, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (58, 'Tiền Giang', 'tien-giang', 10.36, 106.36, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (59, 'Trà Vinh', 'tra-vinh', 9.95, 106.34, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (60, 'Tuyên Quang', 'tuyen-quang', 21.82, 105.21, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (61, 'Vĩnh Phúc', 'vinh-phuc', 21.31, 105.60, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (62, 'Vĩnh Long', 'vinh-long', 10.25, 105.97, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');
INSERT INTO `provinces` VALUES (63, 'Yên Bái', 'yen-bai', 21.72, 104.91, NULL, 1, NULL, '2021-07-14 05:04:07', '2021-07-14 05:04:07');

-- ----------------------------
-- Table structure for product_logs
-- ----------------------------
DROP TABLE IF EXISTS `product_logs`;
CREATE TABLE `product_logs`  (
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`created_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  INDEX `password_resets_email_index`(`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for otp_trackings
-- ----------------------------
DROP TABLE IF EXISTS `otp_trackings`;
CREATE TABLE `otp_trackings`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active_code` char(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `times` smallint(5) UNSIGNED NOT NULL,
  `activated_at` datetime(0) NOT NULL,
  `previous_code` char(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `previous_time` datetime(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `otp_trackings_customer_id_foreign`(`customer_id`) USING BTREE,
  CONSTRAINT `otp_trackings_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for customers
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` tinyint(4) NULL DEFAULT NULL,
  `address` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `province_id` bigint(20) UNSIGNED NOT NULL,
  `id_card_number` int(11) NULL DEFAULT NULL,
  `brand_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `approved_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `approved_at` datetime(0) NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `customers_province_id_foreign`(`province_id`) USING BTREE,
  INDEX `customers_approved_by_foreign`(`approved_by`) USING BTREE,
  INDEX `customers_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `customers_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `customers_phone_number_first_name_last_name_index`(`phone_number`, `first_name`, `last_name`) USING BTREE,
  INDEX `customers_status_province_id_index`(`status`, `province_id`) USING BTREE,
  CONSTRAINT `customers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `customers_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for configs
-- ----------------------------
DROP TABLE IF EXISTS `configs`;
CREATE TABLE `configs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `configs_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `configs_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `configs_entity_id_entity_type_value_index`(`entity_id`, `entity_type`, `value`) USING BTREE,
  CONSTRAINT `configs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `configs_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for codes
-- ----------------------------
DROP TABLE IF EXISTS `codes`;
CREATE TABLE `codes`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `value` double NOT NULL,
  `status` tinyint(4) NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `activated_date` datetime(0) NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `codes_campaign_id_foreign`(`campaign_id`) USING BTREE,
  INDEX `codes_customer_id_foreign`(`customer_id`) USING BTREE,
  INDEX `codes_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `codes_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `codes_code_status_index`(`code`, `status`) USING BTREE,
  CONSTRAINT `codes_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `codes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `codes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for campaigns
-- ----------------------------
DROP TABLE IF EXISTS `campaigns`;
CREATE TABLE `campaigns`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` datetime(0) NOT NULL,
  `end_date` datetime(0) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `campaigns_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `campaigns_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `campaigns_deleted_by_foreign`(`deleted_by`) USING BTREE,
  INDEX `campaigns_code_name_index`(`code`, `name`) USING BTREE,
  CONSTRAINT `campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `campaigns_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `campaigns_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of campaigns
-- ----------------------------
INSERT INTO `campaigns` VALUES (1, 'Trường Campaign', 'truong-campaign', '2021-07-15 17:03:04', '2021-07-20 17:03:08', 1, 1, NULL, NULL, '2021-07-15 17:03:45', NULL, NULL);

-- ----------------------------
-- Table structure for campaign_configs
-- ----------------------------
DROP TABLE IF EXISTS `campaign_configs`;
CREATE TABLE `campaign_configs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int(10) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `campaign_configs_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `campaign_configs_updated_by_foreign`(`updated_by`) USING BTREE,
  INDEX `campaign_configs_campaign_id_type_value_index`(`campaign_id`, `type`, `value`) USING BTREE,
  CONSTRAINT `campaign_configs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `campaign_configs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `campaign_configs_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of campaign_configs
-- ----------------------------
INSERT INTO `campaign_configs` VALUES (1, 1, '20000', 7000, 0, 1, NULL, '2021-07-15 17:04:31', NULL);
INSERT INTO `campaign_configs` VALUES (2, 1, '30000', 5000, 0, 1, NULL, '2021-07-15 17:04:49', NULL);

-- ----------------------------
-- Table structure for api_logs
-- ----------------------------
DROP TABLE IF EXISTS `api_logs`;
CREATE TABLE `api_logs`  (
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`created_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
