DROP VIEW IF EXISTS all_tables;
CREATE VIEW all_tables AS 
SELECT c.relname AS table_name, 
    a.attname AS field_name, 
    FORMAT_TYPE(a.atttypid, a.atttypmod) AS field_type,
    a.attnotnull AS not_null,
    COL_DESCRIPTION(a.attrelid, a.attnum) AS comment, 
    a.atthasdef AS has_default
FROM pg_class AS c, pg_attribute AS a 
WHERE a.attrelid = c.oid AND a.attnum > 0 AND NOT c.relname LIKE 'pg_%' AND NOT c.relname LIKE 'sql_%' AND c.relkind = 'r';
