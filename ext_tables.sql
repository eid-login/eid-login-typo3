CREATE TABLE tx_eidlogin_domain_model_eid (
	uid int(11) unsigned DEFAULT 0 NOT NULL auto_increment,
	pid int(11) DEFAULT 0 NOT NULL,
	eidvalue varchar(255) DEFAULT '' NOT NULL,
	attributes varchar(255) DEFAULT '' NOT NULL,
	beuid int(11) NULL UNIQUE,
	feuid int(11) NULL UNIQUE,
	PRIMARY KEY (uid)
);

CREATE TABLE tx_eidlogin_domain_model_attribute (
	uid int(11) unsigned DEFAULT 0 NOT NULL auto_increment,
	pid int(11) DEFAULT 0 NOT NULL,
	eid int(11) NULL NOT NULL,
	name TEXT,
	value TEXT,
	PRIMARY KEY (uid)
);

CREATE TABLE tx_eidlogin_domain_model_continuedata (
	uid int(11) unsigned DEFAULT 0 NOT NULL auto_increment,
	pid int(11) DEFAULT 0 NOT NULL,
	reqid varchar(255) DEFAULT '' NOT NULL UNIQUE,
	value TEXT,
	time INT DEFAULT 0 NOT NULL,
	PRIMARY KEY (uid)
);

CREATE TABLE tx_eidlogin_domain_model_responsedata (
	uid int(11) unsigned DEFAULT 0 NOT NULL auto_increment,
	pid int(11) DEFAULT 0 NOT NULL,
	rspid varchar(255) DEFAULT '' NOT NULL UNIQUE,
	value TEXT,
	time INT DEFAULT 0 NOT NULL,
	PRIMARY KEY (uid)
);

CREATE TABLE tx_eidlogin_domain_model_message (
	uid int(11) unsigned DEFAULT 0 NOT NULL auto_increment,
	pid int(11) DEFAULT 0 NOT NULL,
	msgid varchar(255) DEFAULT '' NOT NULL UNIQUE,
	severity int DEFAULT 0 NOT NULL,
	value TEXT,
	time INT DEFAULT 0 NOT NULL,
	PRIMARY KEY (uid)
);

CREATE TABLE fe_users (
	tx_eidlogin_disablepwlogin int UNSIGNED DEFAULT 0 NULL,
);