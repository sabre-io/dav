CREATE TABLE users (
    id SERIAL NOT NULL,
    username VARCHAR(50),
    password_hash VARCHAR(255)
);

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX users_ukey
    ON users USING btree (username);

INSERT INTO users (username,password_hash) VALUES
('admin',  '$2y$10$ovboO1pYLAD0J61zC443X.KfeuE31akctc1PZS4sz7d9Vjzbl1gEi');
