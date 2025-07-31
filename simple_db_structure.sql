-- Chọn database
USE thichho1_it_crm_db;

-- Cấu trúc bảng deployment_requests
SHOW CREATE TABLE deployment_requests;

-- Cấu trúc bảng deployment_tasks  
SHOW CREATE TABLE deployment_tasks;

-- Cấu trúc bảng deployment_case_types
SHOW CREATE TABLE deployment_case_types;

-- Cấu trúc bảng deployment_cases
SHOW CREATE TABLE deployment_cases;

-- Cấu trúc bảng staffs
SHOW CREATE TABLE staffs;

-- Foreign keys liên quan
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = 'thichho1_it_crm_db'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND (TABLE_NAME LIKE '%deployment%' OR REFERENCED_TABLE_NAME LIKE '%deployment%');

-- Số lượng records trong các bảng deployment
SELECT 'deployment_requests' as table_name, COUNT(*) as record_count FROM deployment_requests
UNION ALL
SELECT 'deployment_cases' as table_name, COUNT(*) as record_count FROM deployment_cases  
UNION ALL
SELECT 'deployment_tasks' as table_name, COUNT(*) as record_count FROM deployment_tasks
UNION ALL
SELECT 'deployment_case_types' as table_name, COUNT(*) as record_count FROM deployment_case_types; 