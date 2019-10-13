/* This currently creates Planworld for mysql.
	It does not result in a database with the same options as the Amherst Planworld.
	Most of the tables and rows are created, but data types are simplified,
	only indexes also supported by postgres are created, and storage type is not specified.
	However, this should have no effect on the programmatic use of the database
	(i.e. code that works against this database will also work against Amherst production and a postgres version) */

/* May need to add node definitions and cookies. */

USE mysql;
CREATE USER 'pwmysqluser'@'localhost' IDENTIFIED BY 'planworld';
FLUSH PRIVILEGES;
CREATE DATABASE PLANWORLD_PUBLIC_MYSQL;
FLUSH PRIVILEGES;
USE PLANWORLD_PUBLIC_MYSQL;

CREATE TABLE IF NOT EXISTS archive (
  uid bigint NOT NULL DEFAULT 0,
  posted int NOT NULL DEFAULT 0,
  name nchar(64) NOT NULL DEFAULT '',
  pub nchar(1) NOT NULL DEFAULT 'N',
  views int NOT NULL DEFAULT 0,
  content text NOT NULL,
  PRIMARY KEY (uid,posted)
);

CREATE TABLE IF NOT EXISTS block (
  uid bigint NOT NULL DEFAULT 0,
  b_uid bigint NOT NULL DEFAULT 0,
  added int NOT NULL DEFAULT 0,
  PRIMARY KEY (uid,b_uid)
);

CREATE TABLE IF NOT EXISTS cookies (
  id int NOT NULL DEFAULT 0,
  quote text NOT NULL,
  author nchar(255) NOT NULL DEFAULT '',
  s_uid bigint NOT NULL DEFAULT 0,
  approved nchar(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS cookies_seq (
  id int  NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS drafts (
  uid bigint NOT NULL DEFAULT 0,
  date_saved timestamp NOT NULL,
  content text NOT NULL,
  PRIMARY KEY (uid,date_saved)
);

CREATE TABLE IF NOT EXISTS globalstats (
  totalhits bigint NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS groupid_seq (
  id int  NOT NULL,
  PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS message (
  uid bigint NOT NULL DEFAULT 0,
  to_uid bigint NOT NULL DEFAULT 0,
  last_update int NOT NULL DEFAULT 0,
  message text NOT NULL,
  seen smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (uid,to_uid)
);

CREATE TABLE IF NOT EXISTS news (
  id int UNIQUE NOT NULL DEFAULT 0,
  news text NOT NULL,
  date int NOT NULL DEFAULT 0,
  live nchar(1) NOT NULL DEFAULT 'Z',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS news_seq (
  id int  NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS nodes (
  name nchar(64) NOT NULL DEFAULT '',
  hostname nchar(128) NOT NULL DEFAULT '',
  path nchar(128) NOT NULL DEFAULT '',
  port int NOT NULL DEFAULT 80,
  version smallint NOT NULL DEFAULT 2,
  PRIMARY KEY (name)
);

CREATE TABLE IF NOT EXISTS online (
  uid bigint NOT NULL DEFAULT 0,
  login int NOT NULL DEFAULT 0,
  last_access int NOT NULL DEFAULT 0,
  what nchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (uid)
);

CREATE TABLE IF NOT EXISTS plans (
  id int  UNIQUE NOT NULL AUTO_INCREMENT,
  uid bigint NOT NULL DEFAULT 0,
  content text NOT NULL,
  PRIMARY KEY (uid)
);

CREATE TABLE IF NOT EXISTS planwatch (
  uid bigint NOT NULL DEFAULT 0,
  w_uid bigint NOT NULL DEFAULT 0,
  gid bigint NOT NULL DEFAULT 1,
  last_view int NOT NULL DEFAULT 0,
  PRIMARY KEY (uid,w_uid)
);

CREATE TABLE IF NOT EXISTS preferences (
  uid bigint NOT NULL DEFAULT 0,
  name nchar(255) NOT NULL DEFAULT '',
  value nchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (uid,name)
);

CREATE TABLE IF NOT EXISTS pw_groups (
  gid bigint NOT NULL DEFAULT 0,
  uid bigint NOT NULL DEFAULT 0,
  name nchar(64) NOT NULL DEFAULT '',
  pos smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (gid,uid)
);

CREATE TABLE IF NOT EXISTS security (
  ip nchar(15) NOT NULL,
  errorcount int NOT NULL DEFAULT 0,
  lasterror int NOT NULL DEFAULT 0,
  PRIMARY KEY (ip)
);

CREATE TABLE IF NOT EXISTS send (
  uid int  NOT NULL DEFAULT 0,
  to_uid int  NOT NULL DEFAULT 0,
  sent int  NOT NULL DEFAULT 0,
  seen int  NOT NULL DEFAULT 0,
  message text,
  PRIMARY KEY (uid,to_uid,sent)
);

CREATE TABLE IF NOT EXISTS snitch (
  uid bigint NOT NULL DEFAULT 0,
  s_uid bigint NOT NULL DEFAULT 0,
  last_view int NOT NULL DEFAULT 0,
  views int NOT NULL DEFAULT 0,
  PRIMARY KEY (uid,s_uid)
);

CREATE TABLE IF NOT EXISTS snoop (
  uid bigint NOT NULL DEFAULT 0,
  s_uid bigint NOT NULL DEFAULT 0,
  referenced bigint NOT NULL DEFAULT 0,
  PRIMARY KEY (uid,s_uid)
);


CREATE TABLE IF NOT EXISTS timezones (
  name nchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (name)
);

CREATE TABLE IF NOT EXISTS tokens (
  token nchar(64) NOT NULL,
  uid bigint NOT NULL,
  expire int NOT NULL,
  clientname nchar(128) NOT NULL DEFAULT 'default'
);

CREATE TABLE IF NOT EXISTS userid_seq (
  id int  NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS users (
  id int  NOT NULL,
  username nchar(128) UNIQUE DEFAULT NULL,
  remote nchar(1) NOT NULL DEFAULT 'N',
  world  nchar(1) NOT NULL DEFAULT 'Y',
  snitch  nchar(1) NOT NULL DEFAULT 'N',
  snitch_views smallint NOT NULL DEFAULT 0,
  archive nchar(1) NOT NULL DEFAULT 'P',
  archive_size int  NOT NULL DEFAULT 0,
  archive_size_pub int  NOT NULL DEFAULT 0,
  views int NOT NULL DEFAULT 0,
  watch_order nchar(6) NOT NULL DEFAULT 'alph',
  theme_id smallint NOT NULL DEFAULT 1,
  snitch_activated int NOT NULL DEFAULT 0,
  last_login int NOT NULL DEFAULT 0,
  last_update int NOT NULL DEFAULT 0,
  last_ip nchar(15) DEFAULT NULL,
  first_login int DEFAULT NULL,
  PRIMARY KEY (id)
);


/* Now populate initial values. */

insert into userid_seq (id) values (0);
insert into groupid_seq (id) values (0);
insert into news_seq (id) values (0);
insert into cookies_seq (id) values (0);




insert into timezones (name) values ('Europe/Andorra');
insert into timezones (name) values ('Asia/Dubai');
insert into timezones (name) values ('Asia/Kabul');
insert into timezones (name) values ('America/Antigua');
insert into timezones (name) values ('America/Anguilla');
insert into timezones (name) values ('Europe/Tirane');
insert into timezones (name) values ('Asia/Yerevan');
insert into timezones (name) values ('America/Curacao');
insert into timezones (name) values ('Africa/Luanda');
insert into timezones (name) values ('Antarctica/McMurdo');
insert into timezones (name) values ('Antarctica/South_Pole');
insert into timezones (name) values ('Antarctica/Palmer');
insert into timezones (name) values ('Antarctica/Mawson');
insert into timezones (name) values ('Antarctica/Davis');
insert into timezones (name) values ('Antarctica/Casey');
insert into timezones (name) values ('Antarctica/Vostok');
insert into timezones (name) values ('Antarctica/DumontDUrville');
insert into timezones (name) values ('Antarctica/Syowa');
insert into timezones (name) values ('America/Buenos_Aires');
insert into timezones (name) values ('America/Rosario');
insert into timezones (name) values ('America/Cordoba');
insert into timezones (name) values ('America/Jujuy');
insert into timezones (name) values ('America/Catamarca');
insert into timezones (name) values ('America/Mendoz');
insert into timezones (name) values ('Pacific/Pago_Pago');
insert into timezones (name) values ('Europe/Vienna');
insert into timezones (name) values ('Australia/Lord_Howe');
insert into timezones (name) values ('Australia/Hobart');
insert into timezones (name) values ('Australia/Melbourne');
insert into timezones (name) values ('Australia/Sydney');
insert into timezones (name) values ('Australia/Broken_Hill');
insert into timezones (name) values ('Australia/Brisbane');
insert into timezones (name) values ('Australia/Lindeman');
insert into timezones (name) values ('Australia/Adelaide');
insert into timezones (name) values ('Australia/Darwin');
insert into timezones (name) values ('Australia/Perth');
insert into timezones (name) values ('America/Aruba');
insert into timezones (name) values ('Asia/Baku');
insert into timezones (name) values ('Europe/Sarajevo');
insert into timezones (name) values ('America/Barbados');
insert into timezones (name) values ('Asia/Dhaka');
insert into timezones (name) values ('Europe/Brussels');
insert into timezones (name) values ('Africa/Ouagadougou');
insert into timezones (name) values ('Europe/Sofia');
insert into timezones (name) values ('Asia/Bahrain');
insert into timezones (name) values ('Africa/Bujumbura');
insert into timezones (name) values ('Africa/Porto-Novo');
insert into timezones (name) values ('Atlantic/Bermuda');
insert into timezones (name) values ('Asia/Brunei');
insert into timezones (name) values ('America/La_Paz');
insert into timezones (name) values ('America/Noronha');
insert into timezones (name) values ('America/Belem');
insert into timezones (name) values ('America/Fortaleza');
insert into timezones (name) values ('America/Recife');
insert into timezones (name) values ('America/Araguaina');
insert into timezones (name) values ('America/Maceio');
insert into timezones (name) values ('America/Sao_Paulo');
insert into timezones (name) values ('America/Cuiaba');
insert into timezones (name) values ('America/Porto_Velho');
insert into timezones (name) values ('America/Boa_Vista');
insert into timezones (name) values ('America/Manaus');
insert into timezones (name) values ('America/Eirunepe');
insert into timezones (name) values ('America/Rio_Branco');
insert into timezones (name) values ('America/Nassau');
insert into timezones (name) values ('Asia/Thimphu');
insert into timezones (name) values ('Africa/Gaborone');
insert into timezones (name) values ('Europe/Minsk');
insert into timezones (name) values ('America/Belize');
insert into timezones (name) values ('America/St_Johns');
insert into timezones (name) values ('America/Halifax');
insert into timezones (name) values ('America/Glace_Bay');
insert into timezones (name) values ('America/Goose_Bay');
insert into timezones (name) values ('America/Montreal');
insert into timezones (name) values ('America/Nipigon');
insert into timezones (name) values ('America/Thunder_Bay');
insert into timezones (name) values ('America/Pangnirtung');
insert into timezones (name) values ('America/Iqaluit');
insert into timezones (name) values ('America/Rankin_Inlet');
insert into timezones (name) values ('America/Winnipeg');
insert into timezones (name) values ('America/Rainy_River');
insert into timezones (name) values ('America/Cambridge_Bay');
insert into timezones (name) values ('America/Regina');
insert into timezones (name) values ('America/Swift_Current');
insert into timezones (name) values ('America/Edmonton');
insert into timezones (name) values ('America/Yellowknife');
insert into timezones (name) values ('America/Inuvik');
insert into timezones (name) values ('America/Dawson_Creek');
insert into timezones (name) values ('America/Vancouver');
insert into timezones (name) values ('America/Whitehorse');
insert into timezones (name) values ('America/Dawson');
insert into timezones (name) values ('Indian/Cocos');
insert into timezones (name) values ('Africa/Kinshasa');
insert into timezones (name) values ('Africa/Lubumbashi');
insert into timezones (name) values ('Africa/Bangui');
insert into timezones (name) values ('Africa/Brazzaville');
insert into timezones (name) values ('Europe/Zurich');
insert into timezones (name) values ('Africa/Abidjan');
insert into timezones (name) values ('Pacific/Rarotonga');
insert into timezones (name) values ('America/Santiago');
insert into timezones (name) values ('Pacific/Easter');
insert into timezones (name) values ('Africa/Douala');
insert into timezones (name) values ('Asia/Harbin');
insert into timezones (name) values ('Asia/Shanghai');
insert into timezones (name) values ('Asia/Chungking');
insert into timezones (name) values ('Asia/Urumqi');
insert into timezones (name) values ('Asia/Kashgar');
insert into timezones (name) values ('America/Bogota');
insert into timezones (name) values ('America/Costa_Rica');
insert into timezones (name) values ('America/Havana');
insert into timezones (name) values ('Atlantic/Cape_Verde');
insert into timezones (name) values ('Indian/Christmas');
insert into timezones (name) values ('Asia/Nicosia');
insert into timezones (name) values ('Europe/Prague');
insert into timezones (name) values ('Europe/Berlin');
insert into timezones (name) values ('Africa/Djibouti');
insert into timezones (name) values ('Europe/Copenhagen');
insert into timezones (name) values ('America/Dominica');
insert into timezones (name) values ('America/Santo_Domingo');
insert into timezones (name) values ('Africa/Algiers');
insert into timezones (name) values ('America/Guayaquil');
insert into timezones (name) values ('Pacific/Galapagos');
insert into timezones (name) values ('Europe/Tallinn');
insert into timezones (name) values ('Africa/Cairo');
insert into timezones (name) values ('Africa/El_Aaiun');
insert into timezones (name) values ('Africa/Asmera');
insert into timezones (name) values ('Europe/Madrid');
insert into timezones (name) values ('Africa/Ceuta');
insert into timezones (name) values ('Atlantic/Canary');
insert into timezones (name) values ('Africa/Addis_Ababa');
insert into timezones (name) values ('Europe/Helsinki');
insert into timezones (name) values ('Pacific/Fiji');
insert into timezones (name) values ('Atlantic/Stanley');
insert into timezones (name) values ('Pacific/Yap');
insert into timezones (name) values ('Pacific/Truk');
insert into timezones (name) values ('Pacific/Ponape');
insert into timezones (name) values ('Pacific/Kosrae');
insert into timezones (name) values ('Atlantic/Faeroe');
insert into timezones (name) values ('Europe/Paris');
insert into timezones (name) values ('Africa/Libreville');
insert into timezones (name) values ('Europe/London');
insert into timezones (name) values ('Europe/Belfast');
insert into timezones (name) values ('America/Grenada');
insert into timezones (name) values ('Asia/Tbilisi');
insert into timezones (name) values ('America/Cayenne');
insert into timezones (name) values ('Africa/Accra');
insert into timezones (name) values ('Europe/Gibraltar');
insert into timezones (name) values ('America/Scoresbysund');
insert into timezones (name) values ('America/Godthab');
insert into timezones (name) values ('America/Thule');
insert into timezones (name) values ('Africa/Banjul');
insert into timezones (name) values ('Africa/Conakry');
insert into timezones (name) values ('America/Guadeloupe');
insert into timezones (name) values ('Africa/Malabo');
insert into timezones (name) values ('Europe/Athens');
insert into timezones (name) values ('Atlantic/South_Georgia');
insert into timezones (name) values ('America/Guatemala');
insert into timezones (name) values ('Pacific/Guam');
insert into timezones (name) values ('Africa/Bissau');
insert into timezones (name) values ('America/Guyana');
insert into timezones (name) values ('Asia/Hong_Kong');
insert into timezones (name) values ('America/Tegucigalpa');
insert into timezones (name) values ('Europe/Zagreb');
insert into timezones (name) values ('America/Port-au-Prince');
insert into timezones (name) values ('Europe/Budapest');
insert into timezones (name) values ('Asia/Jakarta');
insert into timezones (name) values ('Asia/Ujung_Pandang');
insert into timezones (name) values ('Asia/Jayapura');
insert into timezones (name) values ('Europe/Dublin');
insert into timezones (name) values ('Asia/Jerusalem');
insert into timezones (name) values ('Asia/Calcutta');
insert into timezones (name) values ('Indian/Chagos');
insert into timezones (name) values ('Asia/Baghdad');
insert into timezones (name) values ('Asia/Tehran');
insert into timezones (name) values ('Atlantic/Reykjavik');
insert into timezones (name) values ('Europe/Rome');
insert into timezones (name) values ('America/Jamaica');
insert into timezones (name) values ('Asia/Amman');
insert into timezones (name) values ('Asia/Tokyo');
insert into timezones (name) values ('Africa/Nairobi');
insert into timezones (name) values ('Asia/Bishkek');
insert into timezones (name) values ('Asia/Phnom_Penh');
insert into timezones (name) values ('Pacific/Tarawa');
insert into timezones (name) values ('Pacific/Enderbury');
insert into timezones (name) values ('Pacific/Kiritimati');
insert into timezones (name) values ('Indian/Comoro');
insert into timezones (name) values ('America/St_Kitts');
insert into timezones (name) values ('Asia/Pyongyang');
insert into timezones (name) values ('Asia/Seoul');
insert into timezones (name) values ('Asia/Kuwait');
insert into timezones (name) values ('America/Cayman');
insert into timezones (name) values ('Asia/Almaty');
insert into timezones (name) values ('Asia/Aqtobe');
insert into timezones (name) values ('Asia/Aqtau');
insert into timezones (name) values ('Asia/Vientiane');
insert into timezones (name) values ('Asia/Beirut');
insert into timezones (name) values ('America/St_Lucia');
insert into timezones (name) values ('Europe/Vaduz');
insert into timezones (name) values ('Asia/Colombo');
insert into timezones (name) values ('Africa/Monrovia');
insert into timezones (name) values ('Africa/Maseru');
insert into timezones (name) values ('Europe/Vilnius');
insert into timezones (name) values ('Europe/Luxembourg');
insert into timezones (name) values ('Europe/Riga');
insert into timezones (name) values ('Africa/Tripoli');
insert into timezones (name) values ('Africa/Casablanca');
insert into timezones (name) values ('Europe/Monaco');
insert into timezones (name) values ('Europe/Chisinau');
insert into timezones (name) values ('Indian/Antananarivo');
insert into timezones (name) values ('Pacific/Majuro');
insert into timezones (name) values ('Pacific/Kwajalein');
insert into timezones (name) values ('Europe/Skopje');
insert into timezones (name) values ('Africa/Bamako');
insert into timezones (name) values ('Africa/Timbuktu');
insert into timezones (name) values ('Asia/Rangoon');
insert into timezones (name) values ('Asia/Ulaanbaatar');
insert into timezones (name) values ('Asia/Hovd');
insert into timezones (name) values ('Asia/Macao');
insert into timezones (name) values ('Pacific/Saipan');
insert into timezones (name) values ('America/Martinique');
insert into timezones (name) values ('Africa/Nouakchott');
insert into timezones (name) values ('America/Montserrat');
insert into timezones (name) values ('Europe/Malta');
insert into timezones (name) values ('Indian/Mauritius');
insert into timezones (name) values ('Indian/Maldives');
insert into timezones (name) values ('Africa/Blantyre');
insert into timezones (name) values ('America/Mexico_City');
insert into timezones (name) values ('America/Cancun');
insert into timezones (name) values ('America/Merida');
insert into timezones (name) values ('America/Monterrey');
insert into timezones (name) values ('America/Mazatlan');
insert into timezones (name) values ('America/Chihuahua');
insert into timezones (name) values ('America/Hermosillo');
insert into timezones (name) values ('America/Tijuana');
insert into timezones (name) values ('Asia/Kuala_Lumpur');
insert into timezones (name) values ('Asia/Kuching');
insert into timezones (name) values ('Africa/Maputo');
insert into timezones (name) values ('Africa/Windhoek');
insert into timezones (name) values ('Pacific/Noumea');
insert into timezones (name) values ('Africa/Niamey');
insert into timezones (name) values ('Pacific/Norfolk');
insert into timezones (name) values ('Africa/Lagos');
insert into timezones (name) values ('America/Managua');
insert into timezones (name) values ('Europe/Amsterdam');
insert into timezones (name) values ('Europe/Oslo');
insert into timezones (name) values ('Asia/Katmandu');
insert into timezones (name) values ('Pacific/Nauru');
insert into timezones (name) values ('Pacific/Niue');
insert into timezones (name) values ('Pacific/Auckland');
insert into timezones (name) values ('Pacific/Chatham');
insert into timezones (name) values ('Asia/Muscat');
insert into timezones (name) values ('America/Panama');
insert into timezones (name) values ('America/Lima');
insert into timezones (name) values ('Pacific/Tahiti');
insert into timezones (name) values ('Pacific/Marquesas');
insert into timezones (name) values ('Pacific/Gambier');
insert into timezones (name) values ('Pacific/Port_Moresby');
insert into timezones (name) values ('Asia/Manila');
insert into timezones (name) values ('Asia/Karachi');
insert into timezones (name) values ('Europe/Warsaw');
insert into timezones (name) values ('America/Miquelon');
insert into timezones (name) values ('Pacific/Pitcairn');
insert into timezones (name) values ('America/Puerto_Rico');
insert into timezones (name) values ('Asia/Gaza');
insert into timezones (name) values ('Europe/Lisbon');
insert into timezones (name) values ('Atlantic/Madeira');
insert into timezones (name) values ('Atlantic/Azores');
insert into timezones (name) values ('Pacific/Palau');
insert into timezones (name) values ('America/Asuncion');
insert into timezones (name) values ('Asia/Qatar');
insert into timezones (name) values ('Indian/Reunion');
insert into timezones (name) values ('Europe/Bucharest');
insert into timezones (name) values ('Europe/Kaliningrad');
insert into timezones (name) values ('Europe/Moscow');
insert into timezones (name) values ('Europe/Samara');
insert into timezones (name) values ('Asia/Yekaterinburg');
insert into timezones (name) values ('Asia/Omsk');
insert into timezones (name) values ('Asia/Novosibirsk');
insert into timezones (name) values ('Asia/Krasnoyarsk');
insert into timezones (name) values ('Asia/Irkutsk');
insert into timezones (name) values ('Asia/Yakutsk');
insert into timezones (name) values ('Asia/Vladivostok');
insert into timezones (name) values ('Asia/Magadan');
insert into timezones (name) values ('Asia/Kamchatka');
insert into timezones (name) values ('Asia/Anadyr');
insert into timezones (name) values ('Africa/Kigali');
insert into timezones (name) values ('Asia/Riyadh');
insert into timezones (name) values ('Pacific/Guadalcanal');
insert into timezones (name) values ('Indian/Mahe');
insert into timezones (name) values ('Africa/Khartoum');
insert into timezones (name) values ('Europe/Stockholm');
insert into timezones (name) values ('Asia/Singapore');
insert into timezones (name) values ('Atlantic/St_Helena');
insert into timezones (name) values ('Europe/Ljubljana');
insert into timezones (name) values ('Arctic/Longyearbyen');
insert into timezones (name) values ('Atlantic/Jan_Mayen');
insert into timezones (name) values ('Europe/Bratislava');
insert into timezones (name) values ('Africa/Freetown');
insert into timezones (name) values ('Europe/San_Marino');
insert into timezones (name) values ('Africa/Dakar');
insert into timezones (name) values ('Africa/Mogadishu');
insert into timezones (name) values ('America/Paramaribo');
insert into timezones (name) values ('Africa/Sao_Tome');
insert into timezones (name) values ('America/El_Salvador');
insert into timezones (name) values ('Asia/Damascus');
insert into timezones (name) values ('Africa/Mbabane');
insert into timezones (name) values ('America/Grand_Turk');
insert into timezones (name) values ('Africa/Ndjamena');
insert into timezones (name) values ('Indian/Kerguelen');
insert into timezones (name) values ('Africa/Lome');
insert into timezones (name) values ('Asia/Bangkok');
insert into timezones (name) values ('Asia/Dushanbe');
insert into timezones (name) values ('Pacific/Fakaofo');
insert into timezones (name) values ('Asia/Ashgabat');
insert into timezones (name) values ('Africa/Tunis');
insert into timezones (name) values ('Pacific/Tongatapu');
insert into timezones (name) values ('Asia/Dili');
insert into timezones (name) values ('Europe/Istanbul');
insert into timezones (name) values ('America/Port_of_Spain');
insert into timezones (name) values ('Pacific/Funafuti');
insert into timezones (name) values ('Asia/Taipei');
insert into timezones (name) values ('Africa/Dar_es_Salaam');
insert into timezones (name) values ('Europe/Kiev');
insert into timezones (name) values ('Europe/Uzhgorod');
insert into timezones (name) values ('Europe/Zaporozhye');
insert into timezones (name) values ('Europe/Simferopol');
insert into timezones (name) values ('Africa/Kampala');
insert into timezones (name) values ('Pacific/Johnston');
insert into timezones (name) values ('Pacific/Midway');
insert into timezones (name) values ('Pacific/Wake');
insert into timezones (name) values ('America/New_York');
insert into timezones (name) values ('America/Detroit');
insert into timezones (name) values ('America/Louisville');
insert into timezones (name) values ('America/Kentucky/Monticello');
insert into timezones (name) values ('America/Indianapolis');
insert into timezones (name) values ('America/Indiana/Marengo');
insert into timezones (name) values ('America/Indiana/Knox');
insert into timezones (name) values ('America/Indiana/Vevay');
insert into timezones (name) values ('America/Chicago');
insert into timezones (name) values ('America/Menominee');
insert into timezones (name) values ('America/Denver');
insert into timezones (name) values ('America/Boise');
insert into timezones (name) values ('America/Shiprock');
insert into timezones (name) values ('America/Phoenix');
insert into timezones (name) values ('America/Los_Angeles');
insert into timezones (name) values ('America/Anchorage');
insert into timezones (name) values ('America/Juneau');
insert into timezones (name) values ('America/Yakutat');
insert into timezones (name) values ('America/Nome');
insert into timezones (name) values ('America/Adak');
insert into timezones (name) values ('Pacific/Honolulu');
insert into timezones (name) values ('America/Montevideo');
insert into timezones (name) values ('Asia/Samarkand');
insert into timezones (name) values ('Asia/Tashkent');
insert into timezones (name) values ('Europe/Vatican');
insert into timezones (name) values ('America/St_Vincent');
insert into timezones (name) values ('America/Caracas');
insert into timezones (name) values ('America/Tortola');
insert into timezones (name) values ('America/St_Thomas');
insert into timezones (name) values ('Asia/Saigon');
insert into timezones (name) values ('Pacific/Efate');
insert into timezones (name) values ('Pacific/Wallis');
insert into timezones (name) values ('Pacific/Apia');
insert into timezones (name) values ('Asia/Aden');
insert into timezones (name) values ('Indian/Mayotte');
insert into timezones (name) values ('Europe/Belgrade');
insert into timezones (name) values ('Africa/Johannesburg');
insert into timezones (name) values ('Africa/Lusaka');
insert into timezones (name) values ('Africa/Harare');

GRANT ALL PRIVILEGES ON PLANWORLD_PUBLIC_MYSQL.* TO 'pwmysqluser'@'localhost';


