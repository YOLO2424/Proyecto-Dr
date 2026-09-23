-- 006: Historia clinica: encuentros, diagnosticos, tratamientos, notas y versionado.
CREATE TABLE IF NOT EXISTS clinical_encounters (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id    TEXT NOT NULL REFERENCES patients(patient_id),
    encounter_date TEXT NOT NULL,
    reason        TEXT,
    subjective    TEXT,
    objective     TEXT,
    plan          TEXT,
    recorded_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at    TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS diagnoses (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    encounter_id INTEGER NOT NULL REFERENCES clinical_encounters(id) ON DELETE CASCADE,
    patient_id   TEXT NOT NULL REFERENCES patients(patient_id),
    code         TEXT,
    description  TEXT NOT NULL,
    status       TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'RESOLVED', 'CHRONIC')),
    notes        TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS treatments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    encounter_id INTEGER NOT NULL REFERENCES clinical_encounters(id) ON DELETE CASCADE,
    patient_id   TEXT NOT NULL REFERENCES patients(patient_id),
    description  TEXT NOT NULL,
    dose         TEXT,
    frequency    TEXT,
    duration     TEXT,
    notes        TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS clinical_notes (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    encounter_id INTEGER REFERENCES clinical_encounters(id) ON DELETE CASCADE,
    patient_id   TEXT NOT NULL REFERENCES patients(patient_id),
    note_type    TEXT NOT NULL DEFAULT 'OBSERVACION' CHECK (note_type IN ('OBSERVACION', 'INFORME', 'RECETA', 'OTRO')),
    content      TEXT NOT NULL,
    created_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at   TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- Snapshot del estado anterior cuando se modifica un registro clinico.
CREATE TABLE IF NOT EXISTS clinical_record_versions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id   INTEGER NOT NULL,
    version     INTEGER NOT NULL,
    data_json   TEXT NOT NULL,
    reason      TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_encounters_patient ON clinical_encounters(patient_id, encounter_date);
CREATE INDEX IF NOT EXISTS idx_diag_encounter ON diagnoses(encounter_id);
CREATE INDEX IF NOT EXISTS idx_treat_encounter ON treatments(encounter_id);
CREATE INDEX IF NOT EXISTS idx_notes_encounter ON clinical_notes(encounter_id);
CREATE INDEX IF NOT EXISTS idx_versions_entity ON clinical_record_versions(entity_type, entity_id);