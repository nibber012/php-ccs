-- First, check if user exists and delete if it does
DELETE FROM super_admins WHERE user_id IN (SELECT id FROM users WHERE email = 'superadmin@ccs.edu.ph');
DELETE FROM users WHERE email = 'superadmin@ccs.edu.ph';

-- Then insert into users table
INSERT INTO users (email, password, first_name, last_name, role, status)
VALUES (
    'superadmin@ccs.edu.ph',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is "password"
    'Super',
    'Admin',
    'super_admin',
    'active'
);

-- Then, insert into super_admins table using the last inserted user_id
INSERT INTO super_admins (user_id, first_name, last_name)
SELECT id, first_name, last_name FROM users WHERE email = 'superadmin@ccs.edu.ph';
