CREATE TABLE locks (
    id SERIAL NOT NULL,
    owner VARCHAR(100),
    timeout INTEGER,
    created INTEGER,
    token VARCHAR(100),
    scope SMALLINT,
    depth SMALLINT,
    uri TEXT
);

ALTER TABLE ONLY locks
    ADD CONSTRAINT locks_pkey PRIMARY KEY (id);

CREATE INDEX locks_token_ix
    ON locks USING btree (token);

CREATE INDEX locks_uri_ix
    ON locks USING btree (uri);
