CREATE TABLE principals (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    email VARBINARY(80),
    displayname VARCHAR(80),
    UNIQUE(uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groupmembers (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principal_id INTEGER UNSIGNED NOT NULL,
    member_id INTEGER UNSIGNED NOT NULL,
    UNIQUE(principal_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO principals (uri,email,displayname) VALUES
('principals/admin', 'admin@example.org','Administrator'),
('principals/admin/calendar-proxy-read', null, null),
('principals/admin/calendar-proxy-write', null, null);

