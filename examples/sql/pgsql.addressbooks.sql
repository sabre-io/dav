CREATE TABLE addressbooks (
    id SERIAL NOT NULL,
    principaluri VARCHAR(255),
    displayname VARCHAR(255),
    uri VARCHAR(200),
    description TEXT,
    synctoken INTEGER NOT NULL DEFAULT 1
);

ALTER TABLE ONLY addressbooks
    ADD CONSTRAINT addressbooks_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX addressbooks_ukey
    ON addressbooks USING btree (principaluri, uri);

CREATE TABLE cards (
    id SERIAL NOT NULL,
    addressbookid INTEGER NOT NULL,
    carddata BYTEA,
    uri VARCHAR(200),
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL
);

ALTER TABLE ONLY cards
    ADD CONSTRAINT cards_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX cards_ukey
    ON cards USING btree (addressbookid, uri);

CREATE TABLE addressbookchanges (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    synctoken INTEGER NOT NULL,
    addressbookid INTEGER NOT NULL,
    operation SMALLINT NOT NULL
);

ALTER TABLE ONLY addressbookchanges
    ADD CONSTRAINT addressbookchanges_pkey PRIMARY KEY (id);

CREATE INDEX addressbookchanges_addressbookid_synctoken_ix
    ON addressbookchanges USING btree (addressbookid, synctoken);
