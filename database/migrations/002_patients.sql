-- 002: Expediente principal del paciente.
CREATE TABLE IF NOT EXISTS patients (
    db_id               INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id          TEXT NOT NULL UNIQUE REFERENCES patient_identity_registry(patient_id),
    first_name          TEXT NOT NULL,
    last_name           TEXT NOT NULL,
    national_id         TEXT,
    birth_date          TEXT,
    gender              TEXT CHECK (gender IN ('M', 'F', 'O') OR gender IS NULL),
    marital_status      TEXT,
    nationality         TEXT,
    place_of_birth      TEXT,

    blood_group         TEXT,
    previous_surgeries  TEXT,
    observations        TEXT,

    status              TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'INACTIVE', 'DELETED')),
    created_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    deleted_at          TEXT
);

CREATE INDEX IF NOT EXISTS idx_patients_name ON patients(last_name, first_name);
CREATE INDEX IF NOT EXISTS idx_patients_status ON patients(status);