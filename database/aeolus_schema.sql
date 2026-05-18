-- Nuestra estructura de la base de datos para Aeolus Cloud
-- Motor Usado: MariaDB 

CREATE DATABASE IF NOT EXISTS Aeolus_Cloud;
USE Aeolus_Cloud;

-- Estructura de la tabla `usuario`
CREATE TABLE IF NOT EXISTS usuario (
  id_usuario INT(11) NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL,
  clave VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verificado TINYINT(1) DEFAULT 0,
  token VARCHAR(64) DEFAULT NULL,
  token_expiracion TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY (usuario),
  UNIQUE KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS archivos_compartidos (
    id_compartido INT(11) NOT NULL AUTO_INCREMENT,
    id_propietario INT(11) NOT NULL,
    id_receptor INT(11) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_relativa VARCHAR(255) NOT NULL,
    fecha_compartido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_compartido),
    FOREIGN KEY (id_propietario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_receptor) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;