-- Insert employee user
INSERT INTO users (name, email, username, password, user_type, code, remember_token, created_at, updated_at) 
VALUES (
    'Demo Employee',
    'employee@stmarksms.com',
    'employee',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'employee',
    'ABCDEFGHIJ',
    'ABCDEFGHIJ',
    NOW(),
    NOW()
);
