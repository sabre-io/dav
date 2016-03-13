CREATE TABLE principals (
    id INTEGER PRIMARY KEY ASC NOT NULL,
    uri TEXT NOT NULL,
    email TEXT,
    displayname TEXT,
    UNIQUE(uri)
);

CREATE TABLE groupmembers (
    id INTEGER PRIMARY KEY ASC NOT NULL,
    principal_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL,
    UNIQUE(principal_id, member_id)
);


INSERT INTO principals (uri,email,displayname) VALUES ('principals/admin', 'admin@example.org','Administrator');
INSERT INTO principals (uri,email,displayname) VALUES ('principals/admin/calendar-proxy-read', null, null);
INSERT INTO principals (uri,email,displayname) VALUES ('principals/admin/calendar-proxy-write', null, null);

