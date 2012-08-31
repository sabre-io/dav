CREATE TABLE calendarobjects (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendardata MEDIUMBLOB,
    uri VARCHAR(200),
    calendarid INTEGER UNSIGNED NOT NULL,
    lastmodified INT(11) UNSIGNED,
    etag VARCHAR(32),
    size INT(11) UNSIGNED NOT NULL,
    componenttype VARCHAR(8),
    firstoccurence INT(11) UNSIGNED,
    lastoccurence INT(11) UNSIGNED,
    UNIQUE(calendarid, uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE calendars (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARCHAR(100),
    displayname VARCHAR(100),
    uri VARCHAR(200),
    ctag INTEGER UNSIGNED NOT NULL DEFAULT '0',
    description TEXT,
    calendarorder INTEGER UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARCHAR(10),
    timezone TEXT,
    components VARCHAR(20),
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    UNIQUE(principaluri, uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
