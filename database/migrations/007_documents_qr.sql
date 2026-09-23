-- 007: Documentos y token QR de paciente.
CREATE TABLE IF NOT EXISTS documents (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id     TEXT NOT NULL REFERENCES patients(patient_id),
    encounter_id   INTEGER REFERENCES clinical_encounters(id),
    original_name  TEXT NOT NULL,
    stored_name    TEXT NOT NULL,
    mime_type      TEXT NOT NULL,
    size           INTEGER NOT NULL,
    category       TEXT,
    description    TEXT,
    sha256         TEXT,
    created_at     TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS patient_qr_tokens (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id TEXT NOT NULL REFERENCES patients(patient_id),
    token      TEXT NOT NULL UNIQUE,
    status     TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'REVOKED')),
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    revoked_at TEXT,
    reason     TEXT
);

CREATE INDEX IF NOT EXISTS idx_documents_patient ON documents(patient_id);
CREATE INDEX IF NOT EXISTS idx_qr_patient ON patient_qr_tokens(patient_id);
CREATE INDEX IF NOT EXISTS idx_qr_token ON patient_qr_tokens(token);