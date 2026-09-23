-- 009: Configuracion local.
CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT
);

INSERT OR IGNORE INTO settings (key, value) VALUES
    ('clinic_name', 'Consultorio Médico'),
    ('doctor_name', 'Dra. / Dr.'),
    ('backup_retention_days', '30'),
    ('registration_url', '/registro'),
    ('db_created_at', datetime('now', 'localtime'));