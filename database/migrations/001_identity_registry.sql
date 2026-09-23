-- 001: Registro permanente de identidades.
-- Nunca se reutiliza un patient_id. Las filas no se eliminan fisicamente.
CREATE TABLE IF NOT EXISTS patient_identity_registry (
    sequence_id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id  TEXT NOT NULL UNIQUE,
    status      TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'DELETED')),
    created_at  TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);