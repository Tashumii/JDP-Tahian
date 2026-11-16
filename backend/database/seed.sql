USE joey_tahian;

-- Insert default admin user (password: password)
INSERT INTO users (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username=username;

-- Insert sample inventory
INSERT INTO inventory (name, category, price, quantity, stage, item_condition, image_url) VALUES
('Queen Size Bedsheet Set', 'Bedsheets', 850.00, 30, 'on_hand', 'new', 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&h=400&fit=crop'),
('King Size Duvet Cover', 'Bedsheets', 1200.00, 15, 'packed', 'new', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&h=400&fit=crop'),
('Fleece Blanket - Double', 'Blankets', 550.00, 50, 'on_hand', 'new', 'https://images.unsplash.com/photo-1580301762395-57af8be28eb0?w=600&h=400&fit=crop'),
('Knitted Throw Blanket', 'Blankets', 399.00, 20, 'made', 'new', 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&h=400&fit=crop'),
('Blackout Curtains (Pair)', 'Curtains', 980.00, 25, 'on_hand', 'new', 'https://images.unsplash.com/photo-1586105251261-72a756497a11?w=600&h=400&fit=crop');

