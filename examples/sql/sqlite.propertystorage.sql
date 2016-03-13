CREATE TABLE propertystorage (
    id integer primary key asc NOT NULL,
    path text NOT NULL,
    name text NOT NULL,
    valuetype integer NOT NULL,
    value string
);


CREATE UNIQUE INDEX path_property ON propertystorage (path, name);
