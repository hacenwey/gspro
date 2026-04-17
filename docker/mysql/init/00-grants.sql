-- MySQL's official image already created the user from MYSQL_USER / MYSQL_PASSWORD.
-- We only need to extend its privileges so the app can:
--   - create new tenant databases (Tenant::provision)
--   - use any gestionpro_* database it creates later
GRANT ALL PRIVILEGES ON `gestionpro\_%`.* TO 'gestion'@'%';
GRANT ALL PRIVILEGES ON `gestion\_commerciale`.* TO 'gestion'@'%';
GRANT CREATE, DROP, ALTER, REFERENCES, INDEX ON *.* TO 'gestion'@'%';
FLUSH PRIVILEGES;
