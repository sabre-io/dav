CREATE TABLE calendarobjects (
    id integer primary key asc,
    calendardata blob,
    uri text,
    calendarid integer,
    lastmodified integer,
    etag text,
    size integer,
    componenttype text,
    firstoccurence integer,
    lastoccurence integer,
    uid text
);

CREATE TABLE calendars (
    id integer primary key asc,
    principaluri text,
    displayname text,
    uri text,
    synctoken integer,
    description text,
    calendarorder integer,
    calendarcolor text,
    timezone text,
    components text,
    transparent bool
);

CREATE TABLE calendarchanges (
    id integer primary key asc,
    uri text,
    synctoken integer,
    calendarid integer,
    operation integer
);

CREATE INDEX calendarid_synctoken ON calendarchanges (calendarid, synctoken);

CREATE TABLE calendarsubscriptions (
    id integer primary key asc,
    uri text,
    principaluri text,
    source text,
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
    id integer primary key asc,
    principaluri text,
    calendardata blob,
    uri text,
    lastmodified integer,
    etag text,
    size integer
);

CREATE INDEX principaluri_uri ON calendarsubscriptions (principaluri, uri);
