CREATE TABLE users (
	id integer primary key asc NOT NULL,
	username TEXT NOT NULL,
	digesta1 TEXT NOT NULL,
	UNIQUE(username)
);

INSERT INTO users (username,digesta1) VALUES
('admin',  '87fd274b7b6c01e48d7c2f965da8ddf7');
