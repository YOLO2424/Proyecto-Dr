-- 005: Solicitudes de pre-registro externas (nunca crean expediente directamente).
CREATE TABLE IF NOT EXISTS registration_requests (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    token            TEXT NOT NULL UNIQUE,
    status           TEXT NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED')),
    data_json        TEXT NOT NULL,
    created_at       TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    decided_at       TEXT,
    rejection_reason TEXT,
    patient_id       TEXT REFERENCES patients(patient_id)
);

CREATE INDEX IF NOT EXISTS idx_registration_status ON registration_requests(status);