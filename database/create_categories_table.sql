-- Create categories table if it doesn't exist
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default categories if table is empty
INSERT IGNORE INTO `categories` (`name`) VALUES 
('Cement'),
('Steel Bars'),
('Aggregates'),
('Lumber'),
('Plumbing'),
('Electrical'),
('Hardware'),
('Tools'),
('Paint'),
('Safety Equipment');
