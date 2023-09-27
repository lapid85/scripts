DROP VIEW IF EXISTS all_tables;
CREATE VIEW all_tables AS
SELECT
    TABLE_NAME AS 'table_name',
    COLUMN_NAME AS 'field_name',
    COLUMN_TYPE AS 'field_type',
    CHARACTER_MAXIMUM_LENGTH AS 'field_length',
    IS_NULLABLE AS 'is_nullable',
    COLUMN_DEFAULT AS 'default_value',
    COLUMN_COMMENT AS 'field_comment'
FROM
    information_schema.COLUMNS
WHERE
    TABLE_SCHEMA = 'integrated_platforms_v5' AND TABLE_NAME <> 'all_tables';
