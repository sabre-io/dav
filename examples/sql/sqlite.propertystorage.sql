CREATE TABLE propertystorage (
    id integer primary key asc,
    path text,
    name text,
    valuetype integer,
    value string
);


CREATE UNIQUE INDEX path_property ON propertystorage (path, name);
