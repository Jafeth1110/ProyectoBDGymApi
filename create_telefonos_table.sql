-- Crear tabla telefonos unificada
CREATE TABLE `telefonos` (
  `idTelefono` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) NOT NULL,
  `telefono` varchar(45) NOT NULL,
  `tipoTel` varchar(20) NOT NULL,
  `idRol` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idTelefono`),
  UNIQUE KEY `telefono_UNIQUE` (`telefono`),
  KEY `idx_idUsuario_idRol` (`idUsuario`, `idRol`),
  KEY `idx_telefono` (`telefono`),
  CONSTRAINT `FK_TELEFONOS_USUARIO` FOREIGN KEY (`idUsuario`) REFERENCES `users` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_TELEFONOS_ROL` FOREIGN KEY (`idRol`) REFERENCES `roles` (`idRol`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrar datos de telefonoadministrador
INSERT INTO `telefonos` (`idUsuario`, `telefono`, `tipoTel`, `idRol`, `created_at`, `updated_at`)
SELECT 
    u.idUsuario,
    ta.telefono,
    ta.tipoTel,
    u.idRol,
    NOW(),
    NOW()
FROM telefonoadministrador ta
JOIN admin a ON ta.idAdmin = a.idAdmin
JOIN users u ON a.idUsuario = u.idUsuario;

-- Migrar datos de telefonocliente
INSERT INTO `telefonos` (`idUsuario`, `telefono`, `tipoTel`, `idRol`, `created_at`, `updated_at`)
SELECT 
    u.idUsuario,
    tc.telefono,
    tc.tipoTel,
    u.idRol,
    NOW(),
    NOW()
FROM telefonocliente tc
JOIN cliente c ON tc.idCliente = c.idCliente
JOIN users u ON c.idUsuario = u.idUsuario;

-- Migrar datos de telefonoentrenador
INSERT INTO `telefonos` (`idUsuario`, `telefono`, `tipoTel`, `idRol`, `created_at`, `updated_at`)
SELECT 
    u.idUsuario,
    te.telefono,
    te.tipoTel,
    u.idRol,
    NOW(),
    NOW()
FROM telefonoentrenador te
JOIN entrenador e ON te.idEntrenador = e.idEntrenador
JOIN users u ON e.idUsuario = u.idUsuario;

-- Verificar datos migrados
SELECT 'Teléfonos migrados:' as mensaje, COUNT(*) as total FROM telefonos;
SELECT 'Por rol:' as mensaje, idRol, COUNT(*) as cantidad FROM telefonos GROUP BY idRol;
