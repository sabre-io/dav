CREATE TABLE calendars (
    id SERIAL NOT NULL,
    principaluri VARCHAR(100),
    displayname VARCHAR(100),
    uri VARCHAR(200),
    ctag INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    calendarorder INTEGER NOT NULL DEFAULT 0,
    calendarcolor VARCHAR(10),
    timezone TEXT,
    components VARCHAR(20),
    transparent SMALLINT NOT NULL DEFAULT '0'
);

ALTER TABLE ONLY calendars
    ADD CONSTRAINT calendars_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX calendars_ukey
    ON calendars USING btree (principaluri, uri);

CREATE TABLE calendarobjects (
    id SERIAL NOT NULL,
    calendarid INTEGER NOT NULL,
    calendardata TEXT,
    uri VARCHAR(200),
    etag VARCHAR(32),
    size INTEGER NOT NULL,
    componenttype VARCHAR(8),
    lastmodified INTEGER,
    firstoccurence INTEGER,
    lastoccurence INTEGER
);

ALTER TABLE ONLY calendarobjects
    ADD CONSTRAINT calendarobjects_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX calendarobjects_ukey
    ON calendarobjects USING btree (calendarid, uri);

ALTER TABLE ONLY calendarobjects
    ADD CONSTRAINT calendarobjects_calendarid_fkey FOREIGN KEY (calendarid) REFERENCES calendars(id)
        ON DELETE CASCADE;
