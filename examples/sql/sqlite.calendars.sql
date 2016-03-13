CREATE TABLE calendarobjects (
    id integer primary key asc NOT NULL,
    calendardata blob NOT NULL,
    uri text NOT NULL,
    calendarid integer NOT NULL,
    lastmodified integer NOT NULL,
    etag text NOT NULL,
    size integer NOT NULL,
    componenttype text,
    firstoccurence integer,
    lastoccurence integer,
    uid text
);

CREATE TABLE calendars (
    id integer primary key asc NOT NULL,
    principaluri text NOT NULL,
    displayname text,
    uri text NOT NULL,
    synctoken integer DEFAULT 1 NOT NULL,
    description text,
    calendarorder integer,
    calendarcolor text,
    timezone text,
    components text NOT NULL,
    transparent bool
);

CREATE TABLE calendarchanges (
    id integer primary key asc NOT NULL,
    uri text,
    synctoken integer NOT NULL,
    calendarid integer NOT NULL,
    operation integer NOT NULL
);

CREATE INDEX calendarid_synctoken ON calendarchanges (calendarid, synctoken);

CREATE TABLE calendarsubscriptions (
    id integer primary key asc NOT NULL,
    uri text NOT NULL,
    principaluri text NOT NULL,
    source text NOT NULL,
    displayname text,
    refreshrate text,
    calendarorder integer,
    calendarcolor text,
    striptodos bool,
    stripalarms bool,
    stripattachments bool,
    lastmodified int
);

CREATE TABLE schedulingobjects (
    id integer primary key asc NOT NULL,
    principaluri text NOT NULL,
    calendardata blob,
    uri text NOT NULL,
    lastmodified integer,
    etag text NOT NULL,
    size integer NOT NULL
);

CREATE INDEX principaluri_uri ON calendarsubscriptions (principaluri, uri);
