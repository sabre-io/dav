CREATE TABLE propertystorage (
    id integer primary key asc,
    path TEXT,
    name TEXT,
    value TEXT
);


CREATE UNIQUE INDEX path_property ON propertystorage (path, name);
