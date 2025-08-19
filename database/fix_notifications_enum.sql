-- Fix notifications table ENUM type to include 'internal_case'
-- Run this in phpMyAdmin or MySQL client

ALTER TABLE notifications 
MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';

-- Verify the change
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'notifications' 
AND COLUMN_NAME = 'type';
