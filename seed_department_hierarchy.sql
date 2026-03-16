INSERT INTO departments (department_id, department_name, status)
VALUES
    (1, 'Account', 1),
    (2, 'IT', 1),
    (3, 'HR', 1),
    (4, 'Sales', 1),
    (5, 'Operations', 1),
    (6, 'Marketing', 1),
    (7, 'Support', 1),
    (8, 'Purchase', 1)
ON DUPLICATE KEY UPDATE
    department_name = VALUES(department_name),
    status = VALUES(status);

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'rakesh.shah', 'Rakesh Shah', 'rakesh.shah@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 1, NULL, 1, 38, '9990001001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'rakesh.shah');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'priya.mehta', 'Priya Mehta', 'priya.mehta@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 1, NULL, 1, 38, '9990001002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'priya.mehta');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'manan.joshi', 'Manan Joshi', 'manan.joshi@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 1, NULL, 1, 38, '9990001003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'manan.joshi');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'anjali.desai', 'Anjali Desai', 'anjali.desai@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 3, NULL, 1, 38, '9990002001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'anjali.desai');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'komal.trivedi', 'Komal Trivedi', 'komal.trivedi@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 3, NULL, 1, 38, '9990002002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'komal.trivedi');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'harsh.rana', 'Harsh Rana', 'harsh.rana@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 3, NULL, 1, 38, '9990002003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'harsh.rana');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'vivek.sharma', 'Vivek Sharma', 'vivek.sharma@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 4, NULL, 1, 38, '9990003001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'vivek.sharma');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'sonal.patel', 'Sonal Patel', 'sonal.patel@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 4, NULL, 1, 38, '9990003002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'sonal.patel');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'rahul.chauhan', 'Rahul Chauhan', 'rahul.chauhan@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 4, NULL, 1, 38, '9990003003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'rahul.chauhan');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'karan.mehta', 'Karan Mehta', 'karan.mehta@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 5, NULL, 1, 38, '9990004001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'karan.mehta');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'neha.iyer', 'Neha Iyer', 'neha.iyer@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 5, NULL, 1, 38, '9990004002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'neha.iyer');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'jay.solanki', 'Jay Solanki', 'jay.solanki@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 5, NULL, 1, 38, '9990004003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'jay.solanki');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'ritu.verma', 'Ritu Verma', 'ritu.verma@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 6, NULL, 1, 38, '9990005001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'ritu.verma');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'aman.sinha', 'Aman Sinha', 'aman.sinha@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 6, NULL, 1, 38, '9990005002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'aman.sinha');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'pooja.nair', 'Pooja Nair', 'pooja.nair@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 6, NULL, 1, 38, '9990005003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'pooja.nair');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'dinesh.parmar', 'Dinesh Parmar', 'dinesh.parmar@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 7, NULL, 1, 38, '9990006001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'dinesh.parmar');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'hetal.joshi', 'Hetal Joshi', 'hetal.joshi@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 7, NULL, 1, 38, '9990006002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'hetal.joshi');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'yash.patel', 'Yash Patel', 'yash.patel@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 7, NULL, 1, 38, '9990006003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'yash.patel');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'bhavesh.gandhi', 'Bhavesh Gandhi', 'bhavesh.gandhi@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 2, 8, NULL, 1, 38, '9990007001', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'bhavesh.gandhi');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'mitali.shah', 'Mitali Shah', 'mitali.shah@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 8, NULL, 1, 38, '9990007002', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'mitali.shah');

INSERT INTO users (user_name, name, email, password, role_id, department_id, reports_to, is_registered, created_by, phone, company_name, status)
SELECT 'dhruv.thakkar', 'Dhruv Thakkar', 'dhruv.thakkar@trs.local', '$2y$10$/ine7kMF49bFUL1CQBJJO.1BxrQlevwk97BrZLbiqb8sSRwZThoI2', 1, 8, NULL, 1, 38, '9990007003', 'TRS', 'Active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_name = 'dhruv.thakkar');

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'rakesh.shah' LIMIT 1)
WHERE department_id = 1;

UPDATE departments
SET department_head_id = 38
WHERE department_id = 2;

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'anjali.desai' LIMIT 1)
WHERE department_id = 3;

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'vivek.sharma' LIMIT 1)
WHERE department_id = 4;

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'karan.mehta' LIMIT 1)
WHERE department_id = 5;

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'ritu.verma' LIMIT 1)
WHERE department_id = 6;

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'dinesh.parmar' LIMIT 1)
WHERE department_id = 7;

UPDATE departments
SET department_head_id = (SELECT user_id FROM users WHERE user_name = 'bhavesh.gandhi' LIMIT 1)
WHERE department_id = 8;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'rakesh.shah' LIMIT 1)
WHERE department_id = 1 AND role_id = 1;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'anjali.desai' LIMIT 1)
WHERE department_id = 3 AND role_id = 1;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'vivek.sharma' LIMIT 1)
WHERE department_id = 4 AND role_id = 1;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'karan.mehta' LIMIT 1)
WHERE department_id = 5 AND role_id = 1;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'ritu.verma' LIMIT 1)
WHERE department_id = 6 AND role_id = 1;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'dinesh.parmar' LIMIT 1)
WHERE department_id = 7 AND role_id = 1;

UPDATE users
SET reports_to = (SELECT user_id FROM users WHERE user_name = 'bhavesh.gandhi' LIMIT 1)
WHERE department_id = 8 AND role_id = 1;
