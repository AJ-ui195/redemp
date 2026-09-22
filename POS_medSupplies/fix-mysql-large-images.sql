-- MySQL Configuration Fix for Large Images
-- Run this in MySQL/MariaDB to fix "MySQL server has gone away" error

-- Check current max_allowed_packet setting
SHOW VARIABLES LIKE 'max_allowed_packet';

-- Set max_allowed_packet to 64MB (67108864 bytes) for current session
SET SESSION max_allowed_packet = 67108864;

-- To make this permanent, add to MySQL config file (my.ini or my.cnf):
-- [mysqld]
-- max_allowed_packet = 64M
--
-- Then restart MySQL service

