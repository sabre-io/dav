CREATE TABLE addressbooks (
    id integer primary key asc,
    principaluri text,
    displayname text,
    uri text,
    description text,
    synctoken integer
);

CREATE TABLE cards (
    id integer primary key asc,
    addressbookid integer,
    carddata blob,
    uri text,
    lastmodified integer,
    etag text,
    size integer
);

CREATE TABLE addressbookchanges (
    id integer primary key asc,
    uri text,
    synctoken integer,
    addressbookid integer,
    operation integer
);

CREATE INDEX addressbookid_synctoken ON addressbookchanges (addressbookid, synctoken);
