SET FOREIGN_KEY_CHECKS=0;
TRUNCATE live_attendances;
INSERT INTO `live_attendances` (`id`, `company_id`, `name`, `is_flexible`, `created_at`, `updated_at`, `deleted_by`, `deleted_at`) VALUES
(1, 1, 'SUN Plaza', 1, '2024-09-19 09:25:22', '2024-09-19 09:25:22', NULL, NULL),
(2, 1, 'Live Attendance SUN INDONESIA', 0, '2024-09-19 09:25:22', '2024-09-19 09:25:22', NULL, NULL),
(3, 2, 'Live Attendance Flexible SUN ASA EDUCATION', 1, '2024-09-19 09:25:22', '2024-09-19 09:25:22', NULL, NULL),
(4, 2, 'Live Attendance SUN ASA EDUCATION', 0, '2024-09-19 09:25:22', '2024-09-19 09:25:22', NULL, NULL),
(5, 3, 'Live Attendance Flexible Alba Digital Technology', 1, '2024-09-19 09:25:22', '2024-09-19 09:25:22', NULL, NULL),
(6, 3, 'Live Attendance Alba Digital Technology', 0, '2024-09-19 09:25:22', '2024-09-19 09:25:22', NULL, NULL),
(7, 1, 'SUN Batam Cabang 2', 0, '2024-09-30 21:13:36', '2024-10-01 15:52:15', NULL, NULL),
(8, 1, 'SUN Pontianak', 0, '2024-09-30 21:19:51', '2024-09-30 21:19:51', NULL, NULL),
(9, 1, 'SUN Batam 1', 0, '2024-10-01 16:02:23', '2024-10-01 16:02:23', NULL, NULL),
(10, 1, 'SUN Semarang', 0, '2024-10-10 14:11:59', '2024-10-10 14:11:59', NULL, NULL),
(11, 1, 'SUN Yogyakarta', 0, '2024-10-10 16:12:01', '2024-10-10 16:12:01', NULL, NULL),
(12, 1, 'Flexible Sun Indo', 1, '2024-11-04 18:49:50', '2024-11-04 18:49:50', NULL, NULL),
(13, 1, 'Flexible Sun Indo 2', 1, '2024-11-04 19:04:58', '2024-11-04 19:04:58', NULL, NULL);

TRUNCATE live_attendance_locations;
INSERT INTO `live_attendance_locations` (`live_attendance_id`, `name`, `radius`, `lat`, `lng`, `created_at`, `updated_at`) VALUES
(1, 'SUN Plaza', 100, '-6.1975635', '106.7435653', '2024-09-19 09:25:22', '2024-09-19 09:25:22'),
(4, 'Location 2', 10, '-6.2229137', '106.6549371', '2024-09-19 09:25:22', '2024-09-19 09:25:22'),
(6, 'Location 1', 100, '-6.2275964', '106.6575175', '2024-09-19 09:25:22', '2024-09-19 09:25:22'),
(6, 'Location 2', 10, '-6.2229137', '106.6549371', '2024-09-19 09:25:22', '2024-09-19 09:25:22'),
(8, 'Location 1', 100, '-6.2275964', '106.6575175', '2024-09-19 09:25:22', '2024-09-19 09:25:22'),
(8, 'Location 2', 10, '-6.2229137', '106.6549371', '2024-09-19 09:25:22', '2024-09-19 09:25:22'),
(9, 'SUN Batam 1', 100, '1.1349609', '104.007108', '2024-10-01 17:30:37', '2024-10-01 17:30:37'),
(7, 'SUN Batam 2', 100, '1.1245994598263251', '104.0226848158697', '2024-10-07 09:50:31', '2024-10-07 09:50:31'),
(11, 'SUN Yogya', 100, '-7.775454499999999', '110.3682637', '2024-10-10 16:57:50', '2024-10-10 16:57:50'),
(2, 'Location 1', 100, '-6.1980061', '106.7427285', '2024-10-11 13:14:53', '2024-10-11 13:14:53'),
(8, 'SUN Pontianak', 100, '-0.026369593576645183', '109.33948724798267', '2024-10-18 17:38:35', '2024-10-18 17:38:35'),
(10, 'Semarang Cabang Gayam', 100, '-6.995648799999999', '110.4311292', '2024-11-06 11:52:15', '2024-11-06 11:52:15'),
(10, 'Semarang Cabang Tumpang', 100, '-7.00487327735647', '110.4004369417162', '2024-11-06 11:52:15', '2024-11-06 11:52:15');

UPDATE users SET live_attendance_id=1 WHERE `type` IN ('admin','user');
UPDATE users SET live_attendance_id=6 WHERE branch_id IN (15);
UPDATE users SET live_attendance_id=7 WHERE branch_id IN (25);
UPDATE users SET live_attendance_id=9 WHERE branch_id IN (19);
UPDATE users SET live_attendance_id=10 WHERE branch_id IN (21);

UPDATE users SET live_attendance_id=2 WHERE email LIKE '%user1.1@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%user2.1@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%user3.1@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%user1.2@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%user2.2@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%user3.2@gmail.com%';
UPDATE users SET live_attendance_id=13 WHERE email LIKE '%josephine.k@suneducationgroup.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%admin.alba@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%febika.nikko@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%ibnulmundzir97@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%masfuddifapratama@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%mrobbizulfikar@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%poedi1612@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%bantakarollah@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%aldynsx@gmail.com%';
UPDATE users SET live_attendance_id=5 WHERE email LIKE '%vincent@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%admin2@suneducationgroup.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%bertrand@suneducationgroup.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%citraarumingputri@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%aloysiusbagaskoro@gmail.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%indra@suneducationgroup.com%';
UPDATE users SET live_attendance_id=2 WHERE email LIKE '%angelineiskandar00@gmail.com%';
UPDATE users SET live_attendance_id=9 WHERE email LIKE '%aprillia@suneducationgroup.com%';
UPDATE users SET live_attendance_id=9 WHERE email LIKE '%ai.suneducationgroup@gmail.com%';
UPDATE users SET live_attendance_id=7 WHERE email LIKE '%ANNIE@SUNEDUCATIONGROUP.COM%';
UPDATE users SET live_attendance_id=7 WHERE email LIKE '%haritma@suneducationgroup.com%';
UPDATE users SET live_attendance_id=7 WHERE email LIKE '%atikha.suneducationgroup@gmail.com%';
UPDATE users SET live_attendance_id=7 WHERE email LIKE '%alchenny.suneducationgroup@gmail.com%';
UPDATE users SET live_attendance_id=7 WHERE email LIKE '%gloria.suneducationgroup@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%themichellepriscilla@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%ira123ptk@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%selviealexsandra@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%benardi.ori@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%poppyiga@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%danieleduarjsteven@gmail.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%jessy@suneducationgroup.com%';
UPDATE users SET live_attendance_id=8 WHERE email LIKE '%ardojnwn088@gmail.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%indah.yuliastuti@suneducationgroup.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%sarisuneducationgroup@gmail.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%nathania@suneducationgroup.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%fanny@suneducationgroup.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%eliz@suneducationgroup.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%dimas@suneducationgroup.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%ronynike.rn@gmail.com%';
UPDATE users SET live_attendance_id=10 WHERE email LIKE '%andhike@suneducationgroup.com%';
UPDATE users SET live_attendance_id=11 WHERE email LIKE '%clarita@suneducationgroup.com%';
UPDATE users SET live_attendance_id=11 WHERE email LIKE '%nita@suneducationgroup.com%';

