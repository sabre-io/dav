CREATE TABLE users (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARBINARY(50),
    digesta1 VARBINARY(32),
    UNIQUE(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username,digesta1) VALUES
('admin',  '87fd274b7b6c01e48d7c2f965da8ddf7');
