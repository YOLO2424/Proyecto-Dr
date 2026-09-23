-- 003: Bloques administrativos de contacto y seguro.
CREATE TABLE IF NOT EXISTS patient_contacts (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id      TEXT NOT NULL UNIQUE REFERENCES patients(patient_id),
    phone_primary   TEXT,
    phone_secondary TEXT,
    email           TEXT,
    address         TEXT,
    city            TEXT,
    region          TEXT,
    postal_code     TEXT
);

CREATE TABLE IF NOT EXISTS patient_emergency_contacts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id  TEXT NOT NULL REFERENCES patients(patient_id),
    full_name   TEXT,
    relationship TEXT,
    phone       TEXT,
    alt_phone   TEXT
);

CREATE TABLE IF NOT EXISTS patient_insurance (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id          TEXT NOT NULL UNIQUE REFERENCES patients(patient_id),
    provider            TEXT,
    policy_number       TEXT,
    holder_name         TEXT,
    holder_relationship TEXT,
    company             TEXT
);