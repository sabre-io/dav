CREATE TABLE users (
    id SERIAL NOT NULL,
    username VARCHAR(50),
    digesta1 VARCHAR(32),
    UNIQUE(username)
);

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX users_ukey
    ON users USING btree (username);

INSERT INTO users (username,digesta1) VALUES
('admin',  '87fd274b7b6c01e48d7c2f965da8ddf7');
