-- Add code_expires_at column to users table if it doesn't exist
ALTER TABLE `users` ADD COLUMN `code_expires_at` TIMESTAMP NULL DEFAULT NULL AFTER `verification_code`;
