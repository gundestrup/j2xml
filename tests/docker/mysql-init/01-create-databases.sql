-- Create Joomla 6 database (Joomla 5 DB is created by MYSQL_DATABASE env var)
CREATE DATABASE IF NOT EXISTS joomla6 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON joomla6.* TO 'joomla'@'%';
FLUSH PRIVILEGES;
