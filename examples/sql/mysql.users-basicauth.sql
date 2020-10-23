CREATE TABLE users (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARBINARY(50),
    password_hash VARBINARY(255),
    UNIQUE(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username,password_hash) VALUES
('admin',  '$2y$10$ovboO1pYLAD0J61zC443X.KfeuE31akctc1PZS4sz7d9Vjzbl1gEi');
