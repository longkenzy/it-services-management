-- Hiển thị tất cả databases
SHOW DATABASES;

-- Chọn database
USE thichho1_it_crm_db;

-- Hiển thị tất cả tables
SHOW TABLES;

-- Hiển thị cấu trúc từng table
-- Bảng staffs
DESCRIBE staffs;
SHOW CREATE TABLE staffs;

-- Bảng partner_companies  
DESCRIBE partner_companies;
SHOW CREATE TABLE partner_companies;

-- Bảng deployment_requests
DESCRIBE deployment_requests;
SHOW CREATE TABLE deployment_requests;

-- Bảng deployment_cases
DESCRIBE deployment_cases;
SHOW CREATE TABLE deployment_cases;

-- Bảng deployment_tasks
DESCRIBE deployment_tasks;
SHOW CREATE TABLE deployment_tasks;

-- Bảng maintenance_requests
DESCRIBE maintenance_requests;
SHOW CREATE TABLE maintenance_requests;

-- Bảng maintenance_cases
DESCRIBE maintenance_cases;
SHOW CREATE TABLE maintenance_cases;

-- Bảng maintenance_tasks
DESCRIBE maintenance_tasks;
SHOW CREATE TABLE maintenance_tasks;

-- Bảng user_activity_logs
DESCRIBE user_activity_logs;
SHOW CREATE TABLE user_activity_logs;

-- Bảng positions
DESCRIBE positions;
SHOW CREATE TABLE positions;

-- Bảng departments
DESCRIBE departments;
SHOW CREATE TABLE departments;

-- Bảng eu_companies
DESCRIBE eu_companies;
SHOW CREATE TABLE eu_companies;

-- Bảng case_types
DESCRIBE case_types;
SHOW CREATE TABLE case_types;

-- Hiển thị foreign keys
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Hiển thị indexes
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE,
    SEQ_IN_INDEX
FROM 
    INFORMATION_SCHEMA.STATISTICS 
WHERE 
    TABLE_SCHEMA = DATABASE()
ORDER BY 
    TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Hiển thị số lượng records trong mỗi table
SELECT 
    'staffs' as table_name, COUNT(*) as record_count FROM staffs
UNION ALL
SELECT 
    'partner_companies' as table_name, COUNT(*) as record_count FROM partner_companies
UNION ALL
SELECT 
    'deployment_requests' as table_name, COUNT(*) as record_count FROM deployment_requests
UNION ALL
SELECT 
    'deployment_cases' as table_name, COUNT(*) as record_count FROM deployment_cases
UNION ALL
SELECT 
    'deployment_tasks' as table_name, COUNT(*) as record_count FROM deployment_tasks
UNION ALL
SELECT 
    'maintenance_requests' as table_name, COUNT(*) as record_count FROM maintenance_requests
UNION ALL
SELECT 
    'maintenance_cases' as table_name, COUNT(*) as record_count FROM maintenance_cases
UNION ALL
SELECT 
    'maintenance_tasks' as table_name, COUNT(*) as record_count FROM maintenance_tasks
UNION ALL
SELECT 
    'user_activity_logs' as table_name, COUNT(*) as record_count FROM user_activity_logs
UNION ALL
SELECT 
    'positions' as table_name, COUNT(*) as record_count FROM positions
UNION ALL
SELECT 
    'departments' as table_name, COUNT(*) as record_count FROM departments
UNION ALL
SELECT 
    'eu_companies' as table_name, COUNT(*) as record_count FROM eu_companies
UNION ALL
SELECT 
    'case_types' as table_name, COUNT(*) as record_count FROM case_types; 