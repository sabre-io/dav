CREATE TABLE 'locks' (
    id integer primary key asc NOT NULL,
    owner text,
    timeout integer,
    created integer,
    token text,
    scope integer,
    depth integer,
    uri text
);
CREATE INDEX idx_uri ON 'locks' (uri);
