CREATE TABLE addressbooks (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255),
    displayname VARCHAR(255),
    uri VARBINARY(200),
    description TEXT,
    synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1',
    UNIQUE(principaluri(100), uri(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE cards (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    addressbookid INT(11) UNSIGNED NOT NULL,
    carddata MEDIUMBLOB,
    uri VARBINARY(200),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE addressbookchanges (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    addressbookid INT(11) UNSIGNED NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX addressbookid_synctoken (addressbookid, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
