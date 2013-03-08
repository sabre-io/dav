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
    lastoccurence integer
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
