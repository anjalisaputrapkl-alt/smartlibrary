-- Add fine_amount to borrows table
ALTER TABLE `borrows` ADD COLUMN IF NOT EXISTS `fine_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `status`;

-- Add missing columns to schools table
ALTER TABLE `schools` 
ADD COLUMN IF NOT EXISTS `borrow_duration` INT DEFAULT 7 AFTER `founded_year`,
ADD COLUMN IF NOT EXISTS `late_fine` DECIMAL(10,2) DEFAULT 500.00 AFTER `borrow_duration`,
ADD COLUMN IF NOT EXISTS `max_books` INT DEFAULT 3 AFTER `late_fine`;
