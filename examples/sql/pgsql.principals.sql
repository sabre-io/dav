CREATE TABLE principals (
    id SERIAL NOT NULL,
    uri VARCHAR(100) NOT NULL,
    email VARCHAR(80),
    displayname VARCHAR(80),
    vcardurl VARCHAR(80)
);

ALTER TABLE ONLY principals
    ADD CONSTRAINT principals_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX principals_ukey
    ON principals USING btree (uri);

CREATE TABLE groupmembers (
    id SERIAL NOT NULL,
    principal_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL
);

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX groupmembers_ukey
    ON groupmembers USING btree (principal_id, member_id);

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_principal_id_fkey FOREIGN KEY (principal_id) REFERENCES principals(id)
        ON DELETE CASCADE;

-- Is this correct correct link ... or not?
-- ALTER TABLE ONLY groupmembers
--     ADD CONSTRAINT groupmembers_member_id_id_fkey FOREIGN KEY (member_id) REFERENCES users(id)
--         ON DELETE CASCADE;

INSERT INTO principals (uri,email,displayname) VALUES
('principals/admin', 'admin@example.org','Administrator'),
('principals/admin/calendar-proxy-read', null, null),
('principals/admin/calendar-proxy-write', null, null);

