/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `acciones_hotkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acciones_hotkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acciones_hotkeys_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acuerdos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acuerdos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cliente` bigint unsigned NOT NULL,
  `id_lugar_origen` bigint unsigned DEFAULT NULL,
  `id_lugar_destino` bigint unsigned DEFAULT NULL,
  `id_producto` bigint unsigned DEFAULT NULL,
  `tarifa_ton` decimal(12,2) NOT NULL DEFAULT '0.00',
  `importe` decimal(12,2) NOT NULL DEFAULT '0.00',
  `id_entidad` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acuerdos_cliente_ruta_producto_unique` (`id_cliente`,`id_lugar_origen`,`id_lugar_destino`,`id_producto`),
  KEY `acuerdos_id_cliente_index` (`id_cliente`),
  KEY `acuerdos_activo_index` (`activo`),
  KEY `acuerdos_id_lugar_origen_foreign` (`id_lugar_origen`),
  KEY `acuerdos_id_lugar_destino_foreign` (`id_lugar_destino`),
  KEY `acuerdos_id_producto_foreign` (`id_producto`),
  CONSTRAINT `acuerdos_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `acuerdos_id_lugar_destino_foreign` FOREIGN KEY (`id_lugar_destino`) REFERENCES `lugares` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acuerdos_id_lugar_origen_foreign` FOREIGN KEY (`id_lugar_origen`) REFERENCES `lugares` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acuerdos_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aforos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aforos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_carta_porte` bigint unsigned NOT NULL,
  `id_factura` bigint unsigned DEFAULT NULL,
  `id_prefactura` bigint unsigned DEFAULT NULL,
  `fecha_parte` date NOT NULL,
  `flete_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `flete_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `flete_demora` decimal(12,2) NOT NULL DEFAULT '0.00',
  `otros_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ingreso_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(12,2) NOT NULL DEFAULT '0.00',
  `refactura` tinyint(1) NOT NULL DEFAULT '0',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aforos_id_carta_porte_unique` (`id_carta_porte`),
  KEY `aforos_id_prefactura_foreign` (`id_prefactura`),
  KEY `aforos_id_user_foreign` (`id_user`),
  KEY `aforos_id_factura_index` (`id_factura`),
  KEY `aforos_fecha_parte_index` (`fecha_parte`),
  CONSTRAINT `aforos_id_carta_porte_foreign` FOREIGN KEY (`id_carta_porte`) REFERENCES `giros` (`id`),
  CONSTRAINT `aforos_id_factura_foreign` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id`),
  CONSTRAINT `aforos_id_prefactura_foreign` FOREIGN KEY (`id_prefactura`) REFERENCES `prefacturas` (`id`),
  CONSTRAINT `aforos_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ajustes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ajustes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_giro` bigint unsigned NOT NULL,
  `concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'descuento, recargo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ajustes_id_giro_foreign` (`id_giro`),
  CONSTRAINT `ajustes_id_giro_foreign` FOREIGN KEY (`id_giro`) REFERENCES `giros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alertas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_perfil` bigint unsigned DEFAULT NULL,
  `vencida` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alertas_id_user_foreign` (`id_user`),
  KEY `alertas_id_perfil_foreign` (`id_perfil`),
  CONSTRAINT `alertas_id_perfil_foreign` FOREIGN KEY (`id_perfil`) REFERENCES `perfiles_rh` (`id`),
  CONSTRAINT `alertas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `amortizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `amortizaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tractivo` bigint unsigned NOT NULL,
  `amortizacion_mn` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `amortizaciones_id_tractivo_foreign` (`id_tractivo`),
  CONSTRAINT `amortizaciones_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_area_padre` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `areas_codigo_unique` (`codigo`),
  KEY `areas_id_area_padre_foreign` (`id_area_padre`),
  KEY `areas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `areas_id_area_padre_foreign` FOREIGN KEY (`id_area_padre`) REFERENCES `areas` (`id`),
  CONSTRAINT `areas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `arrastre_tractivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arrastre_tractivo` (
  `id_tractivo` bigint unsigned NOT NULL,
  `id_arrastre` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tractivo`,`id_arrastre`),
  KEY `arrastre_tractivo_id_arrastre_foreign` (`id_arrastre`),
  CONSTRAINT `arrastre_tractivo_id_arrastre_foreign` FOREIGN KEY (`id_arrastre`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `arrastre_tractivo_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `balances_electricos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `balances_electricos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_local` bigint unsigned NOT NULL,
  `id_equipo` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `lectura_inicial` decimal(12,2) DEFAULT NULL,
  `lectura_final` decimal(12,2) DEFAULT NULL,
  `consumo` decimal(12,2) DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `balances_electricos_id_local_foreign` (`id_local`),
  KEY `balances_electricos_id_equipo_foreign` (`id_equipo`),
  CONSTRAINT `balances_electricos_id_equipo_foreign` FOREIGN KEY (`id_equipo`) REFERENCES `equipos_electricos` (`id`),
  CONSTRAINT `balances_electricos_id_local_foreign` FOREIGN KEY (`id_local`) REFERENCES `locales_electricos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `baterias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `baterias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `baterias_folio_unique` (`folio`),
  KEY `baterias_id_tractivo_index` (`id_tractivo`),
  KEY `baterias_estado_index` (`estado`),
  KEY `baterias_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `baterias_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `baterias_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `baterias_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `baterias_movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bateria` bigint unsigned NOT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `tiempo_trabajo` int DEFAULT NULL COMMENT 'días',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `id_destino` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `baterias_movimientos_id_bateria_foreign` (`id_bateria`),
  KEY `baterias_movimientos_id_tractivo_foreign` (`id_tractivo`),
  KEY `baterias_movimientos_id_destino_foreign` (`id_destino`),
  CONSTRAINT `baterias_movimientos_id_bateria_foreign` FOREIGN KEY (`id_bateria`) REFERENCES `baterias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `baterias_movimientos_id_destino_foreign` FOREIGN KEY (`id_destino`) REFERENCES `destinos_agregados` (`id`),
  CONSTRAINT `baterias_movimientos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `accion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tabla` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_registro` bigint unsigned DEFAULT NULL,
  `detalles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_accion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bitacora_user_id_fecha_accion_index` (`user_id`,`fecha_accion`),
  KEY `bitacora_tabla_id_registro_index` (`tabla`,`id_registro`),
  CONSTRAINT `bitacora_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bolsa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bolsa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ci` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexo` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_piel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nivel_educacional` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_civil` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion_defensa` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiene_licencia` tinyint(1) NOT NULL DEFAULT '0',
  `categorias_licencia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `licencia_emision` date DEFAULT NULL,
  `licencia_vencimiento` date DEFAULT NULL,
  `limitaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `chequeo_medico_emision` date DEFAULT NULL,
  `chequeo_medico_vencimiento` date DEFAULT NULL,
  `reubicacion_emision` date DEFAULT NULL,
  `reubicacion_vencimiento` date DEFAULT NULL,
  `psicometrico_emision` date DEFAULT NULL,
  `psicometrico_vencimiento` date DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `direccion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_cargo` bigint unsigned NOT NULL,
  `id_area` bigint unsigned DEFAULT NULL,
  `id_entidad` bigint unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bolsa_ci_unique` (`ci`),
  UNIQUE KEY `bolsa_codigo_unique` (`codigo`),
  KEY `bolsa_ci_index` (`ci`),
  KEY `bolsa_id_cargo_index` (`id_cargo`),
  KEY `bolsa_id_entidad_index` (`id_entidad`),
  KEY `bolsa_id_area_foreign` (`id_area`),
  CONSTRAINT `bolsa_id_area_foreign` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bolsa_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`),
  CONSTRAINT `bolsa_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buques` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buques_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cajas_codigo_unique` (`codigo`),
  KEY `cajas_id_tractivo_index` (`id_tractivo`),
  KEY `cajas_estado_index` (`estado`),
  KEY `cajas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `cajas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cajas_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calificadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificadores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calificadores_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cargos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cargos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `funciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `medios_requeridos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `competencias` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `es_chofer` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `id_calificador` bigint unsigned DEFAULT NULL,
  `id_fondo_tiempo` bigint unsigned DEFAULT NULL,
  `id_nivel_educacion` bigint unsigned DEFAULT NULL,
  `id_grupo_escala` bigint unsigned DEFAULT NULL,
  `id_clasificacion_laboral` bigint unsigned DEFAULT NULL,
  `id_categoria_cargo` bigint unsigned DEFAULT NULL,
  `id_grupo_horario` bigint unsigned DEFAULT NULL,
  `tipo_salario` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Sueldo, 0=Jornal',
  `en_salario` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Dias, 0=Horas',
  `tarifa` decimal(20,12) DEFAULT NULL,
  `salario_escala` decimal(10,2) DEFAULT NULL,
  `cla` decimal(10,4) DEFAULT NULL,
  `noct1` decimal(10,6) DEFAULT NULL,
  `noct2` decimal(10,6) DEFAULT NULL,
  `pago_adicional` decimal(10,5) DEFAULT NULL,
  `aseo_tecnologico` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cargos_codigo_unique` (`codigo`),
  KEY `cargos_id_entidad_foreign` (`id_entidad`),
  KEY `cargos_id_calificador_foreign` (`id_calificador`),
  KEY `cargos_id_fondo_tiempo_foreign` (`id_fondo_tiempo`),
  KEY `cargos_id_nivel_educacion_foreign` (`id_nivel_educacion`),
  KEY `cargos_id_grupo_escala_foreign` (`id_grupo_escala`),
  KEY `cargos_id_clasificacion_laboral_foreign` (`id_clasificacion_laboral`),
  KEY `cargos_id_categoria_cargo_foreign` (`id_categoria_cargo`),
  KEY `cargos_id_grupo_horario_foreign` (`id_grupo_horario`),
  CONSTRAINT `cargos_id_calificador_foreign` FOREIGN KEY (`id_calificador`) REFERENCES `calificadores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_categoria_cargo_foreign` FOREIGN KEY (`id_categoria_cargo`) REFERENCES `categorias_cargo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_clasificacion_laboral_foreign` FOREIGN KEY (`id_clasificacion_laboral`) REFERENCES `tipos_clasificacion_laboral` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_fondo_tiempo_foreign` FOREIGN KEY (`id_fondo_tiempo`) REFERENCES `fondos_tiempo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_grupo_escala_foreign` FOREIGN KEY (`id_grupo_escala`) REFERENCES `grupos_escala` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_grupo_horario_foreign` FOREIGN KEY (`id_grupo_horario`) REFERENCES `tipos_grupo_horario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cargos_id_nivel_educacion_foreign` FOREIGN KEY (`id_nivel_educacion`) REFERENCES `tipos_nivel_educacion` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cartas_porte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cartas_porte` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_hoja_ruta` bigint unsigned NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_lugar_origen` bigint unsigned NOT NULL,
  `id_lugar_destino` bigint unsigned NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_recepcion` date DEFAULT NULL,
  `toneladas` decimal(10,2) NOT NULL,
  `tarifa_km` decimal(10,2) DEFAULT NULL,
  `total_flete` decimal(12,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitida',
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `re Facturacion` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cartas_porte_numero_unique` (`numero`),
  KEY `cartas_porte_id_lugar_origen_foreign` (`id_lugar_origen`),
  KEY `cartas_porte_id_lugar_destino_foreign` (`id_lugar_destino`),
  KEY `cartas_porte_numero_index` (`numero`),
  KEY `cartas_porte_id_hoja_ruta_index` (`id_hoja_ruta`),
  KEY `cartas_porte_id_cliente_index` (`id_cliente`),
  KEY `cartas_porte_estado_index` (`estado`),
  KEY `cartas_porte_fecha_emision_index` (`fecha_emision`),
  CONSTRAINT `cartas_porte_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `cartas_porte_id_hoja_ruta_foreign` FOREIGN KEY (`id_hoja_ruta`) REFERENCES `hojas_ruta` (`id`),
  CONSTRAINT `cartas_porte_id_lugar_destino_foreign` FOREIGN KEY (`id_lugar_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `cartas_porte_id_lugar_origen_foreign` FOREIGN KEY (`id_lugar_origen`) REFERENCES `lugares` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalogo_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalogo_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origen_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `extra` json DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalogo_items_tipo_codigo_unique` (`tipo`,`codigo`),
  UNIQUE KEY `catalogo_items_tipo_origen_unique` (`tipo`,`origen_id`),
  KEY `catalogo_items_tipo_index` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalogo_tipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalogo_tipos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `agrupacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `tabla_legacy` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalogo_tipos_tipo_unique` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias_cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_cargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perfeccionamiento` decimal(8,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categorias_cargo_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categorias_productos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `causas_gps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `causas_gps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `causas_gps_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `causas_multas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `causas_multas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `causas_multas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centros_costos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centros_costos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `centros_costos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `choferes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `choferes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ci` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_empleado` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `choferes_codigo_unique` (`codigo`),
  UNIQUE KEY `choferes_ci_unique` (`ci`),
  KEY `choferes_id_tractivo_foreign` (`id_tractivo`),
  KEY `choferes_id_empleado_foreign` (`id_empleado`),
  CONSTRAINT `choferes_id_empleado_foreign` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id`),
  CONSTRAINT `choferes_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cierres_cdt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cierres_cdt` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `tiempo_general` float DEFAULT NULL,
  `tiempo_taller` float DEFAULT NULL,
  `porcentaje` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clasificaciones_ordenes_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clasificaciones_ordenes_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clasificaciones_ordenes_taller_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nrocontrato` int DEFAULT NULL,
  `falta` date DEFAULT NULL,
  `fvencimiento` date DEFAULT NULL,
  `codreup` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agenciamn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ctamn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idorganismos` bigint unsigned DEFAULT NULL,
  `idosdes` bigint unsigned DEFAULT NULL,
  `idmonedas` bigint unsigned DEFAULT NULL,
  `idclientesel` bigint unsigned DEFAULT NULL,
  `emailfacturacion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelado` int DEFAULT NULL,
  `descuento` int DEFAULT NULL,
  `plan` decimal(15,2) DEFAULT NULL,
  `mora` int DEFAULT NULL,
  `contacto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_codigo_unique` (`codigo`),
  KEY `clientes_codigo_index` (`codigo`),
  KEY `clientes_nombre_index` (`nombre`),
  KEY `clientes_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `clientes_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes_mm` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_mm_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes_seleccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes_seleccion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `colores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colores_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combustible_cargas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combustible_cargas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tarjeta` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_bolsa` bigint unsigned DEFAULT NULL,
  `fecha_carga` date NOT NULL,
  `cantidad_litros` decimal(10,2) NOT NULL,
  `precio_litro` decimal(10,4) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `tipo_combustible` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lugar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrada',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `combustible_cargas_numero_unique` (`numero`),
  KEY `combustible_cargas_id_tarjeta_foreign` (`id_tarjeta`),
  KEY `combustible_cargas_id_tractivo_foreign` (`id_tractivo`),
  KEY `combustible_cargas_id_bolsa_foreign` (`id_bolsa`),
  KEY `combustible_cargas_id_user_foreign` (`id_user`),
  KEY `combustible_cargas_fecha_carga_index` (`fecha_carga`),
  KEY `combustible_cargas_estado_index` (`estado`),
  CONSTRAINT `combustible_cargas_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `combustible_cargas_id_tarjeta_foreign` FOREIGN KEY (`id_tarjeta`) REFERENCES `tarjetas` (`id`),
  CONSTRAINT `combustible_cargas_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `combustible_cargas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combustible_descargas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combustible_descargas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_carga` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned NOT NULL,
  `fecha_descarga` date NOT NULL,
  `cantidad_litros` decimal(10,2) NOT NULL,
  `kilometraje` decimal(10,2) DEFAULT NULL,
  `tipo_combustible` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrada',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `combustible_descargas_id_carga_foreign` (`id_carga`),
  KEY `combustible_descargas_id_tractivo_foreign` (`id_tractivo`),
  KEY `combustible_descargas_id_user_foreign` (`id_user`),
  KEY `combustible_descargas_fecha_descarga_index` (`fecha_descarga`),
  KEY `combustible_descargas_estado_index` (`estado`),
  CONSTRAINT `combustible_descargas_id_carga_foreign` FOREIGN KEY (`id_carga`) REFERENCES `combustible_cargas` (`id`),
  CONSTRAINT `combustible_descargas_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `combustible_descargas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combustibles_lubricantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combustibles_lubricantes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_carga` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned NOT NULL,
  `id_tipo_lubricante` bigint unsigned DEFAULT NULL,
  `id_causa` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `importe_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `combustibles_lubricantes_id_carga_foreign` (`id_carga`),
  KEY `combustibles_lubricantes_id_tipo_lubricante_foreign` (`id_tipo_lubricante`),
  KEY `combustibles_lubricantes_id_causa_foreign` (`id_causa`),
  KEY `combustibles_lubricantes_id_tractivo_index` (`id_tractivo`),
  KEY `combustibles_lubricantes_fecha_index` (`fecha`),
  CONSTRAINT `combustibles_lubricantes_id_carga_foreign` FOREIGN KEY (`id_carga`) REFERENCES `combustible_cargas` (`id`),
  CONSTRAINT `combustibles_lubricantes_id_causa_foreign` FOREIGN KEY (`id_causa`) REFERENCES `tipos_causas` (`id`),
  CONSTRAINT `combustibles_lubricantes_id_tipo_lubricante_foreign` FOREIGN KEY (`id_tipo_lubricante`) REFERENCES `tipos_lubricantes` (`id`),
  CONSTRAINT `combustibles_lubricantes_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competencias_cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competencias_cargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cargo` bigint unsigned NOT NULL,
  `competencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competencias_cargo_id_cargo_foreign` (`id_cargo`),
  CONSTRAINT `competencias_cargo_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conceptos_costos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conceptos_costos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tipo_gasto` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conceptos_costos_codigo_unique` (`codigo`),
  KEY `conceptos_costos_id_tipo_gasto_foreign` (`id_tipo_gasto`),
  CONSTRAINT `conceptos_costos_id_tipo_gasto_foreign` FOREIGN KEY (`id_tipo_gasto`) REFERENCES `tipos_gastos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conciliaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conciliaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_factura` bigint unsigned DEFAULT NULL,
  `fecha_conciliacion` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'bancaria, interna, cliente',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conciliaciones_numero_unique` (`numero`),
  KEY `conciliaciones_id_factura_foreign` (`id_factura`),
  KEY `conciliaciones_id_user_foreign` (`id_user`),
  KEY `conciliaciones_fecha_conciliacion_index` (`fecha_conciliacion`),
  KEY `conciliaciones_estado_index` (`estado`),
  KEY `conciliaciones_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `conciliaciones_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conciliaciones_id_factura_foreign` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id`),
  CONSTRAINT `conciliaciones_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuraciones_modelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones_modelo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_tipo_modelo` bigint unsigned DEFAULT NULL,
  `set_x` int DEFAULT NULL,
  `set_y` int DEFAULT NULL,
  `letra` int DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `configuraciones_modelo_id_tipo_modelo_foreign` (`codigo_tipo_modelo`),
  KEY `configuraciones_modelo_id_user_foreign` (`id_user`),
  KEY `configuraciones_modelo_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `configuraciones_modelo_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `configuraciones_modelo_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuraciones_tarifa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones_tarifa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `demora_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `demora_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_vacio_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_vacio_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tarifa_horaria_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tarifa_horaria_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_adicionales_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_adicionales_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `almacenaje` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_3_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_3_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_3_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recargo_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `hora_1` int NOT NULL DEFAULT '0',
  `hora_2` int NOT NULL DEFAULT '0',
  `hora_3` int NOT NULL DEFAULT '0',
  `izaje_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `izaje_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_izaje_mt` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_izaje_me` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_almacenaje` decimal(10,2) NOT NULL DEFAULT '0.00',
  `plazo_libre_exp` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consecutivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consecutivos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ultimo` int NOT NULL DEFAULT '0',
  `formato` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consecutivos_codigo_id_entidad_unique` (`codigo`,`id_entidad`),
  KEY `consecutivos_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `consecutivos_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consumo_lubricantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consumo_lubricantes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_tipo_aceite` bigint unsigned DEFAULT NULL,
  `id_causa` bigint unsigned DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `unidad` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'litros',
  `importe_mn` decimal(12,2) DEFAULT NULL,
  `importe_me` decimal(12,2) DEFAULT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consumo_lubricantes_folio_unique` (`folio`),
  KEY `consumo_lubricantes_id_tractivo_foreign` (`id_tractivo`),
  KEY `consumo_lubricantes_id_tipo_aceite_foreign` (`id_tipo_aceite`),
  KEY `consumo_lubricantes_id_causa_foreign` (`id_causa`),
  CONSTRAINT `consumo_lubricantes_id_causa_foreign` FOREIGN KEY (`id_causa`) REFERENCES `tipos_causas` (`id`),
  CONSTRAINT `consumo_lubricantes_id_tipo_aceite_foreign` FOREIGN KEY (`id_tipo_aceite`) REFERENCES `tipos_lubricantes` (`id`),
  CONSTRAINT `consumo_lubricantes_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consumo_piezas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consumo_piezas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_concepto` bigint unsigned DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `importe_mn` decimal(12,2) DEFAULT NULL,
  `importe_me` decimal(12,2) DEFAULT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consumo_piezas_folio_unique` (`folio`),
  KEY `consumo_piezas_id_tractivo_foreign` (`id_tractivo`),
  KEY `consumo_piezas_id_concepto_foreign` (`id_concepto`),
  CONSTRAINT `consumo_piezas_id_concepto_foreign` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_costos` (`id`),
  CONSTRAINT `consumo_piezas_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contabilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contabilidad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero_asiento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_asiento` date NOT NULL,
  `tipo_concepto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `debe` decimal(12,2) NOT NULL DEFAULT '0.00',
  `haber` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contabilidad_numero_asiento_unique` (`numero_asiento`),
  KEY `contabilidad_numero_asiento_index` (`numero_asiento`),
  KEY `contabilidad_fecha_asiento_index` (`fecha_asiento`),
  KEY `contabilidad_tipo_concepto_index` (`tipo_concepto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contabilidad_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contabilidad_detalle` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_asiento` bigint unsigned NOT NULL,
  `cuenta_contable` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_cuenta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `debe` decimal(12,2) NOT NULL DEFAULT '0.00',
  `haber` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contabilidad_detalle_id_asiento_index` (`id_asiento`),
  KEY `contabilidad_detalle_cuenta_contable_index` (`cuenta_contable`),
  CONSTRAINT `contabilidad_detalle_id_asiento_foreign` FOREIGN KEY (`id_asiento`) REFERENCES `contabilidad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contenedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contenedores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_carta_porte` bigint unsigned DEFAULT NULL,
  `id_carta_porte_retorno` bigint unsigned DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `fecha_retorno` date DEFAULT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tara` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contenedores_codigo_unique` (`codigo`),
  KEY `contenedores_id_carta_porte_foreign` (`id_carta_porte`),
  KEY `contenedores_id_carta_porte_retorno_foreign` (`id_carta_porte_retorno`),
  CONSTRAINT `contenedores_id_carta_porte_foreign` FOREIGN KEY (`id_carta_porte`) REFERENCES `cartas_porte` (`id`),
  CONSTRAINT `contenedores_id_carta_porte_retorno_foreign` FOREIGN KEY (`id_carta_porte_retorno`) REFERENCES `cartas_porte` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `control_lubricantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_lubricantes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tractivo` bigint unsigned NOT NULL,
  `id_lubricante` bigint unsigned NOT NULL,
  `fecha_cambio` date NOT NULL,
  `cantidad_litros` decimal(8,2) NOT NULL,
  `kilometraje` decimal(12,2) NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_orden_taller` bigint unsigned DEFAULT NULL,
  `confeccionado_por` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `control_lubricantes_id_lubricante_foreign` (`id_lubricante`),
  KEY `control_lubricantes_id_tractivo_index` (`id_tractivo`),
  KEY `control_lubricantes_fecha_cambio_index` (`fecha_cambio`),
  KEY `control_lubricantes_id_orden_taller_foreign` (`id_orden_taller`),
  CONSTRAINT `control_lubricantes_id_lubricante_foreign` FOREIGN KEY (`id_lubricante`) REFERENCES `lubricantes` (`id`),
  CONSTRAINT `control_lubricantes_id_orden_taller_foreign` FOREIGN KEY (`id_orden_taller`) REFERENCES `ordenes_taller` (`id`),
  CONSTRAINT `control_lubricantes_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `costos_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `costos_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tractivo` bigint unsigned NOT NULL,
  `horas_taller` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `costos_taller_id_tractivo_foreign` (`id_tractivo`),
  CONSTRAINT `costos_taller_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `demandas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demandas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha_demanda` date NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_producto` bigint unsigned NOT NULL,
  `id_origen` bigint unsigned NOT NULL,
  `id_destino` bigint unsigned NOT NULL,
  `id_embalaje` bigint unsigned NOT NULL,
  `viajes` int NOT NULL DEFAULT '0',
  `kms_totales` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_carga` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tiempo_demanda` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tiempo_aceptacion` decimal(10,2) NOT NULL DEFAULT '0.00',
  `datos_mensuales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demandas_id_producto_foreign` (`id_producto`),
  KEY `demandas_id_origen_foreign` (`id_origen`),
  KEY `demandas_id_destino_foreign` (`id_destino`),
  KEY `demandas_id_embalaje_foreign` (`id_embalaje`),
  KEY `demandas_id_user_foreign` (`id_user`),
  KEY `demandas_id_cliente_index` (`id_cliente`),
  KEY `demandas_fecha_demanda_index` (`fecha_demanda`),
  CONSTRAINT `demandas_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `demandas_id_destino_foreign` FOREIGN KEY (`id_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `demandas_id_embalaje_foreign` FOREIGN KEY (`id_embalaje`) REFERENCES `embalajes` (`id`),
  CONSTRAINT `demandas_id_origen_foreign` FOREIGN KEY (`id_origen`) REFERENCES `lugares` (`id`),
  CONSTRAINT `demandas_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  CONSTRAINT `demandas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `descuentos_empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `descuentos_empleados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_empleado` bigint unsigned NOT NULL,
  `fecha_inicio` date NOT NULL,
  `tiempo` decimal(8,2) NOT NULL COMMENT 'Horas/minutos descontados',
  `motivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `descuentos_empleados_id_empleado_foreign` (`id_empleado`),
  CONSTRAINT `descuentos_empleados_id_empleado_foreign` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `destinos_agregados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `destinos_agregados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `destinos_agregados_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_movimientos_inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_movimiento` bigint unsigned NOT NULL,
  `id_tarjetero` bigint unsigned NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `precio_mn` decimal(12,2) DEFAULT NULL,
  `precio_me` decimal(12,2) DEFAULT NULL,
  `valor_mn` decimal(12,2) DEFAULT NULL,
  `valor_me` decimal(12,2) DEFAULT NULL,
  `renglon` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalle_movimientos_inventario_id_movimiento_foreign` (`id_movimiento`),
  KEY `detalle_movimientos_inventario_id_tarjetero_foreign` (`id_tarjetero`),
  CONSTRAINT `detalle_movimientos_inventario_id_movimiento_foreign` FOREIGN KEY (`id_movimiento`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_movimientos_inventario_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_prefacturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_prefacturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_prefactura` bigint unsigned NOT NULL,
  `id_moneda` bigint unsigned DEFAULT NULL,
  `id_origen` bigint unsigned DEFAULT NULL,
  `id_destino` bigint unsigned DEFAULT NULL,
  `id_tipo_carga` bigint unsigned DEFAULT NULL,
  `importe` decimal(12,2) DEFAULT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalle_prefacturas_id_prefactura_foreign` (`id_prefactura`),
  KEY `detalle_prefacturas_id_moneda_foreign` (`id_moneda`),
  KEY `detalle_prefacturas_id_origen_foreign` (`id_origen`),
  KEY `detalle_prefacturas_id_destino_foreign` (`id_destino`),
  KEY `detalle_prefacturas_id_tipo_carga_foreign` (`id_tipo_carga`),
  CONSTRAINT `detalle_prefacturas_id_destino_foreign` FOREIGN KEY (`id_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `detalle_prefacturas_id_moneda_foreign` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id`),
  CONSTRAINT `detalle_prefacturas_id_origen_foreign` FOREIGN KEY (`id_origen`) REFERENCES `lugares` (`id`),
  CONSTRAINT `detalle_prefacturas_id_prefactura_foreign` FOREIGN KEY (`id_prefactura`) REFERENCES `prefacturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_prefacturas_id_tipo_carga_foreign` FOREIGN KEY (`id_tipo_carga`) REFERENCES `tipos_cargas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_vales_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_vales_inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_vale` bigint unsigned NOT NULL,
  `id_tarjetero` bigint unsigned NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `precio_mn` decimal(12,2) DEFAULT NULL,
  `precio_me` decimal(12,2) DEFAULT NULL,
  `valor_mn` decimal(12,2) DEFAULT NULL,
  `valor_me` decimal(12,2) DEFAULT NULL,
  `renglon` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalle_vales_inventario_id_vale_foreign` (`id_vale`),
  KEY `detalle_vales_inventario_id_tarjetero_foreign` (`id_tarjetero`),
  CONSTRAINT `detalle_vales_inventario_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`),
  CONSTRAINT `detalle_vales_inventario_id_vale_foreign` FOREIGN KEY (`id_vale`) REFERENCES `vales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalles_carga_combustible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalles_carga_combustible` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_carga` bigint unsigned NOT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_bolsa` bigint unsigned DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `comprobante` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `importe_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `importe_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalles_carga_combustible_id_tractivo_foreign` (`id_tractivo`),
  KEY `detalles_carga_combustible_id_bolsa_foreign` (`id_bolsa`),
  KEY `detalles_carga_combustible_id_carga_index` (`id_carga`),
  KEY `detalles_carga_combustible_fecha_movimiento_index` (`fecha_movimiento`),
  CONSTRAINT `detalles_carga_combustible_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `detalles_carga_combustible_id_carga_foreign` FOREIGN KEY (`id_carga`) REFERENCES `combustible_cargas` (`id`),
  CONSTRAINT `detalles_carga_combustible_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalles_vale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalles_vale` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_vale` bigint unsigned NOT NULL,
  `id_inventario` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `unidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio_unitario` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalles_vale_id_inventario_foreign` (`id_inventario`),
  KEY `detalles_vale_id_vale_index` (`id_vale`),
  CONSTRAINT `detalles_vale_id_inventario_foreign` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`),
  CONSTRAINT `detalles_vale_id_vale_foreign` FOREIGN KEY (`id_vale`) REFERENCES `vales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devoluciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devoluciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_carta_porte` bigint unsigned NOT NULL,
  `id_cliente` bigint unsigned DEFAULT NULL,
  `id_cliente_mm` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_empleado` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `aumento_flete_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `aumento_flete_me` decimal(12,2) NOT NULL DEFAULT '0.00',
  `aumento_demora` decimal(12,2) NOT NULL DEFAULT '0.00',
  `aumento_salario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `aumento_alquiler` decimal(12,2) NOT NULL DEFAULT '0.00',
  `aumento_izaje` decimal(12,2) NOT NULL DEFAULT '0.00',
  `disminucion_flete_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `disminucion_flete_me` decimal(12,2) NOT NULL DEFAULT '0.00',
  `disminucion_demora` decimal(12,2) NOT NULL DEFAULT '0.00',
  `disminucion_salario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `disminucion_alquiler` decimal(12,2) NOT NULL DEFAULT '0.00',
  `disminucion_izaje` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `devoluciones_id_carta_porte_foreign` (`id_carta_porte`),
  KEY `devoluciones_id_cliente_foreign` (`id_cliente`),
  KEY `devoluciones_id_cliente_mm_foreign` (`id_cliente_mm`),
  KEY `devoluciones_id_tractivo_foreign` (`id_tractivo`),
  KEY `devoluciones_id_empleado_foreign` (`id_empleado`),
  CONSTRAINT `devoluciones_id_carta_porte_foreign` FOREIGN KEY (`id_carta_porte`) REFERENCES `cartas_porte` (`id`),
  CONSTRAINT `devoluciones_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `devoluciones_id_cliente_mm_foreign` FOREIGN KEY (`id_cliente_mm`) REFERENCES `clientes_mm` (`id`),
  CONSTRAINT `devoluciones_id_empleado_foreign` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id`),
  CONSTRAINT `devoluciones_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dietas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dietas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bolsa` bigint unsigned NOT NULL,
  `id_hoja_ruta` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tipo_dieta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dietas_id_hoja_ruta_foreign` (`id_hoja_ruta`),
  KEY `dietas_id_bolsa_index` (`id_bolsa`),
  KEY `dietas_fecha_index` (`fecha`),
  CONSTRAINT `dietas_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `dietas_id_hoja_ruta_foreign` FOREIGN KEY (`id_hoja_ruta`) REFERENCES `hojas_ruta` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `diferenciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `diferenciales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `durabilidad` int DEFAULT NULL,
  `relacion` int DEFAULT NULL,
  `ancho` int DEFAULT NULL,
  `cantidad_lubricante` int DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `kms_acumulados` int DEFAULT NULL,
  `capacidad_carter` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `diferenciales_codigo_unique` (`codigo`),
  KEY `diferenciales_id_tractivo_index` (`id_tractivo`),
  KEY `diferenciales_estado_index` (`estado`),
  KEY `diferenciales_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `diferenciales_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `diferenciales_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `distancias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `distancias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_lugar_origen` bigint unsigned NOT NULL,
  `id_lugar_destino` bigint unsigned NOT NULL,
  `distancia_km` decimal(10,2) NOT NULL,
  `tiempo_horas` decimal(8,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `distancias_id_lugar_origen_id_lugar_destino_unique` (`id_lugar_origen`,`id_lugar_destino`),
  KEY `distancias_id_lugar_destino_foreign` (`id_lugar_destino`),
  CONSTRAINT `distancias_id_lugar_destino_foreign` FOREIGN KEY (`id_lugar_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `distancias_id_lugar_origen_foreign` FOREIGN KEY (`id_lugar_origen`) REFERENCES `lugares` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `elementos_gasto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elementos_gasto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subelemento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `elementos_gasto_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `embalajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `embalajes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `embalajes_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expediente` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_area` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleados_codigo_unique` (`codigo`),
  UNIQUE KEY `empleados_expediente_unique` (`expediente`),
  KEY `empleados_id_area_foreign` (`id_area`),
  CONSTRAINT `empleados_id_area_foreign` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entidad_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entidad_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `entidad_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entidad_user_user_id_entidad_id_unique` (`user_id`,`entidad_id`),
  KEY `entidad_user_entidad_id_foreign` (`entidad_id`),
  CONSTRAINT `entidad_user_entidad_id_foreign` FOREIGN KEY (`entidad_id`) REFERENCES `entidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entidad_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entidades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `es_matriz` tinyint(1) NOT NULL DEFAULT '0',
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_area` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_provincia` bigint unsigned DEFAULT NULL,
  `id_municipio` bigint unsigned DEFAULT NULL,
  `email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nit` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `licencia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `licencia_vencimiento` date DEFAULT NULL,
  `licencia_activa` tinyint(1) NOT NULL DEFAULT '1',
  `cta_unica` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_mn` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_me` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agencia` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minutos` int unsigned DEFAULT NULL,
  `folio_fact` int unsigned DEFAULT NULL,
  `almacenaje` decimal(6,4) DEFAULT NULL,
  `interruptos` int DEFAULT NULL,
  `lugares` int DEFAULT NULL,
  `pass_dias` int NOT NULL DEFAULT '120',
  `pass_cant_h` int NOT NULL DEFAULT '2',
  `notas_fact` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `mora_dias` int DEFAULT NULL,
  `mora_porciento` int DEFAULT NULL,
  `cliente_fincimex_mn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `talon_versat` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vida_bateria` int DEFAULT NULL,
  `vida_neum_nuevo` int DEFAULT NULL,
  `vida_neum_rec` int DEFAULT NULL,
  `vida_neum_admin` int DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT '0',
  `desactivar_disp` tinyint(1) NOT NULL DEFAULT '0',
  `alertas_mtto` tinyint(1) NOT NULL DEFAULT '0',
  `tipo_planificacion` int DEFAULT NULL,
  `matriz` int unsigned DEFAULT NULL,
  `tasas_aforo` int unsigned DEFAULT NULL,
  `requisitos` int unsigned DEFAULT NULL,
  `oper_carga` int unsigned DEFAULT NULL,
  `descargas` int unsigned DEFAULT NULL,
  `id_frecuencia` int unsigned DEFAULT NULL,
  `id_sistema` int unsigned DEFAULT NULL,
  `id_cajera` int unsigned DEFAULT NULL,
  `id_parqueo` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entidades_codigo_unique` (`codigo`),
  KEY `entidades_id_area_foreign` (`id_area`),
  KEY `entidades_id_provincia_foreign` (`id_provincia`),
  KEY `entidades_id_municipio_foreign` (`id_municipio`),
  KEY `entidades_parent_id_foreign` (`parent_id`),
  CONSTRAINT `entidades_id_area_foreign` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id`),
  CONSTRAINT `entidades_id_municipio_foreign` FOREIGN KEY (`id_municipio`) REFERENCES `municipios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entidades_id_provincia_foreign` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entidades_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipos_electricos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipos_electricos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `potencia` decimal(8,2) DEFAULT NULL,
  `unidad` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipos_electricos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipos_garaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipos_garaje` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipos_garaje_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estadisticas_explotacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estadisticas_explotacion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_hoja_ruta` bigint unsigned NOT NULL,
  `fecha_indicadores` date NOT NULL,
  `viajes` int NOT NULL DEFAULT '0',
  `kms_carga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kms_vacio` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kms_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `toneladas_posibles` decimal(12,2) NOT NULL DEFAULT '0.00',
  `toneladas_reales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `trafico_posible` decimal(12,2) NOT NULL DEFAULT '0.00',
  `trafico_producido` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estadisticas_explotacion_id_hoja_ruta_foreign` (`id_hoja_ruta`),
  CONSTRAINT `estadisticas_explotacion_id_hoja_ruta_foreign` FOREIGN KEY (`id_hoja_ruta`) REFERENCES `hojas_ruta` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estados_componentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estados_componentes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `estados_componentes_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estados_tarjetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estados_tarjetas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjeta` bigint unsigned NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `id_entrega` bigint unsigned DEFAULT NULL,
  `id_recibe` bigint unsigned DEFAULT NULL,
  `saldo_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `comprobante` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estados_tarjetas_id_entrega_foreign` (`id_entrega`),
  KEY `estados_tarjetas_id_recibe_foreign` (`id_recibe`),
  KEY `estados_tarjetas_id_tarjeta_index` (`id_tarjeta`),
  KEY `estados_tarjetas_fecha_movimiento_index` (`fecha_movimiento`),
  CONSTRAINT `estados_tarjetas_id_entrega_foreign` FOREIGN KEY (`id_entrega`) REFERENCES `users` (`id`),
  CONSTRAINT `estados_tarjetas_id_recibe_foreign` FOREIGN KEY (`id_recibe`) REFERENCES `users` (`id`),
  CONSTRAINT `estados_tarjetas_id_tarjeta_foreign` FOREIGN KEY (`id_tarjeta`) REFERENCES `tarjetas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` bigint NOT NULL,
  `fecha_emision` date NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_unidad` bigint DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `flete_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `flete_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `flete_demora` decimal(12,2) NOT NULL DEFAULT '0.00',
  `otros_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ingreso_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cancelada` tinyint(1) NOT NULL DEFAULT '0',
  `refacturada` tinyint(1) NOT NULL DEFAULT '0',
  `oventas` tinyint(1) NOT NULL DEFAULT '0',
  `id_tipo_ingreso` bigint unsigned DEFAULT NULL,
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_firma` date DEFAULT NULL,
  `fecha_cobro_mn` date DEFAULT NULL,
  `fecha_cobro_mlc` date DEFAULT NULL,
  `fecha_conciliacion` date DEFAULT NULL,
  `factura_cliente` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_pago_mn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitida',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facturas_id_cliente_foreign` (`id_cliente`),
  KEY `facturas_id_user_foreign` (`id_user`),
  KEY `facturas_id_tipo_ingreso_foreign` (`id_tipo_ingreso`),
  KEY `facturas_estado_index` (`estado`),
  KEY `facturas_fecha_emision_index` (`fecha_emision`),
  KEY `facturas_numero_index` (`numero`),
  KEY `facturas_id_unidad_index` (`id_unidad`),
  KEY `facturas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `facturas_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `facturas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_id_tipo_ingreso_foreign` FOREIGN KEY (`id_tipo_ingreso`) REFERENCES `tipo_ingresos` (`id`),
  CONSTRAINT `facturas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `firmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firmas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `confecciona_nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confecciona_cargo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisa_nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisa_cargo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aprueba_nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aprueba_cargo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `firmas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `firmas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `firmas_autorizadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firmas_autorizadas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `firmas_autorizadas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `firmas_autorizadas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fondos_tiempo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fondos_tiempo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fondo_tiempo` decimal(8,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `funciones_cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `funciones_cargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cargo` bigint unsigned NOT NULL,
  `funcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `orden` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `funciones_cargo_id_cargo_foreign` (`id_cargo`),
  CONSTRAINT `funciones_cargo_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gastos_orden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gastos_orden` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_orden_taller` bigint unsigned NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `codigo_pieza` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vale` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `id_motor` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gastos_orden_id_orden_taller_foreign` (`id_orden_taller`),
  KEY `gastos_orden_id_motor_foreign` (`id_motor`),
  CONSTRAINT `gastos_orden_id_motor_foreign` FOREIGN KEY (`id_motor`) REFERENCES `motores` (`id`),
  CONSTRAINT `gastos_orden_id_orden_taller_foreign` FOREIGN KEY (`id_orden_taller`) REFERENCES `ordenes_taller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gastos_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gastos_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_orden_taller` bigint unsigned NOT NULL,
  `concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gastos_taller_id_orden_taller_index` (`id_orden_taller`),
  KEY `gastos_taller_fecha_index` (`fecha`),
  CONSTRAINT `gastos_taller_id_orden_taller_foreign` FOREIGN KEY (`id_orden_taller`) REFERENCES `ordenes_taller` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `giros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `giros` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero_carta_porte` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_solicitud` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_lugar_origen` bigint unsigned DEFAULT NULL,
  `id_lugar_destino` bigint unsigned DEFAULT NULL,
  `id_producto` bigint unsigned DEFAULT NULL,
  `id_tipo_carga` bigint unsigned DEFAULT NULL,
  `id_moneda` bigint unsigned DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `fecha_parte` date NOT NULL,
  `ingreso_mt` decimal(12,2) DEFAULT NULL,
  `flete_mt` decimal(12,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `giros_numero_carta_porte_unique` (`numero_carta_porte`),
  KEY `giros_id_solicitud_foreign` (`id_solicitud`),
  KEY `giros_id_tractivo_foreign` (`id_tractivo`),
  KEY `giros_id_cliente_foreign` (`id_cliente`),
  KEY `giros_id_lugar_origen_foreign` (`id_lugar_origen`),
  KEY `giros_id_lugar_destino_foreign` (`id_lugar_destino`),
  KEY `giros_id_producto_foreign` (`id_producto`),
  KEY `giros_id_tipo_carga_foreign` (`id_tipo_carga`),
  KEY `giros_id_moneda_foreign` (`id_moneda`),
  KEY `giros_id_user_foreign` (`id_user`),
  KEY `giros_estado_index` (`estado`),
  CONSTRAINT `giros_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `giros_id_lugar_destino_foreign` FOREIGN KEY (`id_lugar_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `giros_id_lugar_origen_foreign` FOREIGN KEY (`id_lugar_origen`) REFERENCES `lugares` (`id`),
  CONSTRAINT `giros_id_moneda_foreign` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id`),
  CONSTRAINT `giros_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  CONSTRAINT `giros_id_solicitud_foreign` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes_servicio` (`id`),
  CONSTRAINT `giros_id_tipo_carga_foreign` FOREIGN KEY (`id_tipo_carga`) REFERENCES `tipos_cargas` (`id`),
  CONSTRAINT `giros_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `giros_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grupos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `grupos_escala`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos_escala` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tarifa` decimal(10,4) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grupos_escala_codigo_unique` (`codigo`),
  KEY `grupos_escala_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `grupos_escala_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_movimiento` bigint unsigned DEFAULT NULL,
  `id_bolsa` bigint unsigned NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `numero_nomina` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historial_movimientos_id_movimiento_foreign` (`id_movimiento`),
  KEY `historial_movimientos_id_user_foreign` (`id_user`),
  KEY `historial_movimientos_id_bolsa_index` (`id_bolsa`),
  KEY `historial_movimientos_tipo_index` (`tipo`),
  KEY `historial_movimientos_fecha_index` (`fecha`),
  CONSTRAINT `historial_movimientos_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `historial_movimientos_id_movimiento_foreign` FOREIGN KEY (`id_movimiento`) REFERENCES `movimientos` (`id`),
  CONSTRAINT `historial_movimientos_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_tractivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_tractivos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tractivo` bigint unsigned NOT NULL,
  `id_grupo` bigint unsigned DEFAULT NULL,
  `id_caja` bigint unsigned DEFAULT NULL,
  `id_motor` bigint unsigned DEFAULT NULL,
  `id_diferencial` bigint unsigned DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `fecha_cierre` date NOT NULL,
  `km_historico` decimal(12,2) DEFAULT NULL,
  `km_motor` decimal(12,2) DEFAULT NULL,
  `km_caja` decimal(12,2) DEFAULT NULL,
  `km_diferencial` decimal(12,2) DEFAULT NULL,
  `indice` decimal(8,2) DEFAULT NULL,
  `indice_acumulado` decimal(8,2) DEFAULT NULL,
  `plan_combustible` decimal(12,2) DEFAULT NULL,
  `gps` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historial_tractivos_id_tractivo_foreign` (`id_tractivo`),
  KEY `historial_tractivos_id_grupo_foreign` (`id_grupo`),
  KEY `historial_tractivos_id_caja_foreign` (`id_caja`),
  KEY `historial_tractivos_id_motor_foreign` (`id_motor`),
  KEY `historial_tractivos_id_diferencial_foreign` (`id_diferencial`),
  KEY `historial_tractivos_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `historial_tractivos_id_caja_foreign` FOREIGN KEY (`id_caja`) REFERENCES `cajas` (`id`),
  CONSTRAINT `historial_tractivos_id_diferencial_foreign` FOREIGN KEY (`id_diferencial`) REFERENCES `diferenciales` (`id`),
  CONSTRAINT `historial_tractivos_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historial_tractivos_id_grupo_foreign` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id`),
  CONSTRAINT `historial_tractivos_id_motor_foreign` FOREIGN KEY (`id_motor`) REFERENCES `motores` (`id`),
  CONSTRAINT `historial_tractivos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hojas_ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hojas_ruta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_solicitud` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_cliente` bigint unsigned DEFAULT NULL,
  `fecha_salida` date NOT NULL,
  `fecha_llegada_estimada` date DEFAULT NULL,
  `fecha_llegada_real` date DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_transito',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `hora_emision` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_arrastre` bigint unsigned DEFAULT NULL,
  `id_chofer` bigint unsigned DEFAULT NULL,
  `id_chofer2` bigint unsigned DEFAULT NULL,
  `kms_disponible` decimal(10,2) DEFAULT NULL,
  `kms_disponibles_adicionales` decimal(10,2) DEFAULT NULL,
  `id_hr_anterior` bigint unsigned DEFAULT NULL,
  `id_parqueo` bigint unsigned DEFAULT NULL,
  `id_grupo` bigint unsigned DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `fecha_cierre` date DEFAULT NULL,
  `hora_cierre` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kms_totales` decimal(6,2) DEFAULT NULL,
  `combustible_habilitado` decimal(10,2) DEFAULT NULL,
  `combustible_consumido` decimal(10,2) DEFAULT NULL,
  `combustible_tecnico` decimal(10,2) DEFAULT NULL,
  `indice_hr` decimal(10,8) DEFAULT NULL,
  `tiempo_mov` decimal(10,2) DEFAULT NULL,
  `tiempo_espera` decimal(10,2) DEFAULT NULL,
  `tiempo_carga` decimal(10,2) DEFAULT NULL,
  `tiempo_taller` decimal(10,2) DEFAULT NULL,
  `tiempo_inactivo` decimal(10,2) DEFAULT NULL,
  `tiempo_otras_actividades` decimal(10,2) DEFAULT NULL,
  `tiempo_total` decimal(10,2) DEFAULT NULL,
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `analisis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dias_trabajados` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelada` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hojas_ruta_numero_index` (`numero`),
  KEY `hojas_ruta_id_solicitud_index` (`id_solicitud`),
  KEY `hojas_ruta_id_tractivo_index` (`id_tractivo`),
  KEY `hojas_ruta_estado_index` (`estado`),
  KEY `hojas_ruta_fecha_salida_index` (`fecha_salida`),
  KEY `hojas_ruta_id_cliente_foreign` (`id_cliente`),
  KEY `hojas_ruta_id_arrastre_foreign` (`id_arrastre`),
  KEY `hojas_ruta_id_chofer2_foreign` (`id_chofer2`),
  KEY `hojas_ruta_id_hr_anterior_foreign` (`id_hr_anterior`),
  KEY `hojas_ruta_id_parqueo_foreign` (`id_parqueo`),
  KEY `hojas_ruta_id_grupo_foreign` (`id_grupo`),
  KEY `hojas_ruta_id_user_foreign` (`id_user`),
  KEY `hojas_ruta_fecha_emision_index` (`fecha_emision`),
  KEY `hojas_ruta_id_chofer_index` (`id_chofer`),
  KEY `hojas_ruta_id_entidad_index` (`id_entidad`),
  CONSTRAINT `hojas_ruta_id_arrastre_foreign` FOREIGN KEY (`id_arrastre`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hojas_ruta_id_chofer2_foreign` FOREIGN KEY (`id_chofer2`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `hojas_ruta_id_chofer_foreign` FOREIGN KEY (`id_chofer`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `hojas_ruta_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hojas_ruta_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`),
  CONSTRAINT `hojas_ruta_id_grupo_foreign` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id`),
  CONSTRAINT `hojas_ruta_id_hr_anterior_foreign` FOREIGN KEY (`id_hr_anterior`) REFERENCES `hojas_ruta` (`id`),
  CONSTRAINT `hojas_ruta_id_parqueo_foreign` FOREIGN KEY (`id_parqueo`) REFERENCES `lugares` (`id`),
  CONSTRAINT `hojas_ruta_id_solicitud_foreign` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id`),
  CONSTRAINT `hojas_ruta_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `hojas_ruta_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `combinacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: Ctrl+Shift+F',
  `id_accion` bigint unsigned NOT NULL,
  `id_usuario` bigint unsigned DEFAULT NULL,
  `tipo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A=accion, R=reporte',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hotkeys_id_accion_foreign` (`id_accion`),
  KEY `hotkeys_id_usuario_foreign` (`id_usuario`),
  CONSTRAINT `hotkeys_id_accion_foreign` FOREIGN KEY (`id_accion`) REFERENCES `acciones_hotkeys` (`id`),
  CONSTRAINT `hotkeys_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `importes_gps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `importes_gps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_chofer` bigint unsigned NOT NULL,
  `id_causa_gps` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `importes_gps_id_chofer_foreign` (`id_chofer`),
  KEY `importes_gps_id_causa_gps_foreign` (`id_causa_gps`),
  CONSTRAINT `importes_gps_id_causa_gps_foreign` FOREIGN KEY (`id_causa_gps`) REFERENCES `causas_gps` (`id`),
  CONSTRAINT `importes_gps_id_chofer_foreign` FOREIGN KEY (`id_chofer`) REFERENCES `choferes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `importes_multas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `importes_multas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_chofer` bigint unsigned NOT NULL,
  `id_causa_multa` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `importes_multas_id_chofer_foreign` (`id_chofer`),
  KEY `importes_multas_id_causa_multa_foreign` (`id_causa_multa`),
  CONSTRAINT `importes_multas_id_causa_multa_foreign` FOREIGN KEY (`id_causa_multa`) REFERENCES `causas_multas` (`id`),
  CONSTRAINT `importes_multas_id_chofer_foreign` FOREIGN KEY (`id_chofer`) REFERENCES `choferes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incidencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bolsa` bigint unsigned NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_tipo_incidencia` bigint unsigned NOT NULL,
  `periodo_actual` decimal(6,2) NOT NULL DEFAULT '0.00',
  `importe` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `incidencias_id_bolsa_index` (`id_bolsa`),
  KEY `incidencias_fecha_inicio_index` (`fecha_inicio`),
  KEY `incidencias_id_tipo_incidencia_foreign` (`id_tipo_incidencia`),
  CONSTRAINT `incidencias_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incidencias_id_tipo_incidencia_foreign` FOREIGN KEY (`id_tipo_incidencia`) REFERENCES `tipos_incidencias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indicadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indicadores` (
  `id_carta_porte` bigint unsigned NOT NULL,
  `tn_pos_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_real_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_carga_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_vacio_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_total_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_real_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_pos_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_pos_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_real_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_carga_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_vacio_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_total_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_real_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_pos_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_pos_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_real_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_carga_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_vacio_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_total_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_real_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_pos_5` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_pos_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_real_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_carga_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_vacio_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_total_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_real_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_pos_6` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_pos_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tn_real_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_carga_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `km_vacio_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kms_total_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_real_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `traf_pos_7` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_carta_porte`),
  CONSTRAINT `indicadores_id_carta_porte_foreign` FOREIGN KEY (`id_carta_porte`) REFERENCES `cartas_porte` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indicadores_planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indicadores_planes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tipo_indicador` bigint unsigned NOT NULL,
  `periodo` int NOT NULL COMMENT 'año',
  `valores_mensuales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `plan_periodo` decimal(12,2) DEFAULT NULL,
  `ajuste_periodo` decimal(12,2) DEFAULT NULL,
  `real_periodo_anterior` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indicadores_planes_id_tipo_indicador_foreign` (`id_tipo_indicador`),
  CONSTRAINT `indicadores_planes_id_tipo_indicador_foreign` FOREIGN KEY (`id_tipo_indicador`) REFERENCES `tipos_indicadores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_medida` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `costo_unitario` decimal(12,2) DEFAULT NULL,
  `costo_total` decimal(12,2) DEFAULT NULL,
  `ubicacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventario_codigo_unique` (`codigo`),
  KEY `inventario_codigo_index` (`codigo`),
  KEY `inventario_categoria_index` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lecturas_medidores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecturas_medidores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_medidor` bigint unsigned NOT NULL,
  `fecha_lectura` date NOT NULL,
  `lectura_inicial` decimal(12,2) NOT NULL,
  `lectura_final` decimal(12,2) NOT NULL,
  `consumo` decimal(12,2) NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lecturas_medidores_id_medidor_foreign` (`id_medidor`),
  CONSTRAINT `lecturas_medidores_id_medidor_foreign` FOREIGN KEY (`id_medidor`) REFERENCES `medidores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lineas_bateria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_bateria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjetero` bigint unsigned NOT NULL,
  `amperaje` decimal(8,2) DEFAULT NULL,
  `voltaje` decimal(8,2) DEFAULT NULL,
  `largo` decimal(8,2) DEFAULT NULL,
  `ancho` decimal(8,2) DEFAULT NULL,
  `alto` decimal(8,2) DEFAULT NULL,
  `durabilidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lineas_bateria_id_tarjetero_foreign` (`id_tarjetero`),
  CONSTRAINT `lineas_bateria_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lineas_diferencial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_diferencial` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjetero` bigint unsigned NOT NULL,
  `id_lubricante` bigint unsigned DEFAULT NULL,
  `durabilidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ancho` decimal(8,2) DEFAULT NULL,
  `relacion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `litros` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lineas_diferencial_id_tarjetero_foreign` (`id_tarjetero`),
  KEY `lineas_diferencial_id_lubricante_foreign` (`id_lubricante`),
  CONSTRAINT `lineas_diferencial_id_lubricante_foreign` FOREIGN KEY (`id_lubricante`) REFERENCES `tipos_lubricantes` (`id`),
  CONSTRAINT `lineas_diferencial_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lineas_lubricante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_lubricante` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjetero` bigint unsigned NOT NULL,
  `id_tipo_lubricante` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lineas_lubricante_id_tarjetero_foreign` (`id_tarjetero`),
  KEY `lineas_lubricante_id_tipo_lubricante_foreign` (`id_tipo_lubricante`),
  CONSTRAINT `lineas_lubricante_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lineas_lubricante_id_tipo_lubricante_foreign` FOREIGN KEY (`id_tipo_lubricante`) REFERENCES `tipos_lubricantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lineas_mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_mantenimiento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tipo_mantenimiento` bigint unsigned NOT NULL,
  `kilometraje` int NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lineas_mantenimiento_id_tipo_mantenimiento_foreign` (`id_tipo_mantenimiento`),
  CONSTRAINT `lineas_mantenimiento_id_tipo_mantenimiento_foreign` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipos_mantenimiento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lineas_neumatico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_neumatico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjetero` bigint unsigned NOT NULL,
  `id_tipo_neumatico` bigint unsigned DEFAULT NULL,
  `id_medida_neumatico` bigint unsigned DEFAULT NULL,
  `capas` int DEFAULT NULL,
  `presion` decimal(8,2) DEFAULT NULL,
  `carga` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `velocidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `durabilidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regrabable` tinyint(1) NOT NULL DEFAULT '0',
  `camara` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lineas_neumatico_id_tarjetero_foreign` (`id_tarjetero`),
  KEY `lineas_neumatico_id_tipo_neumatico_foreign` (`id_tipo_neumatico`),
  KEY `lineas_neumatico_id_medida_neumatico_foreign` (`id_medida_neumatico`),
  CONSTRAINT `lineas_neumatico_id_medida_neumatico_foreign` FOREIGN KEY (`id_medida_neumatico`) REFERENCES `medidas_neumaticos` (`id`),
  CONSTRAINT `lineas_neumatico_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lineas_neumatico_id_tipo_neumatico_foreign` FOREIGN KEY (`id_tipo_neumatico`) REFERENCES `tipos_neumaticos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lineas_otro_agregado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_otro_agregado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjetero` bigint unsigned NOT NULL,
  `id_tipo_agregado` bigint unsigned DEFAULT NULL,
  `durabilidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lineas_otro_agregado_id_tarjetero_foreign` (`id_tarjetero`),
  KEY `lineas_otro_agregado_id_tipo_agregado_foreign` (`id_tipo_agregado`),
  CONSTRAINT `lineas_otro_agregado_id_tarjetero_foreign` FOREIGN KEY (`id_tarjetero`) REFERENCES `tarjetero` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lineas_otro_agregado_id_tipo_agregado_foreign` FOREIGN KEY (`id_tipo_agregado`) REFERENCES `tipos_agregados` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locales_electricos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locales_electricos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locales_electricos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lubricantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lubricantes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viscosidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `costo_litro` decimal(10,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lubricantes_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lugares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lugares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provincia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipio` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personalidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lugares_codigo_unique` (`codigo`),
  KEY `lugares_codigo_index` (`codigo`),
  KEY `lugares_provincia_index` (`provincia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mantenimiento_ciclos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mantenimiento_ciclos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `km` int DEFAULT NULL,
  `tipo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'motor, caja, diferencial, neumatico, bateria, tractor',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marcas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medidas_neumaticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medidas_neumaticos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `medida` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medidas_neumaticos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medidores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medidores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta_folio` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prepago` tinyint(1) NOT NULL DEFAULT '0',
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lectura_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `factor` decimal(8,2) DEFAULT NULL,
  `lecturas_mensuales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medidores_codigo_unique` (`codigo`),
  KEY `medidores_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `medidores_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medios_proteccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medios_proteccion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tipo_medio_proteccion` bigint unsigned DEFAULT NULL,
  `duracion` int DEFAULT NULL,
  `tipo_duracion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medios_proteccion_id_tipo_medio_proteccion_foreign` (`id_tipo_medio_proteccion`),
  CONSTRAINT `medios_proteccion_id_tipo_medio_proteccion_foreign` FOREIGN KEY (`id_tipo_medio_proteccion`) REFERENCES `tipos_medios_proteccion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permission` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` smallint unsigned NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_parent_id_orden_index` (`parent_id`,`orden`),
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dias_laborables` decimal(6,2) DEFAULT NULL,
  `dias_laborables_sin_sabado` decimal(6,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modelos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monedas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monedas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `simbolo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monedas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motivos_baja_bateria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivos_baja_bateria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `motivos_baja_bateria_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motivos_entrada_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivos_entrada_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `motivos_entrada_taller_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motivos_espera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivos_espera` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpl` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caballaje` int DEFAULT NULL,
  `cantidad_lubricante` int DEFAULT NULL,
  `numero_tiempos` int DEFAULT NULL,
  `numero_cilindros` int DEFAULT NULL,
  `kms_acumulados` int DEFAULT NULL,
  `capacidad_carter` int DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_baja` date DEFAULT NULL,
  `id_lubricante` bigint unsigned DEFAULT NULL,
  `id_pais` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `motores_codigo_unique` (`codigo`),
  KEY `motores_id_tractivo_index` (`id_tractivo`),
  KEY `motores_estado_index` (`estado`),
  KEY `motores_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `motores_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `motores_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motores_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motores_movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_motor` bigint unsigned NOT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'instalacion, retiro, reparacion',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `motores_movimientos_id_motor_foreign` (`id_motor`),
  KEY `motores_movimientos_id_tractivo_foreign` (`id_tractivo`),
  CONSTRAINT `motores_movimientos_id_motor_foreign` FOREIGN KEY (`id_motor`) REFERENCES `motores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `motores_movimientos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movil_web`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movil_web` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `hoja_ruta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `combustible` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bolsa` bigint unsigned NOT NULL,
  `tipo_movimiento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `id_entidad_origen` bigint unsigned NOT NULL,
  `id_entidad_destino` bigint unsigned NOT NULL,
  `id_cargo` bigint unsigned NOT NULL,
  `id_turno` bigint unsigned NOT NULL,
  `salario` decimal(12,2) DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_id_entidad_origen_foreign` (`id_entidad_origen`),
  KEY `movimientos_id_entidad_destino_foreign` (`id_entidad_destino`),
  KEY `movimientos_id_cargo_foreign` (`id_cargo`),
  KEY `movimientos_id_turno_foreign` (`id_turno`),
  KEY `movimientos_id_bolsa_index` (`id_bolsa`),
  KEY `movimientos_tipo_movimiento_index` (`tipo_movimiento`),
  KEY `movimientos_fecha_movimiento_index` (`fecha_movimiento`),
  CONSTRAINT `movimientos_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `movimientos_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`),
  CONSTRAINT `movimientos_id_entidad_destino_foreign` FOREIGN KEY (`id_entidad_destino`) REFERENCES `entidades` (`id`),
  CONSTRAINT `movimientos_id_entidad_origen_foreign` FOREIGN KEY (`id_entidad_origen`) REFERENCES `entidades` (`id`),
  CONSTRAINT `movimientos_id_turno_foreign` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_almacen` bigint unsigned DEFAULT NULL,
  `id_suministrador` bigint unsigned DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `factura` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_factura` date DEFAULT NULL,
  `importe_mn` decimal(12,2) DEFAULT NULL,
  `importe_me` decimal(12,2) DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `movimientos_inventario_folio_unique` (`folio`),
  KEY `movimientos_inventario_id_almacen_foreign` (`id_almacen`),
  KEY `movimientos_inventario_id_suministrador_foreign` (`id_suministrador`),
  CONSTRAINT `movimientos_inventario_id_almacen_foreign` FOREIGN KEY (`id_almacen`) REFERENCES `tarjetero` (`id`),
  CONSTRAINT `movimientos_inventario_id_suministrador_foreign` FOREIGN KEY (`id_suministrador`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_orden_taller` bigint unsigned NOT NULL,
  `id_nave` bigint unsigned DEFAULT NULL,
  `id_valla` bigint unsigned DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_final` datetime DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_final` time DEFAULT NULL,
  `tiempo_minutos` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_taller_id_orden_taller_foreign` (`id_orden_taller`),
  KEY `movimientos_taller_id_nave_foreign` (`id_nave`),
  KEY `movimientos_taller_id_valla_foreign` (`id_valla`),
  CONSTRAINT `movimientos_taller_id_nave_foreign` FOREIGN KEY (`id_nave`) REFERENCES `naves` (`id`),
  CONSTRAINT `movimientos_taller_id_orden_taller_foreign` FOREIGN KEY (`id_orden_taller`) REFERENCES `ordenes_taller` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_taller_id_valla_foreign` FOREIGN KEY (`id_valla`) REFERENCES `vallas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_tarjetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_tarjetas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tarjeta` bigint unsigned NOT NULL,
  `tipo_movimiento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `saldo_anterior` decimal(12,2) NOT NULL,
  `saldo_posterior` decimal(12,2) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_tarjetas_id_tarjeta_index` (`id_tarjeta`),
  KEY `movimientos_tarjetas_fecha_movimiento_index` (`fecha_movimiento`),
  CONSTRAINT `movimientos_tarjetas_id_tarjeta_foreign` FOREIGN KEY (`id_tarjeta`) REFERENCES `tarjetas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `municipios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `municipios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_provincia` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `municipios_id_provincia_index` (`id_provincia`),
  CONSTRAINT `municipios_id_provincia_foreign` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `naves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `naves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `id_taller` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `naves_codigo_unique` (`codigo`),
  KEY `naves_id_entidad_foreign` (`id_entidad`),
  KEY `naves_id_taller_foreign` (`id_taller`),
  CONSTRAINT `naves_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `naves_id_taller_foreign` FOREIGN KEY (`id_taller`) REFERENCES `talleres` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `navieras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `navieras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `navieras_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `neumaticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `neumaticos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medida` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tractivo` bigint unsigned NOT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `kilometraje` decimal(12,2) NOT NULL DEFAULT '0.00',
  `precio_mn` decimal(12,2) DEFAULT NULL,
  `precio_me` decimal(12,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `id_posicion` bigint unsigned DEFAULT NULL,
  `fecha_fabricacion` date DEFAULT NULL,
  `balanceada` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profinicial` int DEFAULT NULL,
  `explotacion_anterior` decimal(12,2) DEFAULT NULL,
  `kms_promedio` decimal(12,2) DEFAULT NULL,
  `fecha_plan_retiro` date DEFAULT NULL,
  `fecha_plan_aviso` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `neumaticos_folio_unique` (`folio`),
  KEY `neumaticos_id_tractivo_index` (`id_tractivo`),
  KEY `neumaticos_estado_index` (`estado`),
  KEY `neumaticos_id_entidad_foreign` (`id_entidad`),
  KEY `neumaticos_id_posicion_foreign` (`id_posicion`),
  CONSTRAINT `neumaticos_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `neumaticos_id_posicion_foreign` FOREIGN KEY (`id_posicion`) REFERENCES `posiciones_neumaticos` (`id`),
  CONSTRAINT `neumaticos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `neumaticos_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `neumaticos_movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_neumatico` bigint unsigned NOT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `fecha_montaje` date NOT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `km_instalado` decimal(12,2) DEFAULT NULL,
  `km_retirado` decimal(12,2) DEFAULT NULL,
  `posicion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_destino` bigint unsigned DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `neumaticos_movimientos_id_neumatico_foreign` (`id_neumatico`),
  KEY `neumaticos_movimientos_id_tractivo_foreign` (`id_tractivo`),
  KEY `neumaticos_movimientos_id_destino_foreign` (`id_destino`),
  CONSTRAINT `neumaticos_movimientos_id_destino_foreign` FOREIGN KEY (`id_destino`) REFERENCES `destinos_agregados` (`id`),
  CONSTRAINT `neumaticos_movimientos_id_neumatico_foreign` FOREIGN KEY (`id_neumatico`) REFERENCES `neumaticos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `neumaticos_movimientos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `neumaticos_roturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `neumaticos_roturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_neumatico` bigint unsigned NOT NULL,
  `id_tipo_causa` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `neumaticos_roturas_id_neumatico_foreign` (`id_neumatico`),
  KEY `neumaticos_roturas_id_tipo_causa_foreign` (`id_tipo_causa`),
  CONSTRAINT `neumaticos_roturas_id_neumatico_foreign` FOREIGN KEY (`id_neumatico`) REFERENCES `neumaticos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `neumaticos_roturas_id_tipo_causa_foreign` FOREIGN KEY (`id_tipo_causa`) REFERENCES `tipos_causas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ordenes_operaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_operaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_orden_taller` bigint unsigned NOT NULL,
  `id_tipo_operacion` bigint unsigned NOT NULL,
  `id_subsistema` bigint unsigned NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_mano_obra` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_repuestos` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ordenes_operaciones_id_subsistema_foreign` (`id_subsistema`),
  KEY `ordenes_operaciones_id_orden_taller_index` (`id_orden_taller`),
  KEY `ordenes_operaciones_id_tipo_operacion_index` (`id_tipo_operacion`),
  CONSTRAINT `ordenes_operaciones_id_orden_taller_foreign` FOREIGN KEY (`id_orden_taller`) REFERENCES `ordenes_taller` (`id`),
  CONSTRAINT `ordenes_operaciones_id_subsistema_foreign` FOREIGN KEY (`id_subsistema`) REFERENCES `subsistemas` (`id`),
  CONSTRAINT `ordenes_operaciones_id_tipo_operacion_foreign` FOREIGN KEY (`id_tipo_operacion`) REFERENCES `tipos_operaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ordenes_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tractivo` bigint unsigned NOT NULL,
  `id_tipo_mantenimiento` bigint unsigned NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_salida_estimada` date DEFAULT NULL,
  `fecha_salida_real` date DEFAULT NULL,
  `kilometraje` decimal(12,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  `diagnostico` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ordenes_taller_numero_unique` (`numero`),
  KEY `ordenes_taller_id_tipo_mantenimiento_foreign` (`id_tipo_mantenimiento`),
  KEY `ordenes_taller_numero_index` (`numero`),
  KEY `ordenes_taller_id_tractivo_index` (`id_tractivo`),
  KEY `ordenes_taller_estado_index` (`estado`),
  KEY `ordenes_taller_fecha_ingreso_index` (`fecha_ingreso`),
  CONSTRAINT `ordenes_taller_id_tipo_mantenimiento_foreign` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipos_mantenimiento` (`id`),
  CONSTRAINT `ordenes_taller_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organismos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organismos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organismos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `osdes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `osdes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `siglas` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_organismo` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `osdes_id_organismo_foreign` (`id_organismo`),
  CONSTRAINT `osdes_id_organismo_foreign` FOREIGN KEY (`id_organismo`) REFERENCES `organismos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otros_agregados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otros_agregados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_marca` bigint unsigned DEFAULT NULL,
  `id_modelo` bigint unsigned DEFAULT NULL,
  `id_pais` bigint unsigned DEFAULT NULL,
  `id_estado` bigint unsigned DEFAULT NULL,
  `id_lubricante` bigint unsigned DEFAULT NULL,
  `nro_cilindros` int DEFAULT NULL,
  `nro_tiempos` int DEFAULT NULL,
  `caballaje` decimal(8,2) DEFAULT NULL,
  `cantidad_lubricante` decimal(8,2) DEFAULT NULL,
  `fecha_baja` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `otros_agregados_codigo_unique` (`codigo`),
  KEY `otros_agregados_id_marca_foreign` (`id_marca`),
  KEY `otros_agregados_id_modelo_foreign` (`id_modelo`),
  KEY `otros_agregados_id_pais_foreign` (`id_pais`),
  KEY `otros_agregados_id_estado_foreign` (`id_estado`),
  KEY `otros_agregados_id_lubricante_foreign` (`id_lubricante`),
  CONSTRAINT `otros_agregados_id_estado_foreign` FOREIGN KEY (`id_estado`) REFERENCES `estados_componentes` (`id`),
  CONSTRAINT `otros_agregados_id_lubricante_foreign` FOREIGN KEY (`id_lubricante`) REFERENCES `tipos_lubricantes` (`id`),
  CONSTRAINT `otros_agregados_id_marca_foreign` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id`),
  CONSTRAINT `otros_agregados_id_modelo_foreign` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id`),
  CONSTRAINT `otros_agregados_id_pais_foreign` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otros_gastos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otros_gastos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bolsa` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `id_tipo_concepto` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `monto_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otros_gastos_id_bolsa_foreign` (`id_bolsa`),
  KEY `otros_gastos_id_tractivo_foreign` (`id_tractivo`),
  KEY `otros_gastos_id_tipo_concepto_foreign` (`id_tipo_concepto`),
  KEY `otros_gastos_id_user_foreign` (`id_user`),
  KEY `otros_gastos_fecha_index` (`fecha`),
  KEY `otros_gastos_estado_index` (`estado`),
  CONSTRAINT `otros_gastos_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `otros_gastos_id_tipo_concepto_foreign` FOREIGN KEY (`id_tipo_concepto`) REFERENCES `tipos_conceptos` (`id`),
  CONSTRAINT `otros_gastos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `otros_gastos_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otros_ingresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otros_ingresos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_giro` bigint unsigned NOT NULL,
  `concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otros_ingresos_id_giro_foreign` (`id_giro`),
  CONSTRAINT `otros_ingresos_id_giro_foreign` FOREIGN KEY (`id_giro`) REFERENCES `giros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otros_ingresos_pre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otros_ingresos_pre` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_carta_porte` bigint unsigned NOT NULL,
  `id_tipo_ingreso` bigint unsigned NOT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `importe_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otros_ingresos_pre_id_tipo_ingreso_foreign` (`id_tipo_ingreso`),
  KEY `otros_ingresos_pre_id_carta_porte_index` (`id_carta_porte`),
  CONSTRAINT `otros_ingresos_pre_id_carta_porte_foreign` FOREIGN KEY (`id_carta_porte`) REFERENCES `cartas_porte` (`id`),
  CONSTRAINT `otros_ingresos_pre_id_tipo_ingreso_foreign` FOREIGN KEY (`id_tipo_ingreso`) REFERENCES `tipo_ingresos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tipo_documento` bigint unsigned DEFAULT NULL,
  `id_moneda` bigint unsigned DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `numero_documento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_id_tipo_documento_foreign` (`id_tipo_documento`),
  KEY `pagos_id_moneda_foreign` (`id_moneda`),
  KEY `pagos_id_user_foreign` (`id_user`),
  KEY `pagos_fecha_pago_index` (`fecha_pago`),
  KEY `pagos_estado_index` (`estado`),
  CONSTRAINT `pagos_id_moneda_foreign` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id`),
  CONSTRAINT `pagos_id_tipo_documento_foreign` FOREIGN KEY (`id_tipo_documento`) REFERENCES `tipos_documentos` (`id`),
  CONSTRAINT `pagos_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagos_adicionales_cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_adicionales_cargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cargo` bigint unsigned NOT NULL,
  `id_tipo_pago_adicional` bigint unsigned NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cargo_pago_adicional_unique` (`id_cargo`,`id_tipo_pago_adicional`),
  KEY `pagos_adicionales_cargo_id_tipo_pago_adicional_foreign` (`id_tipo_pago_adicional`),
  CONSTRAINT `pagos_adicionales_cargo_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagos_adicionales_cargo_id_tipo_pago_adicional_foreign` FOREIGN KEY (`id_tipo_pago_adicional`) REFERENCES `tipos_pagos_adicionales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paises_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `password_histories_user_id_index` (`user_id`),
  CONSTRAINT `password_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `penalizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penalizaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bolsa` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_tipo_penalizacion` bigint unsigned NOT NULL,
  `importe` decimal(6,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `penalizaciones_id_bolsa_index` (`id_bolsa`),
  KEY `penalizaciones_fecha_index` (`fecha`),
  KEY `penalizaciones_id_tipo_penalizacion_foreign` (`id_tipo_penalizacion`),
  CONSTRAINT `penalizaciones_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penalizaciones_id_tipo_penalizacion_foreign` FOREIGN KEY (`id_tipo_penalizacion`) REFERENCES `tipos_penalizaciones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perfiles_rh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfiles_rh` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `piezas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `piezas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_medida` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` int NOT NULL DEFAULT '0',
  `stock_actual` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `piezas_codigo_unique` (`codigo`),
  KEY `piezas_codigo_index` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pizarra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pizarra` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tractivo_id` bigint unsigned NOT NULL,
  `conductor_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `ubicacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destino` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salida` datetime DEFAULT NULL,
  `llegada_estimada` datetime DEFAULT NULL,
  `llegada_real` datetime DEFAULT NULL,
  `carga` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tonelaje` decimal(10,2) DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pizarra_tractivo_id_foreign` (`tractivo_id`),
  KEY `pizarra_conductor_id_foreign` (`conductor_id`),
  CONSTRAINT `pizarra_conductor_id_foreign` FOREIGN KEY (`conductor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pizarra_tractivo_id_foreign` FOREIGN KEY (`tractivo_id`) REFERENCES `tractivos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pizarra_tractivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pizarra_tractivos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mes` int NOT NULL,
  `ano` int NOT NULL,
  `id_tractivo` bigint unsigned NOT NULL,
  `dias` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pizarra_tractivos_mes_ano_id_tractivo_unique` (`mes`,`ano`,`id_tractivo`),
  KEY `pizarra_tractivos_id_tractivo_foreign` (`id_tractivo`),
  CONSTRAINT `pizarra_tractivos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `planes_codigo_unique` (`codigo`),
  KEY `planes_codigo_index` (`codigo`),
  KEY `planes_id_cliente_index` (`id_cliente`),
  CONSTRAINT `planes_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planes_mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planes_mantenimiento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_orden_taller` bigint unsigned NOT NULL,
  `fecha_mantenimiento` date NOT NULL,
  `id_tipo_mantenimiento` bigint unsigned NOT NULL,
  `kms_mantenimiento` bigint NOT NULL,
  `kms_disponible` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planes_mantenimiento_id_orden_taller_foreign` (`id_orden_taller`),
  KEY `planes_mantenimiento_id_tipo_mantenimiento_foreign` (`id_tipo_mantenimiento`),
  CONSTRAINT `planes_mantenimiento_id_orden_taller_foreign` FOREIGN KEY (`id_orden_taller`) REFERENCES `ordenes_taller` (`id`),
  CONSTRAINT `planes_mantenimiento_id_tipo_mantenimiento_foreign` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipos_mantenimiento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `posiciones_neumaticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posiciones_neumaticos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posiciones_neumaticos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prefacturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prefacturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `flete_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `flete_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `flete_demora` decimal(12,2) NOT NULL DEFAULT '0.00',
  `otros_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ingreso_mt` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prefacturas_numero_unique` (`numero`),
  KEY `prefacturas_id_cliente_foreign` (`id_cliente`),
  KEY `prefacturas_id_user_foreign` (`id_user`),
  KEY `prefacturas_estado_index` (`estado`),
  KEY `prefacturas_fecha_index` (`fecha`),
  KEY `prefacturas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `prefacturas_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `prefacturas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prefacturas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provincias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provincias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reembolsos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reembolsos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_bolsa` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `documentos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reembolsos_id_bolsa_index` (`id_bolsa`),
  KEY `reembolsos_fecha_index` (`fecha`),
  CONSTRAINT `reembolsos_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registro_ordenes_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_ordenes_taller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tractivo` bigint unsigned NOT NULL,
  `fecha_salida_taller` date NOT NULL,
  `tiempo_minutos` int NOT NULL DEFAULT '0' COMMENT 'Minutos en taller',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registro_ordenes_taller_id_tractivo_foreign` (`id_tractivo`),
  CONSTRAINT `registro_ordenes_taller_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reportes_costos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes_costos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha_reporte` date NOT NULL,
  `id_tractivo` bigint unsigned NOT NULL,
  `combustible_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `lubricante_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `piezas_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `salario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vacaciones` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuesto1` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuesto2` decimal(12,2) NOT NULL DEFAULT '0.00',
  `salario_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `dietas` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amortizacion_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `chapa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `otros_gastos_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indirectos_admin_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indirectos_taller_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `indirectos_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gastos_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ingresos_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kms_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `toneladas` decimal(10,2) NOT NULL DEFAULT '0.00',
  `trafico` decimal(10,2) NOT NULL DEFAULT '0.00',
  `horas_taller` int NOT NULL DEFAULT '0',
  `utilidad_mn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `utilidad_mlc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `costo_mn` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `costo_mlc` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `costo_tn_kms` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reportes_costos_id_user_foreign` (`id_user`),
  KEY `reportes_costos_fecha_reporte_index` (`fecha_reporte`),
  KEY `reportes_costos_id_tractivo_index` (`id_tractivo`),
  CONSTRAINT `reportes_costos_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `reportes_costos_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reportes_legacy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes_legacy` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `controlador` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `variable` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rechum` tinyint(1) NOT NULL DEFAULT '0',
  `com` tinyint(1) NOT NULL DEFAULT '0',
  `cont` tinyint(1) NOT NULL DEFAULT '0',
  `conte` tinyint(1) NOT NULL DEFAULT '0',
  `tec` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mes` int NOT NULL,
  `ano` int NOT NULL,
  `id_bolsa` bigint unsigned NOT NULL,
  `id_movimiento` bigint unsigned DEFAULT NULL,
  `numero_nomina` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_area` bigint unsigned DEFAULT NULL,
  `id_sexo` bigint unsigned DEFAULT NULL,
  `id_categoria_cargo` bigint unsigned DEFAULT NULL,
  `id_cargo` bigint unsigned DEFAULT NULL,
  `id_tipo_sistema_pago` bigint unsigned DEFAULT NULL,
  `id_grupo_escala` bigint unsigned DEFAULT NULL,
  `id_nivel_educacion` bigint unsigned DEFAULT NULL,
  `id_integracion_politica` bigint unsigned DEFAULT NULL,
  `id_color_piel` bigint unsigned DEFAULT NULL,
  `salario_base` decimal(12,2) NOT NULL DEFAULT '0.00',
  `plus_base` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tarifa` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `plus` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `cla` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `t_regular` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_irregular` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_garantia` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_doblaje` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_nocturna_1` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_nocturna_2` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_feriados` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_extra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `t_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_regular` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_plus` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_adicional` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_cla` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_gps` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_irregular` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_nocturna_1` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_nocturna_2` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_feriados` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_maestrias` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_g_electro` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_garantia` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_doblaje` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_h_extra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_reservas_alm` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_otros` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_ir_resultado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pen_resultado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pen_importe` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_resultado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_salario_final` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ri` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cotizacion` decimal(12,2) NOT NULL DEFAULT '0.00',
  `salario_cotizacion` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salarios_id_movimiento_foreign` (`id_movimiento`),
  KEY `salarios_id_area_foreign` (`id_area`),
  KEY `salarios_id_sexo_foreign` (`id_sexo`),
  KEY `salarios_id_categoria_cargo_foreign` (`id_categoria_cargo`),
  KEY `salarios_id_cargo_foreign` (`id_cargo`),
  KEY `salarios_id_tipo_sistema_pago_foreign` (`id_tipo_sistema_pago`),
  KEY `salarios_id_grupo_escala_foreign` (`id_grupo_escala`),
  KEY `salarios_id_nivel_educacion_foreign` (`id_nivel_educacion`),
  KEY `salarios_id_integracion_politica_foreign` (`id_integracion_politica`),
  KEY `salarios_id_color_piel_foreign` (`id_color_piel`),
  KEY `salarios_id_user_foreign` (`id_user`),
  KEY `salarios_mes_ano_index` (`mes`,`ano`),
  KEY `salarios_id_bolsa_index` (`id_bolsa`),
  KEY `salarios_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `salarios_id_area_foreign` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id`),
  CONSTRAINT `salarios_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `salarios_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`),
  CONSTRAINT `salarios_id_categoria_cargo_foreign` FOREIGN KEY (`id_categoria_cargo`) REFERENCES `categorias_cargo` (`id`),
  CONSTRAINT `salarios_id_color_piel_foreign` FOREIGN KEY (`id_color_piel`) REFERENCES `tipos_color_piel` (`id`),
  CONSTRAINT `salarios_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salarios_id_grupo_escala_foreign` FOREIGN KEY (`id_grupo_escala`) REFERENCES `grupos_escala` (`id`),
  CONSTRAINT `salarios_id_integracion_politica_foreign` FOREIGN KEY (`id_integracion_politica`) REFERENCES `tipos_integracion_politica` (`id`),
  CONSTRAINT `salarios_id_movimiento_foreign` FOREIGN KEY (`id_movimiento`) REFERENCES `movimientos` (`id`),
  CONSTRAINT `salarios_id_nivel_educacion_foreign` FOREIGN KEY (`id_nivel_educacion`) REFERENCES `tipos_nivel_educacion` (`id`),
  CONSTRAINT `salarios_id_sexo_foreign` FOREIGN KEY (`id_sexo`) REFERENCES `tipos_sexo` (`id`),
  CONSTRAINT `salarios_id_tipo_sistema_pago_foreign` FOREIGN KEY (`id_tipo_sistema_pago`) REFERENCES `tipos_sistemas_pago` (`id`),
  CONSTRAINT `salarios_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salarios_administrativos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salarios_administrativos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `id_movimiento` bigint unsigned DEFAULT NULL,
  `feriados` decimal(12,2) NOT NULL DEFAULT '0.00',
  `irregular` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cpl` decimal(12,2) NOT NULL DEFAULT '0.00',
  `alimentos_extra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `dias_taller` decimal(12,2) NOT NULL DEFAULT '0.00',
  `h_extra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imp_h_extra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salarios_administrativos_id_movimiento_foreign` (`id_movimiento`),
  KEY `salarios_administrativos_id_user_foreign` (`id_user`),
  KEY `salarios_administrativos_fecha_index` (`fecha`),
  CONSTRAINT `salarios_administrativos_id_movimiento_foreign` FOREIGN KEY (`id_movimiento`) REFERENCES `movimientos` (`id`),
  CONSTRAINT `salarios_administrativos_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `servicentros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicentros` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `servicentros_nombre_index` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `solicitudes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_lugar_origen` bigint unsigned NOT NULL,
  `id_lugar_destino` bigint unsigned NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `fecha_requerida` date NOT NULL,
  `toneladas_solicitadas` decimal(10,2) NOT NULL,
  `tipo_carga` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion_carga` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `solicitudes_numero_unique` (`numero`),
  KEY `solicitudes_id_lugar_origen_foreign` (`id_lugar_origen`),
  KEY `solicitudes_id_lugar_destino_foreign` (`id_lugar_destino`),
  KEY `solicitudes_numero_index` (`numero`),
  KEY `solicitudes_id_cliente_index` (`id_cliente`),
  KEY `solicitudes_estado_index` (`estado`),
  KEY `solicitudes_fecha_solicitud_index` (`fecha_solicitud`),
  CONSTRAINT `solicitudes_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `solicitudes_id_lugar_destino_foreign` FOREIGN KEY (`id_lugar_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `solicitudes_id_lugar_origen_foreign` FOREIGN KEY (`id_lugar_origen`) REFERENCES `lugares` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `solicitudes_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_servicio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_lugar_origen` bigint unsigned DEFAULT NULL,
  `id_lugar_destino` bigint unsigned DEFAULT NULL,
  `id_producto` bigint unsigned DEFAULT NULL,
  `id_producto2` bigint unsigned DEFAULT NULL,
  `id_tipo_carga` bigint unsigned DEFAULT NULL,
  `id_tipo_carga2` bigint unsigned DEFAULT NULL,
  `id_moneda` bigint unsigned DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `fecha_solicitud` date NOT NULL,
  `fecha_planificada` date DEFAULT NULL,
  `fecha_ejecutada` date DEFAULT NULL,
  `valor_mt` decimal(12,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `solicitudes_servicio_numero_unique` (`numero`),
  KEY `solicitudes_servicio_id_cliente_foreign` (`id_cliente`),
  KEY `solicitudes_servicio_id_lugar_origen_foreign` (`id_lugar_origen`),
  KEY `solicitudes_servicio_id_lugar_destino_foreign` (`id_lugar_destino`),
  KEY `solicitudes_servicio_id_producto_foreign` (`id_producto`),
  KEY `solicitudes_servicio_id_producto2_foreign` (`id_producto2`),
  KEY `solicitudes_servicio_id_tipo_carga_foreign` (`id_tipo_carga`),
  KEY `solicitudes_servicio_id_tipo_carga2_foreign` (`id_tipo_carga2`),
  KEY `solicitudes_servicio_id_moneda_foreign` (`id_moneda`),
  KEY `solicitudes_servicio_id_user_foreign` (`id_user`),
  KEY `solicitudes_servicio_estado_index` (`estado`),
  KEY `solicitudes_servicio_fecha_solicitud_index` (`fecha_solicitud`),
  KEY `solicitudes_servicio_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `solicitudes_servicio_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `solicitudes_servicio_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `solicitudes_servicio_id_lugar_destino_foreign` FOREIGN KEY (`id_lugar_destino`) REFERENCES `lugares` (`id`),
  CONSTRAINT `solicitudes_servicio_id_lugar_origen_foreign` FOREIGN KEY (`id_lugar_origen`) REFERENCES `lugares` (`id`),
  CONSTRAINT `solicitudes_servicio_id_moneda_foreign` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id`),
  CONSTRAINT `solicitudes_servicio_id_producto2_foreign` FOREIGN KEY (`id_producto2`) REFERENCES `productos` (`id`),
  CONSTRAINT `solicitudes_servicio_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  CONSTRAINT `solicitudes_servicio_id_tipo_carga2_foreign` FOREIGN KEY (`id_tipo_carga2`) REFERENCES `tipos_cargas` (`id`),
  CONSTRAINT `solicitudes_servicio_id_tipo_carga_foreign` FOREIGN KEY (`id_tipo_carga`) REFERENCES `tipos_cargas` (`id`),
  CONSTRAINT `solicitudes_servicio_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sub_tipos_roturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_tipos_roturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tipo_rotura` bigint unsigned NOT NULL,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_tipos_roturas_id_tipo_rotura_foreign` (`id_tipo_rotura`),
  CONSTRAINT `sub_tipos_roturas_id_tipo_rotura_foreign` FOREIGN KEY (`id_tipo_rotura`) REFERENCES `tipos_roturas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subsistemas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subsistemas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subsistemas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `talleres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `talleres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `talleres_codigo_unique` (`codigo`),
  KEY `talleres_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `talleres_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarifas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tipo_carga` bigint unsigned NOT NULL,
  `kms` decimal(10,2) DEFAULT NULL,
  `tarifa_mt` decimal(12,2) DEFAULT NULL,
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tarifas_id_tipo_carga_index` (`id_tipo_carga`),
  CONSTRAINT `tarifas_id_tipo_carga_foreign` FOREIGN KEY (`id_tipo_carga`) REFERENCES `tipos_cargas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarjetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarjetas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` bigint unsigned DEFAULT NULL,
  `saldo_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `fcompra` date DEFAULT NULL,
  `fvence` date DEFAULT NULL,
  `saldoinicialmon` decimal(10,2) DEFAULT NULL,
  `saldoiniciallts` decimal(10,2) DEFAULT NULL,
  `saldoactuallts` decimal(10,2) DEFAULT NULL,
  `saldotransferenciamon` decimal(10,2) DEFAULT NULL,
  `saldotransferencialts` decimal(10,2) DEFAULT NULL,
  `idmonedas` bigint unsigned DEFAULT NULL,
  `idtipocombustibles` bigint unsigned DEFAULT NULL,
  `idempleado` bigint unsigned DEFAULT NULL,
  `idtractivos` bigint unsigned DEFAULT NULL,
  `idchofer` bigint unsigned DEFAULT NULL,
  `cancelado` int DEFAULT NULL,
  `inactiva` int DEFAULT NULL,
  `fmovimiento` date DEFAULT NULL,
  `fcancelado` date DEFAULT NULL,
  `fcierre` date DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  `limite_credito` decimal(12,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tarjetas_numero_unique` (`numero`),
  KEY `tarjetas_id_cliente_foreign` (`id_cliente`),
  KEY `tarjetas_numero_index` (`numero`),
  KEY `tarjetas_estado_index` (`estado`),
  CONSTRAINT `tarjetas_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarjetero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarjetero` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_linea` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'bateria, lubricante, diferencial, neumatico, otro',
  `id_marca` bigint unsigned DEFAULT NULL,
  `id_modelo` bigint unsigned DEFAULT NULL,
  `id_pais` bigint unsigned DEFAULT NULL,
  `existencia` decimal(12,2) NOT NULL DEFAULT '0.00',
  `precio_mn` decimal(12,2) DEFAULT NULL,
  `precio_me` decimal(12,2) DEFAULT NULL,
  `valor_mn` decimal(12,2) DEFAULT NULL,
  `valor_me` decimal(12,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tarjetero_codigo_unique` (`codigo`),
  KEY `tarjetero_id_marca_foreign` (`id_marca`),
  KEY `tarjetero_id_modelo_foreign` (`id_modelo`),
  KEY `tarjetero_id_pais_foreign` (`id_pais`),
  CONSTRAINT `tarjetero_id_marca_foreign` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id`),
  CONSTRAINT `tarjetero_id_modelo_foreign` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id`),
  CONSTRAINT `tarjetero_id_pais_foreign` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_ingresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_ingresos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `siglas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo_ingresos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_aceites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_aceites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_aceites_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_agregados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_agregados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_agregados_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_arrastres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_arrastres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacidad_toneladas` decimal(8,2) DEFAULT NULL,
  `id_marca` bigint unsigned DEFAULT NULL,
  `id_modelo` bigint unsigned DEFAULT NULL,
  `id_pais` bigint unsigned DEFAULT NULL,
  `id_tipo_equipo` bigint unsigned DEFAULT NULL,
  `fabricacion` int DEFAULT NULL,
  `frecuencia` int DEFAULT NULL,
  `id_medida_del` bigint unsigned DEFAULT NULL,
  `id_medida_tra` bigint unsigned DEFAULT NULL,
  `id_medida_res` bigint unsigned DEFAULT NULL,
  `neum_del_cant` int DEFAULT NULL,
  `neum_tras_cant` int DEFAULT NULL,
  `neum_resp_cant` int DEFAULT NULL,
  `id_tipo_suspension` bigint unsigned DEFAULT NULL,
  `ejes_cant` int DEFAULT NULL,
  `eject_trac` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dist_frente` decimal(8,2) DEFAULT NULL,
  `dist_trasera` decimal(8,2) DEFAULT NULL,
  `largo_garganta` decimal(8,2) DEFAULT NULL,
  `altura_piso` decimal(8,2) DEFAULT NULL,
  `altura_total` decimal(8,2) DEFAULT NULL,
  `largo_total` decimal(8,2) DEFAULT NULL,
  `ancho_total` decimal(8,2) DEFAULT NULL,
  `id_tipo_combustible` bigint unsigned DEFAULT NULL,
  `id_lubricante` bigint unsigned DEFAULT NULL,
  `id_lub_cubo` bigint unsigned DEFAULT NULL,
  `id_tipo_mantenimiento` bigint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_arrastres_codigo_unique` (`codigo`),
  KEY `tipos_arrastres_id_marca_foreign` (`id_marca`),
  KEY `tipos_arrastres_id_modelo_foreign` (`id_modelo`),
  KEY `tipos_arrastres_id_pais_foreign` (`id_pais`),
  KEY `tipos_arrastres_id_tipo_equipo_foreign` (`id_tipo_equipo`),
  KEY `tipos_arrastres_id_medida_del_foreign` (`id_medida_del`),
  KEY `tipos_arrastres_id_medida_tra_foreign` (`id_medida_tra`),
  KEY `tipos_arrastres_id_medida_res_foreign` (`id_medida_res`),
  KEY `tipos_arrastres_id_tipo_suspension_foreign` (`id_tipo_suspension`),
  KEY `tipos_arrastres_id_tipo_combustible_foreign` (`id_tipo_combustible`),
  KEY `tipos_arrastres_id_lubricante_foreign` (`id_lubricante`),
  KEY `tipos_arrastres_id_lub_cubo_foreign` (`id_lub_cubo`),
  KEY `tipos_arrastres_id_tipo_mantenimiento_foreign` (`id_tipo_mantenimiento`),
  CONSTRAINT `tipos_arrastres_id_lub_cubo_foreign` FOREIGN KEY (`id_lub_cubo`) REFERENCES `lubricantes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_lubricante_foreign` FOREIGN KEY (`id_lubricante`) REFERENCES `lubricantes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_marca_foreign` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_medida_del_foreign` FOREIGN KEY (`id_medida_del`) REFERENCES `medidas_neumaticos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_medida_res_foreign` FOREIGN KEY (`id_medida_res`) REFERENCES `medidas_neumaticos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_medida_tra_foreign` FOREIGN KEY (`id_medida_tra`) REFERENCES `medidas_neumaticos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_modelo_foreign` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_pais_foreign` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_tipo_combustible_foreign` FOREIGN KEY (`id_tipo_combustible`) REFERENCES `tipos_combustibles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_tipo_equipo_foreign` FOREIGN KEY (`id_tipo_equipo`) REFERENCES `tipos_equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_tipo_mantenimiento_foreign` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipos_mantenimiento` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_arrastres_id_tipo_suspension_foreign` FOREIGN KEY (`id_tipo_suspension`) REFERENCES `tipos_suspension` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_cargas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_cargas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_cargas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_cargas_reporte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_cargas_reporte` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `km1` decimal(8,2) DEFAULT NULL,
  `km2` decimal(8,2) DEFAULT NULL,
  `km3` decimal(8,2) DEFAULT NULL,
  `km4` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_catalogo_lugares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_catalogo_lugares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_causas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_causas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'aceite, rotura, baja',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_causas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_clasificacion_laboral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_clasificacion_laboral` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designado` tinyint(1) NOT NULL DEFAULT '0',
  `cuadro` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_color_piel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_color_piel` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_combustibles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_combustibles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_combustibles_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_conceptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_conceptos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_conceptos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_contratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_contratos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_contratos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_deducciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_deducciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `clave` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_documentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_documentos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_equipos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_equipos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_estado_civil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_estado_civil` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_estados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_estados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siglas` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_gastos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_gastos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_gastos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_grupo_horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_grupo_horario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_incidencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_tipo_deducciones` bigint unsigned DEFAULT NULL,
  `tsuma` tinyint(1) NOT NULL DEFAULT '0',
  `impsuma` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_incidencias_codigo_unique` (`codigo`),
  KEY `tipos_incidencias_id_tipo_deducciones_foreign` (`id_tipo_deducciones`),
  CONSTRAINT `tipos_incidencias_id_tipo_deducciones_foreign` FOREIGN KEY (`id_tipo_deducciones`) REFERENCES `tipos_deducciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_indicadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_indicadores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_indicadores_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_integracion_politica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_integracion_politica` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `politica` int DEFAULT NULL,
  `abreviatura` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_lubricantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_lubricantes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_lubricantes_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_mantenimiento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kms_max` bigint unsigned DEFAULT NULL,
  `frecuencia` bigint unsigned DEFAULT NULL,
  `mtto_base` bigint unsigned DEFAULT NULL,
  `holgura` bigint unsigned DEFAULT NULL,
  `mttos` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_mantenimiento_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_medios_cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_medios_cargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_medio_proteccion` bigint unsigned NOT NULL,
  `id_cargo` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tipos_medios_cargo_id_medio_proteccion_foreign` (`id_medio_proteccion`),
  KEY `tipos_medios_cargo_id_cargo_foreign` (`id_cargo`),
  CONSTRAINT `tipos_medios_cargo_id_cargo_foreign` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`),
  CONSTRAINT `tipos_medios_cargo_id_medio_proteccion_foreign` FOREIGN KEY (`id_medio_proteccion`) REFERENCES `medios_proteccion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_medios_proteccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_medios_proteccion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_modelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_modelo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ancho` decimal(10,2) DEFAULT NULL,
  `alto` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tipos_modelo_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `tipos_modelo_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_neumaticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_neumaticos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_neumaticos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_nivel_educacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_nivel_educacion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_operaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_operaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_operaciones_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_pagos_adicionales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_pagos_adicionales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_pagos_adicionales_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_penalizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_penalizaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  `tipo_pago_adicional_id` bigint unsigned DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT '0.00',
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_penalizaciones_codigo_unique` (`codigo`),
  KEY `tipos_penalizaciones_area_id_foreign` (`area_id`),
  KEY `tipos_penalizaciones_tipo_pago_adicional_id_foreign` (`tipo_pago_adicional_id`),
  KEY `tipos_penalizaciones_id_entidad_index` (`id_entidad`),
  CONSTRAINT `tipos_penalizaciones_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tipos_penalizaciones_tipo_pago_adicional_id_foreign` FOREIGN KEY (`tipo_pago_adicional_id`) REFERENCES `tipos_pagos_adicionales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_ramas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_ramas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_ramas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_roturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_roturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_roturas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_servicios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_servicios_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_sexo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_sexo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_sistemas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_sistemas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_sistemas_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_sistemas_cuc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_sistemas_cuc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_sistemas_cuc_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_sistemas_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_sistemas_pago` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_sistemas_pago_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_subcta_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_subcta_unidad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_subcta_unidad_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_suspension`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_suspension` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_suspension_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_tasas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_tasas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_tasas_codigo_unique` (`codigo`),
  KEY `tipos_tasas_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `tipos_tasas_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_tractivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_tractivos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_marca` bigint unsigned DEFAULT NULL,
  `id_modelo` bigint unsigned DEFAULT NULL,
  `id_pais` bigint unsigned DEFAULT NULL,
  `id_tipo_mantenimiento` bigint unsigned DEFAULT NULL,
  `fabricacion` int DEFAULT NULL,
  `tipo_equipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bat_cant` int DEFAULT NULL,
  `bat_amp` decimal(8,2) DEFAULT NULL,
  `dif_cant` int DEFAULT NULL,
  `dif_relacion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dif_ancho` decimal(8,2) DEFAULT NULL,
  `id_medida_del` bigint unsigned DEFAULT NULL,
  `id_medida_tra` bigint unsigned DEFAULT NULL,
  `id_medida_res` bigint unsigned DEFAULT NULL,
  `neum_del_cant` int DEFAULT NULL,
  `neum_tras_cant` int DEFAULT NULL,
  `neum_resp_cant` int DEFAULT NULL,
  `neum_tractivos` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ejes_cant` int DEFAULT NULL,
  `eject_trac` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tipo_combustible` bigint unsigned DEFAULT NULL,
  `id_lubricante_motor` bigint unsigned DEFAULT NULL,
  `id_lubricante_cubo` bigint unsigned DEFAULT NULL,
  `lub_norma` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lub_caja` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dist_eje_inter` decimal(8,2) DEFAULT NULL,
  `dist_eje_tras` decimal(8,2) DEFAULT NULL,
  `cama_largo` decimal(8,2) DEFAULT NULL,
  `cama_ancho` decimal(8,2) DEFAULT NULL,
  `cama_altura` decimal(8,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_tractivos_codigo_unique` (`codigo`),
  KEY `tipos_tractivos_id_marca_foreign` (`id_marca`),
  KEY `tipos_tractivos_id_modelo_foreign` (`id_modelo`),
  KEY `tipos_tractivos_id_pais_foreign` (`id_pais`),
  KEY `tipos_tractivos_id_medida_del_foreign` (`id_medida_del`),
  KEY `tipos_tractivos_id_medida_tra_foreign` (`id_medida_tra`),
  KEY `tipos_tractivos_id_medida_res_foreign` (`id_medida_res`),
  KEY `tipos_tractivos_id_tipo_combustible_foreign` (`id_tipo_combustible`),
  KEY `tipos_tractivos_id_lubricante_motor_foreign` (`id_lubricante_motor`),
  KEY `tipos_tractivos_id_lubricante_cubo_foreign` (`id_lubricante_cubo`),
  KEY `tipos_tractivos_id_tipo_mantenimiento_foreign` (`id_tipo_mantenimiento`),
  CONSTRAINT `tipos_tractivos_id_lubricante_cubo_foreign` FOREIGN KEY (`id_lubricante_cubo`) REFERENCES `tipos_lubricantes` (`id`),
  CONSTRAINT `tipos_tractivos_id_lubricante_motor_foreign` FOREIGN KEY (`id_lubricante_motor`) REFERENCES `tipos_lubricantes` (`id`),
  CONSTRAINT `tipos_tractivos_id_marca_foreign` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id`),
  CONSTRAINT `tipos_tractivos_id_medida_del_foreign` FOREIGN KEY (`id_medida_del`) REFERENCES `medidas_neumaticos` (`id`),
  CONSTRAINT `tipos_tractivos_id_medida_res_foreign` FOREIGN KEY (`id_medida_res`) REFERENCES `medidas_neumaticos` (`id`),
  CONSTRAINT `tipos_tractivos_id_medida_tra_foreign` FOREIGN KEY (`id_medida_tra`) REFERENCES `medidas_neumaticos` (`id`),
  CONSTRAINT `tipos_tractivos_id_modelo_foreign` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id`),
  CONSTRAINT `tipos_tractivos_id_pais_foreign` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id`),
  CONSTRAINT `tipos_tractivos_id_tipo_combustible_foreign` FOREIGN KEY (`id_tipo_combustible`) REFERENCES `tipos_combustibles` (`id`),
  CONSTRAINT `tipos_tractivos_id_tipo_mantenimiento_foreign` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipos_mantenimiento` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_ubicacion_defensa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_ubicacion_defensa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_vehiculos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_vehiculos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tractivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tractivos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `placa` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tipo_vehiculo` bigint unsigned DEFAULT NULL,
  `id_motor` bigint unsigned DEFAULT NULL,
  `id_caja` bigint unsigned DEFAULT NULL,
  `id_diferencial` bigint unsigned DEFAULT NULL,
  `id_grupo` bigint unsigned DEFAULT NULL,
  `id_tipo_servicio` bigint unsigned DEFAULT NULL,
  `id_color_primario` bigint unsigned DEFAULT NULL,
  `id_color_secundario` bigint unsigned DEFAULT NULL,
  `id_tipo_estado` bigint unsigned DEFAULT NULL,
  `id_lubricante_hidraulico` bigint unsigned DEFAULT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anno` int DEFAULT NULL,
  `color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vin` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nro_carroceria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nro_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nro_resolucion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tara` decimal(10,2) DEFAULT NULL,
  `cap_deposito` decimal(10,2) DEFAULT NULL,
  `cap_hidraulico` decimal(10,2) DEFAULT NULL,
  `cta_combustible` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `indice_consumo` decimal(12,2) DEFAULT NULL,
  `indice_aceite` decimal(12,2) DEFAULT NULL,
  `numero_motor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_chasis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_caja` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacidad_toneladas` decimal(8,2) DEFAULT NULL,
  `capacidad_m3` decimal(8,2) DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_alta` date DEFAULT NULL,
  `fecha_baja` date DEFAULT NULL,
  `gps` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kilometraje_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kms_disp` decimal(12,2) DEFAULT NULL,
  `kms_plan_mtto` int DEFAULT NULL,
  `plan_comb` decimal(12,2) DEFAULT NULL,
  `plan_tn` decimal(12,2) DEFAULT NULL,
  `plan_viajes` decimal(12,2) DEFAULT NULL,
  `plan_gastos` decimal(12,2) DEFAULT NULL,
  `plan_cdt` decimal(12,2) DEFAULT NULL,
  `plan_diario` decimal(12,2) DEFAULT NULL,
  `ficav` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `femision_ficav` date DEFAULT NULL,
  `fvence_ficav` date DEFAULT NULL,
  `lot` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `femision_lot` date DEFAULT NULL,
  `fvence_lot` date DEFAULT NULL,
  `circulacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `femision_circ` date DEFAULT NULL,
  `fvence_circ` date DEFAULT NULL,
  `f_reconstruccion` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_entidad` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tractivos_placa_unique` (`placa`),
  UNIQUE KEY `tractivos_codigo_unique` (`codigo`),
  KEY `tractivos_placa_index` (`placa`),
  KEY `tractivos_estado_index` (`estado`),
  KEY `tractivos_id_tipo_vehiculo_index` (`id_tipo_vehiculo`),
  KEY `tractivos_id_entidad_foreign` (`id_entidad`),
  KEY `tractivos_id_motor_foreign` (`id_motor`),
  KEY `tractivos_id_caja_foreign` (`id_caja`),
  KEY `tractivos_id_diferencial_foreign` (`id_diferencial`),
  KEY `tractivos_id_color_primario_foreign` (`id_color_primario`),
  KEY `tractivos_id_color_secundario_foreign` (`id_color_secundario`),
  KEY `tractivos_id_lubricante_hidraulico_foreign` (`id_lubricante_hidraulico`),
  KEY `tractivos_id_grupo_index` (`id_grupo`),
  KEY `tractivos_id_tipo_servicio_index` (`id_tipo_servicio`),
  KEY `tractivos_id_tipo_estado_index` (`id_tipo_estado`),
  CONSTRAINT `tractivos_id_caja_foreign` FOREIGN KEY (`id_caja`) REFERENCES `cajas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_color_primario_foreign` FOREIGN KEY (`id_color_primario`) REFERENCES `colores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_color_secundario_foreign` FOREIGN KEY (`id_color_secundario`) REFERENCES `colores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_diferencial_foreign` FOREIGN KEY (`id_diferencial`) REFERENCES `diferenciales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_grupo_foreign` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_lubricante_hidraulico_foreign` FOREIGN KEY (`id_lubricante_hidraulico`) REFERENCES `lubricantes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_motor_foreign` FOREIGN KEY (`id_motor`) REFERENCES `motores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_tipo_estado_foreign` FOREIGN KEY (`id_tipo_estado`) REFERENCES `estados_componentes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_tipo_servicio_foreign` FOREIGN KEY (`id_tipo_servicio`) REFERENCES `tipos_servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tractivos_id_tipo_vehiculo_foreign` FOREIGN KEY (`id_tipo_vehiculo`) REFERENCES `tipos_tractivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hora_entrada` time NOT NULL,
  `hora_salida` time NOT NULL,
  `dias_descanso` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `turnos_codigo_unique` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `turnos_comerciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnos_comerciales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `id_entidad` bigint unsigned DEFAULT NULL,
  `fecha_operaciones` date DEFAULT NULL,
  `idgrupo` bigint unsigned DEFAULT NULL,
  `bloqueado` tinyint(1) NOT NULL DEFAULT '0',
  `intentos_fallidos` tinyint unsigned NOT NULL DEFAULT '0',
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_cambio_password` datetime DEFAULT NULL,
  `password_temporal` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_id_entidad_foreign` (`id_entidad`),
  CONSTRAINT `users_id_entidad_foreign` FOREIGN KEY (`id_entidad`) REFERENCES `entidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vacaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_chofer` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `dias` int NOT NULL DEFAULT '0',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vacaciones_id_chofer_foreign` (`id_chofer`),
  CONSTRAINT `vacaciones_id_chofer_foreign` FOREIGN KEY (`id_chofer`) REFERENCES `choferes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_bolsa` bigint unsigned DEFAULT NULL,
  `id_tractivo` bigint unsigned DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'almacen, combustible, repuesto',
  `concepto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitido',
  `id_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vales_numero_unique` (`numero`),
  KEY `vales_id_bolsa_foreign` (`id_bolsa`),
  KEY `vales_id_tractivo_foreign` (`id_tractivo`),
  KEY `vales_id_user_foreign` (`id_user`),
  KEY `vales_fecha_emision_index` (`fecha_emision`),
  KEY `vales_estado_index` (`estado`),
  CONSTRAINT `vales_id_bolsa_foreign` FOREIGN KEY (`id_bolsa`) REFERENCES `bolsa` (`id`),
  CONSTRAINT `vales_id_tractivo_foreign` FOREIGN KEY (`id_tractivo`) REFERENCES `tractivos` (`id`),
  CONSTRAINT `vales_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vallas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vallas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_nave` bigint unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vallas_codigo_unique` (`codigo`),
  KEY `vallas_id_nave_foreign` (`id_nave`),
  CONSTRAINT `vallas_id_nave_foreign` FOREIGN KEY (`id_nave`) REFERENCES `naves` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_01_000005_create_menu_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_01_01_000010_create_seguridad_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_01_01_000020_create_comercial_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_01_01_000030_create_tecnico_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_01_01_000040_create_rrhh_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_01_01_000050_create_operaciones_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_01_01_000060_create_contabilidad_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_01_01_000070_create_taller_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_07_22_184839_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_07_22_200156_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_07_22_201737_create_pizarra_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_07_22_202600_create_comercial_tables_parte4',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_07_22_202719_create_tecnico_tables_parte2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_07_22_204222_create_taller_tables_parte2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_07_22_205647_create_comercial_tables_parte2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_07_22_213000_create_facturacion_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_07_22_220000_create_rrhh_tables_parte2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_07_22_223000_create_contabilidad_tables_parte2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_07_22_224000_create_contabilidad_tables_parte3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_07_22_224400_create_catalogos_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_07_22_224500_create_rrhh_catalog_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_07_22_225000_create_rrhh_tables_parte3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_07_22_225500_create_comercial_tables_parte3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_07_22_231000_create_tec_tables_parte3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_07_22_232000_create_atm_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_07_22_233000_create_rrhh_tables_parte4',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_07_22_235000_create_misc_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_07_23_000001_create_reportes_legacy_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_07_23_000002_create_mantenimiento_ciclos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_07_23_000003_create_motivos_espera_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_07_23_000004_create_cierres_cdt_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_07_23_000005_create_sub_tipos_roturas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_07_23_000006_create_planes_mantenimiento_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_07_24_151800_add_codigo_to_bolsa_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_07_26_160000_add_abreviatura_to_entidades_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_07_26_160100_entidad_en_users_y_pivote',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_07_26_160200_repoint_id_unidad_a_entidades',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_07_27_000000_cleanup_bloque_c',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_07_27_010000_add_legacy_fields_to_entidades_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_07_27_230242_add_parent_id_and_es_matriz_to_entidades_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_07_27_231500_add_licencia_vencimiento_to_entidades_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_07_28_170000_create_catalogo_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_07_28_180000_create_catalogo_tipos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_07_28_180153_add_id_entidad_to_consecutivos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_07_28_193036_add_id_entidad_to_configuraciones_modelo_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_07_28_194500_add_id_entidad_to_tipos_modelo_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_07_28_200000_alter_tipos_modelo_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_07_28_200100_alter_configuraciones_modelo_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_07_29_120000_add_origen_id_to_catalogo_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_07_29_184558_drop_old_tarifas_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_07_29_195117_add_activo_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_07_29_224241_create_configuraciones_tarifa_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_07_30_120000_fix_orphan_foreign_keys',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_07_30_121000_add_id_entidad_to_grupos_escala',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_07_30_130000_add_fields_to_tipos_penalizaciones',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_07_30_150000_add_dias_laborables_sin_sabado_to_meses',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_07_30_201833_add_id_entidad_to_tipos_penalizaciones',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_07_30_232746_change_dias_type_to_string_in_meses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_07_30_233000_add_activo_to_meses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_07_31_005746_add_id_entidad_to_entity_scoped_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_07_31_010000_drop_unused_legacy_catalog_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_07_31_013000_add_extra_fields_to_tipos_incidencias',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_07_31_020000_add_legacy_fields_to_clientes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_07_31_021000_add_legacy_fields_to_tarjetas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_07_31_022000_fix_tractivos_tipo_vehiculo_fk',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_07_31_023000_make_motores_id_tractivo_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_07_31_024000_add_legacy_fields_to_diferenciales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_07_31_025000_add_legacy_fields_to_motores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_07_31_026000_add_legacy_fields_to_neumaticos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_07_31_027000_add_sin_tipo_tipo10_and_nullable_cajas',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_07_31_028000_make_baterias_id_tractivo_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_07_31_029000_drop_id_marca_from_modelos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_08_01_030000_add_entidad_to_talleres_and_taller_to_naves',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_08_01_031000_drop_penalizacuc_from_tipos_incidencias',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_08_01_032000_add_id_entidad_to_firmas',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_08_03_033000_add_rrhh_fields_to_bolsa',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_08_04_034000_add_legacy_fields_to_cargos',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_08_04_035000_align_incidencias_penalizaciones_with_legacy',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_08_05_000000_restructure_acuerdos_and_lugares_legacy_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_08_05_000100_add_licencia_fields_to_bolsa',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_08_05_033000_add_legacy_fields_to_tractivos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_08_05_034000_add_legacy_fields_to_tipos_arrastres_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_08_05_035000_rename_tractivos_f_uraconstruccion',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_08_06_000001_ampliar_hojas_ruta',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_08_06_000002_hojas_ruta_numero_no_unico',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_08_06_000003_hojas_ruta_id_tractivo_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_08_06_165000_add_id_entidad_to_arrastres_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_08_06_170000_add_id_area_to_bolsa_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_08_07_185000_mover_arrastres_a_tractivos',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_08_07_190000_add_id_tipo_mantenimiento_to_tipos_tractivos_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_08_07_201000_add_campos_ciclo_to_tipos_mantenimiento_table',6);
