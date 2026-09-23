-- 004: Informacion clinica basica (separada del historial propiamente dicho).
CREATE TABLE IF NOT EXISTS patient_allergies (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id  TEXT NOT NULL REFERENCES patients(patient_id),
    allergen    TEXT NOT NULL,
    reaction    TEXT,
    severity    TEXT CHECK (severity IN ('LEVE', 'MODERADA', 'SEVERA') OR severity IS NULL),
    notes       TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS patient_conditions (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id     TEXT NOT NULL REFERENCES patients(patient_id),
    condition_name TEXT NOT NULL,
    category       TEXT CHECK (category IN ('CONOCIDA', 'ANTECEDENTE', 'CIRUGIA') OR category IS NULL),
    diagnosis_date TEXT,
    notes          TEXT,
    created_at     TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS patient_medications (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id  TEXT NOT NULL REFERENCES patients(patient_id),
    name        TEXT NOT NULL,
    dose        TEXT,
    frequency   TEXT,
    started_at  TEXT,
    status      TEXT NOT NULL DEFAULT 'CURRENT' CHECK (status IN ('CURRENT', 'DISCONTINUED')),
    notes       TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_allergies_patient ON patient_allergies(patient_id);
CREATE INDEX IF NOT EXISTS idx_conditions_patient ON patient_conditions(patient_id);
CREATE INDEX IF NOT EXISTS idx_medications_patient ON patient_medications(patient_id);