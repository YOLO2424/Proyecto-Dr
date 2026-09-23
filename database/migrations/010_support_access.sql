-- 010: Acceso de Soporte/TI a los backups.
-- El PIN se guarda hasheado (nunca en claro); vacio = proteccion no configurada
-- (los backups quedan bloqueados hasta configurarla).
INSERT OR IGNORE INTO settings (key, value) VALUES
    ('support_pin_hash', ''),
    ('support_pin_updated_at', '');