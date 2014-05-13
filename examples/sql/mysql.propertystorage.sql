CREATE TABLE propertystorage (
    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    path VARCHAR(1024) NOT NULL,
    name VARCHAR(100) NOT NULL,
    value MEDIUMTEXT,
);
CREATE UNIQUE INDEX path_property ON propertystorage (path, name);
