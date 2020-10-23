CREATE TABLE users (
	id integer primary key asc NOT NULL,
	username TEXT NOT NULL,
	password_hash TEXT NOT NULL,
	UNIQUE(username)
);

INSERT INTO users (username,password_hash) VALUES
('admin',  '$2y$10$ovboO1pYLAD0J61zC443X.KfeuE31akctc1PZS4sz7d9Vjzbl1gEi');
