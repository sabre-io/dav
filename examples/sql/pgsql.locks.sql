CREATE TABLE locks (
    id SERIAL NOT NULL,
    owner VARCHAR(100),
    timeout INTEGER,
    created INTEGER,
    token VARCHAR(100),
    scope smallint,
    depth smallint,
    uri text
);

ALTER TABLE ONLY locks
    ADD CONSTRAINT locks_pkey PRIMARY KEY (id);
