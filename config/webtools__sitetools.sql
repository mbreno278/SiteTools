-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 08/11/2026 às 15:51
-- Versão do servidor: 8.4.7
-- Versão do PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `webtools__sitetools`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_activity_logs`
--

DROP TABLE IF EXISTS `admin_activity_logs`;
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_activity_created` (`created_at`),
  KEY `idx_admin_activity_user` (`admin_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admin_activity_logs`
--

INSERT INTO `admin_activity_logs` (`id`, `admin_user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-02 18:52:26'),
(2, 1, 'logout', 'Logout administrativo realizado.', '::1', '2026-11-02 18:55:58'),
(3, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-02 18:56:01'),
(4, 1, 'logout', 'Logout administrativo realizado.', '::1', '2026-11-02 19:11:05'),
(5, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-02 19:11:22'),
(6, 1, 'logout', 'Logout administrativo realizado.', '::1', '2026-11-02 19:11:24'),
(7, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-02 19:11:34'),
(8, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-06 17:44:53'),
(9, 1, 'logout', 'Logout administrativo realizado.', '::1', '2026-11-06 17:52:52'),
(10, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-06 17:53:02'),
(11, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:09:49'),
(12, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:10:10'),
(13, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:10:19'),
(14, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:15:21'),
(15, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:15:46'),
(16, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:20:49'),
(17, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:27:47'),
(18, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:28:09'),
(19, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:28:29'),
(20, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:28:52'),
(21, 1, 'tool_update', 'Atualizou ferramenta #20 (Word para PDF)', '::1', '2026-11-06 18:29:11'),
(22, 1, 'settings_update', 'Atualizou configurações do site', '::1', '2026-11-06 18:38:58'),
(23, 1, 'settings_update', 'Atualizou configurações do site', '::1', '2026-11-06 18:39:15'),
(24, 1, 'settings_update', 'Atualizou configurações do site', '::1', '2026-11-06 18:39:19'),
(25, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-07 15:56:51'),
(26, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-07 15:59:02'),
(27, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-07 16:36:17'),
(28, 1, 'tool_update', 'Atualizou ferramenta #6 (Image Cropper)', '::1', '2026-11-07 16:36:57'),
(29, 1, 'tool_update', 'Atualizou ferramenta #6 (Image Cropper)', '::1', '2026-11-07 16:37:09'),
(30, 1, 'tool_update', 'Atualizou ferramenta #14 (Separar PDF)', '::1', '2026-11-07 16:37:26'),
(31, 1, 'tool_update', 'Atualizou ferramenta #13 (Unir PDFs)', '::1', '2026-11-07 16:37:40'),
(32, 1, 'tool_update', 'Atualizou ferramenta #12 (Compactar PDF)', '::1', '2026-11-07 16:37:52'),
(33, 1, 'tool_update', 'Atualizou ferramenta #9 (Download de Áudio do YouTube)', '::1', '2026-11-07 16:38:19'),
(34, 1, 'tool_update', 'Atualizou ferramenta #8 (Download de Vídeo do YouTube)', '::1', '2026-11-07 16:38:32'),
(35, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-08 12:43:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Matheus Breno', 'mbreno278@gmail.com', '$2y$10$JlQTgWjPL.qJJ2s.irAqIe7DNHsIEcQIqIlv9IREgnx5d4ew/aGIC', 'super_admin', 1, NULL, '2026-11-02 18:32:26', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `advertisements`
--

DROP TABLE IF EXISTS `advertisements`;
CREATE TABLE IF NOT EXISTS `advertisements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placement` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` mediumtext COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `backups`
--

DROP TABLE IF EXISTS `backups`;
CREATE TABLE IF NOT EXISTS `backups` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'database',
  `size_bytes` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Texto', 'texto', NULL, 'text_fields', 0, 1, '2026-11-06 20:28:01', NULL),
(2, 'Imagem', 'imagem', NULL, 'image', 0, 1, '2026-11-06 20:28:01', NULL),
(3, 'PDF e documentos', 'pdf', NULL, 'picture_as_pdf', 0, 1, '2026-11-06 20:28:01', NULL),
(4, 'Downloads', 'downloads', NULL, 'download', 0, 1, '2026-11-06 20:28:01', NULL),
(8, 'Image', 'image', NULL, 'category', 0, 1, '2026-11-06 20:19:46', NULL),
(9, 'Document', 'document', NULL, 'category', 0, 1, '2026-11-06 20:19:46', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'error',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_error_logs_created` (`created_at`),
  KEY `idx_error_logs_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
CREATE TABLE IF NOT EXISTS `file_uploads` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED DEFAULT NULL,
  `tool_id` int UNSIGNED DEFAULT NULL,
  `tool_slug` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'temporary',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_file_uploads_status` (`status`),
  KEY `idx_file_uploads_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_email_time` (`email`,`attempted_at`),
  KEY `idx_login_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `payment_events`
--

DROP TABLE IF EXISTS `payment_events`;
CREATE TABLE IF NOT EXISTS `payment_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` bigint UNSIGNED NOT NULL,
  `event_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'created|paid|failed|refunded|cancelled|access_granted|access_revoked',
  `actor` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT 'system|webhook|admin|user',
  `payload` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pe_payment` (`payment_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `payment_webhook_logs`
--

DROP TABLE IF EXISTS `payment_webhook_logs`;
CREATE TABLE IF NOT EXISTS `payment_webhook_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `processing_status` enum('pending','processed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_id` bigint UNSIGNED DEFAULT NULL,
  `error_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_valid` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_event` (`provider`,`event_id`),
  KEY `idx_webhook_status` (`processing_status`),
  KEY `idx_webhook_payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schema_version` (`version`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `version`, `description`, `applied_at`) VALUES
(1, '001', 'Tabela schema_migrations', '2026-11-06 20:18:26'),
(2, '002', 'Padronizar user_id/tool_id para INT UNSIGNED', '2026-11-06 20:18:51'),
(3, '003', 'Foreign keys com políticas de exclusão seguras', '2026-11-06 20:19:23'),
(4, '004', 'Consolidar categories como fonte principal', '2026-11-06 20:19:47'),
(5, '005', 'Sincronizar access_type com tool_access_rules', '2026-11-06 20:20:08'),
(6, '006', 'Pagamentos: colunas extras, eventos e webhooks', '2026-11-06 20:20:30'),
(7, '007', 'Histórico, índices e limites padrão', '2026-11-06 20:20:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'SiteTools', NULL),
(2, 'site_tagline', 'Ferramentas online para o seu dia a dia', NULL),
(3, 'support_email', '', NULL),
(4, 'maintenance_mode', '0', '2026-11-06 18:39:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_support_status` (`status`),
  KEY `idx_support_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tools`
--

DROP TABLE IF EXISTS `tools`;
CREATE TABLE IF NOT EXISTS `tools` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `icon` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT 'build',
  `handler` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 = desligada no admin (bloqueio total)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'Operacional: active|development|unavailable',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_beta` tinyint(1) NOT NULL DEFAULT '0',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_file_size` int UNSIGNED DEFAULT NULL,
  `access_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'Espelho de tool_access_rules.access_mode: free|login|paid|temporary',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_tools_category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tools`
--

INSERT INTO `tools` (`id`, `name`, `slug`, `description`, `category`, `category_id`, `icon`, `handler`, `meta_title`, `meta_description`, `is_active`, `status`, `sort_order`, `is_featured`, `is_beta`, `version`, `max_file_size`, `access_type`, `created_at`, `updated_at`) VALUES
(1, 'Contador de Palavras', 'contador-de-palavras', 'Conte palavras e caracteres de um texto.', 'texto', 1, 'text_fields', 'word-counter', 'Contador de Palavras Online Grátis', 'Conte palavras, caracteres e espaços online gratuitamente.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-22 19:21:38', NULL),
(2, 'Redimensionar Imagem', 'redimensionar-imagem', 'Altere as dimensões de suas imagens.', 'imagem', 2, 'photo_size_select_large', 'image-resizer', 'Redimensionar Imagem Online', 'Redimensione imagens online de maneira simples.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-22 19:21:38', NULL),
(4, 'Image Compressor', 'image-compressor', 'Compress JPG, PNG and WebP images online. Adjust image quality, compare file sizes and download compressed images.', 'imagem', 2, 'compress', 'image-compressor', 'Image Compressor Online - SiteTools', 'Compress JPG, PNG and WebP images online. Adjust image quality, compare file sizes and download compressed images.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-25 18:59:23', '2026-11-06 23:19:47'),
(5, 'Image Converter', 'image-converter', 'Convert images online between JPG, PNG and WebP formats. Preview your image and download the converted file for free.', 'imagem', 2, 'sync_alt', 'image-converter', 'Image Converter Online - SiteTools', 'Convert images online between JPG, PNG and WebP formats. Preview your image and download the converted file for free.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-25 19:12:16', NULL),
(6, 'Image Cropper', 'image-cropper', 'Crop images online with a free interactive image cropping tool. Adjust the crop area, choose aspect ratios and download your cropped image.', 'imagem', 2, 'crop', 'image-cropper', 'Image Cropper Online - SiteTools', 'Crop JPG, PNG and WebP images online. Choose custom crop areas and aspect ratios, then download your cropped image for free.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-27 21:49:34', '2026-11-07 19:37:08'),
(7, 'Converter PDF para WORD', 'pdf-to-word', 'Converta os seus arquivos PDFs para WORD.', 'pdf', 3, 'build', 'pdf-to-word', 'Converter PDF para WORD', 'Converta os seus arquivos PDFs para WORD.', 1, 'development', 0, 0, 0, NULL, NULL, 'free', '2026-10-27 23:23:49', '2026-11-06 23:19:47'),
(8, 'Download de Vídeo do YouTube', 'download-video-youtube', 'Baixe vídeos do YouTube em diferentes qualidades e formatos, de forma rápida e simples.', 'downloads', 4, 'smart_display', 'download-video-youtube', 'Download de Vídeo do YouTube | SiteTools', 'Baixe vídeos do YouTube em MP4 e outros formatos. Escolha a qualidade desejada e faça o download facilmente.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-29 02:12:40', '2026-11-07 19:38:32'),
(9, 'Download de Áudio do YouTube', 'download-audio-youtube', 'Extraia e baixe apenas o áudio de vídeos do YouTube em MP3, M4A e outros formatos.', 'downloads', 4, 'headphones', 'download-audio-youtube', 'Download de Áudio do YouTube | SiteTools', 'Baixe o áudio de vídeos do YouTube em MP3 e outros formatos de alta qualidade.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-29 22:12:47', '2026-11-07 19:38:19'),
(10, 'Download de Vídeo do Instagram', 'download-video-instagram', 'Baixe Reels, posts com vídeo, IGTV e shorts do Instagram de forma rápida e simples.', 'downloads', 4, 'photo_camera', 'download-video-instagram', 'Download de Vídeo do Instagram | SiteTools', 'Baixe Reels, vídeos e shorts do Instagram em MP4. Rápido, simples e gratuito.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 00:28:28', '2026-10-30 00:28:28'),
(11, 'Download de Vídeo do TikTok', 'download-video-tiktok', 'Baixe vídeos do TikTok em diferentes qualidades, com ou sem áudio, de forma rápida e simples.', 'downloads', 4, 'smart_display', 'download-video-tiktok', 'Download de Vídeo do TikTok | SiteTools', 'Baixe vídeos do TikTok em MP4 com áudio. Rápido, simples e gratuito.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 01:23:28', '2026-10-30 01:23:28'),
(12, 'Compactar PDF', 'compactar-pdf', 'Reduza o tamanho de documentos PDF mantendo a qualidade legível.', 'pdf', 3, 'compress', 'compactar-pdf', 'Compactar PDF | SiteTools', 'Comprima arquivos PDF online de forma rápida e simples.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 02:01:15', '2026-11-07 19:37:52'),
(13, 'Unir PDFs', 'unir-pdf', 'Combine vários documentos PDF em um único arquivo.', 'pdf', 3, 'merge', 'unir-pdf', 'Unir PDFs | SiteTools', 'Junte vários PDFs em um só arquivo online.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 02:01:15', '2026-11-07 19:37:40'),
(14, 'Separar PDF', 'separar-pdf', 'Extraia páginas de um PDF em arquivos separados.', 'pdf', 3, 'call_split', 'separar-pdf', 'Separar PDF | SiteTools', 'Separe páginas de um PDF em arquivos individuais.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 02:01:15', '2026-11-07 19:37:26'),
(15, 'Proteger PDF', 'proteger-pdf', 'Adicione senha de proteção ao seu documento PDF.', 'pdf', 3, 'lock', 'proteger-pdf', 'Proteger PDF | SiteTools', 'Bloqueie seu PDF com senha de forma rápida.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(16, 'Desbloquear PDF', 'desbloquear-pdf', 'Remova a senha de um PDF protegido (é necessário conhecer a senha).', 'pdf', 3, 'lock_open', 'desbloquear-pdf', 'Desbloquear PDF | SiteTools', 'Desbloqueie PDFs protegidos com a senha correta.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(17, 'Formatar Texto', 'formatar-texto', 'Converta texto em maiúsculas, minúsculas, título, camelCase, snake_case e outros formatos.', 'texto', 1, 'text_fields', 'formatar-texto', 'Formatar Texto | SiteTools', 'Transforme texto em maiúsculas, minúsculas e vários outros formatos online.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-31 00:17:42', '2026-10-31 00:17:42'),
(18, 'Remover Espaços', 'remover-espacos', 'Remova espaços desnecessários, linhas em branco e organize seu texto automaticamente.', 'texto', 1, 'space_bar', 'remover-espacos', 'Remover Espaços | SiteTools', 'Limpe espaços extras e organize textos online de forma rápida.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-31 00:32:58', '2026-10-31 00:32:58'),
(19, 'Gerador Lorem Ipsum', 'gerador-lorem', 'Gere textos de exemplo (Lorem Ipsum ou português) para preencher layouts e protótipos.', 'texto', 1, 'notes', 'gerador-lorem', 'Gerador Lorem Ipsum | SiteTools', 'Gere parágrafos, frases ou palavras de Lorem Ipsum e texto fictício em português.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-10-31 00:44:58', '2026-10-31 00:44:58'),
(20, 'Word para PDF', 'word-to-pdf', 'Converta documentos Word (DOC/DOCX) em arquivos PDF.', 'pdf', 3, 'picture_as_pdf', 'word-to-pdf', 'Word para PDF | SiteTools', 'Converta DOC e DOCX para PDF online de forma rápida.', 1, 'active', 0, 0, 0, NULL, NULL, 'free', '2026-11-02 16:26:46', '2026-11-06 21:29:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_access_rules`
--

DROP TABLE IF EXISTS `tool_access_rules`;
CREATE TABLE IF NOT EXISTS `tool_access_rules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED NOT NULL,
  `access_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'Regra de autorização: free|login|paid|temporary',
  `guest_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tool_access_rule_tool` (`tool_id`),
  KEY `idx_tool_access_mode` (`access_mode`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tool_access_rules`
--

INSERT INTO `tool_access_rules` (`id`, `tool_id`, `access_mode`, `guest_allowed`, `created_at`, `updated_at`) VALUES
(1, 1, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(2, 2, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(3, 4, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(4, 5, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(5, 6, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(6, 7, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(7, 8, 'free', 1, '2026-11-06 16:49:26', '2026-11-07 16:38:32'),
(8, 9, 'free', 1, '2026-11-06 16:49:26', '2026-11-07 16:38:19'),
(9, 10, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(10, 11, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(11, 12, 'free', 1, '2026-11-06 16:49:26', '2026-11-07 16:37:52'),
(12, 13, 'free', 1, '2026-11-06 16:49:26', '2026-11-07 16:37:40'),
(13, 14, 'free', 1, '2026-11-06 16:49:26', '2026-11-07 16:37:26'),
(14, 15, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(15, 16, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(16, 17, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(17, 18, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(18, 19, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30'),
(19, 20, 'free', 1, '2026-11-06 16:49:26', '2026-11-06 17:28:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_processing_jobs`
--

DROP TABLE IF EXISTS `tool_processing_jobs`;
CREATE TABLE IF NOT EXISTS `tool_processing_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED DEFAULT NULL,
  `tool_slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `input_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('queued','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `progress` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `error_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_json` json DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_status` (`status`,`created_at`),
  KEY `idx_jobs_user` (`user_id`),
  KEY `idx_jobs_tool` (`tool_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_reviews`
--

DROP TABLE IF EXISTS `tool_reviews`;
CREATE TABLE IF NOT EXISTS `tool_reviews` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_user_tool` (`user_id`,`tool_id`),
  KEY `idx_reviews_tool` (`tool_id`,`is_approved`)
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_usage`
--

DROP TABLE IF EXISTS `tool_usage`;
CREATE TABLE IF NOT EXISTS `tool_usage` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED DEFAULT NULL,
  `tool_slug` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `processing_time_ms` int UNSIGNED DEFAULT NULL,
  `file_size_bytes` bigint UNSIGNED DEFAULT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tool_usage_tool` (`tool_id`),
  KEY `idx_tool_usage_created` (`created_at`),
  KEY `idx_tool_usage_status` (`status`),
  KEY `idx_tu_created_tool` (`created_at`,`tool_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_usage_counters`
--

DROP TABLE IF EXISTS `tool_usage_counters`;
CREATE TABLE IF NOT EXISTS `tool_usage_counters` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED NOT NULL,
  `subject_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guest' COMMENT 'guest|user',
  `subject_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'user_id ou hash(guest)',
  `period_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily' COMMENT 'daily|monthly|lifetime',
  `period_key` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: 2026-11-06',
  `use_count` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_counter` (`tool_id`,`subject_type`,`subject_key`,`period_type`,`period_key`),
  KEY `idx_counter_lookup` (`tool_id`,`subject_key`,`period_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tool_usage_counters`
--

INSERT INTO `tool_usage_counters` (`id`, `tool_id`, `subject_type`, `subject_key`, `period_type`, `period_key`, `use_count`, `updated_at`) VALUES
(1, 12, 'user', '1', 'daily', '2026-11-08', 4, '2026-11-08 10:10:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_usage_limits`
--

DROP TABLE IF EXISTS `tool_usage_limits`;
CREATE TABLE IF NOT EXISTS `tool_usage_limits` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` int UNSIGNED NOT NULL,
  `user_type` enum('guest','registered','paid') COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_uses` int UNSIGNED DEFAULT NULL,
  `period` enum('hourly','daily','monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tool_user_period` (`tool_id`,`user_type`,`period`),
  KEY `idx_tul_tool` (`tool_id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tool_usage_limits`
--

INSERT INTO `tool_usage_limits` (`id`, `tool_id`, `user_type`, `max_uses`, `period`, `created_at`) VALUES
(1, 12, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(2, 1, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(3, 16, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(4, 9, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(5, 10, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(6, 11, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(7, 8, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(8, 17, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(9, 19, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(10, 4, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(11, 5, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(12, 6, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(13, 7, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(14, 15, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(15, 2, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(16, 18, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(17, 14, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(18, 13, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(19, 20, 'guest', 5, 'daily', '2026-11-06 17:28:54'),
(32, 12, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(33, 1, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(34, 16, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(35, 9, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(36, 10, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(37, 11, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(38, 8, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(39, 17, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(40, 19, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(41, 4, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(42, 5, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(43, 6, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(44, 7, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(45, 15, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(46, 2, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(47, 18, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(48, 14, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(49, 13, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(50, 20, 'registered', 20, 'daily', '2026-11-06 17:28:54'),
(63, 12, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(64, 1, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(65, 16, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(66, 9, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(67, 10, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(68, 11, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(69, 8, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(70, 17, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(71, 19, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(72, 4, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(73, 5, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(74, 6, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(75, 7, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(76, 15, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(77, 2, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(78, 18, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(79, 14, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(80, 13, 'paid', 200, 'daily', '2026-11-06 17:28:54'),
(81, 20, 'paid', 200, 'daily', '2026-11-06 17:28:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `verify_token` varchar(64) DEFAULT NULL,
  `verify_token_expires_at` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `is_active`, `last_login_at`, `deleted_at`, `created_at`, `updated_at`, `email_verified_at`, `verify_token`, `verify_token_expires_at`, `reset_token`, `reset_token_expires_at`) VALUES
(1, 'Matheus Breno', 'mbreno278@gmail.com', '$2y$10$d8s983waNQCAAw9R0trlVOKoCfHUfuniULnx/5yfrsPXXapUEf8xq', 1, '2026-11-08 10:09:02', NULL, '2026-11-02 16:47:56', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Breno Sousa', 'mbreno289@gmail.com', '$2y$10$LTf4l2eaVcWKGFraYy5mlOeyLmPosBjrqilm1ma5BHgqEX8aEXIC2', 1, NULL, NULL, '2026-11-02 17:29:15', NULL, NULL, '15f197b53f06e46c4fefc695245871015022f0c479be6a65d2db1ef600d08065', '2026-11-02 21:29:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_activity_logs`
--

DROP TABLE IF EXISTS `user_activity_logs`;
CREATE TABLE IF NOT EXISTS `user_activity_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ual_user` (`user_id`,`created_at`),
  KEY `idx_ual_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_favorites`
--

DROP TABLE IF EXISTS `user_favorites`;
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `tool_id` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_favorite` (`user_id`,`tool_id`),
  KEY `idx_favorites_user` (`user_id`),
  KEY `fk_fav_tool` (`tool_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_favorites`
--

INSERT INTO `user_favorites` (`id`, `user_id`, `tool_id`, `created_at`) VALUES
(7, 1, 10, '2026-11-07 12:41:26'),
(8, 1, 9, '2026-11-07 12:41:29'),
(20, 1, 12, '2026-11-07 12:43:12'),
(21, 1, 1, '2026-11-07 12:43:13'),
(22, 1, 16, '2026-11-07 12:43:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_files`
--

DROP TABLE IF EXISTS `user_files`;
CREATE TABLE IF NOT EXISTS `user_files` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `tool_id` int UNSIGNED DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('available','deleted','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_files` (`user_id`,`status`,`created_at`),
  KEY `idx_user_files_expiry` (`expires_at`),
  KEY `fk_files_tool` (`tool_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_payments`
--

DROP TABLE IF EXISTS `user_payments`;
CREATE TABLE IF NOT EXISTS `user_payments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `tool_id` int UNSIGNED NOT NULL,
  `provider` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_transaction_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_reference` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `status` enum('pending','paid','failed','refunded','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `access_days` smallint UNSIGNED DEFAULT NULL COMMENT 'Dias de acesso concedidos após paid',
  `granted_access_id` bigint UNSIGNED DEFAULT NULL COMMENT 'FK lógica para user_tool_access.id',
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_transaction` (`provider`,`provider_transaction_id`),
  KEY `idx_user_payments` (`user_id`,`created_at`),
  KEY `idx_payment_status` (`status`),
  KEY `fk_pay_tool` (`tool_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_id` (`session_id`),
  KEY `idx_user_sessions_user` (`user_id`),
  KEY `idx_user_sessions_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `created_at`, `last_activity`, `expires_at`) VALUES
(8, 1, 'g26ag7u6trgodi29frk3hhi7d0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-06 16:00:38', '2026-11-06 16:00:38', '2026-11-06 21:00:38'),
(9, 1, '9a4904i0btjg1poquun65qvg2c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-06 17:10:04', '2026-11-06 17:10:04', '2026-11-06 22:10:04'),
(10, 1, 'qv5vdqv1dtuc11j8b44t754jak', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-06 18:30:54', '2026-11-06 18:30:54', '2026-11-06 23:30:54'),
(11, 1, 'f2o5r5j93f60pbmjm7bf4kvkec', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-06 20:29:09', '2026-11-06 20:29:09', '2026-11-07 01:29:09'),
(12, 1, 'amk27u9tmee2luevi5up40jmbu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-07 12:11:21', '2026-11-07 12:11:21', '2026-11-07 17:11:21'),
(13, 1, 'iajvsat3t2hqhjmuck36ivpqdu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-07 14:34:34', '2026-11-07 14:34:34', '2026-11-07 19:34:34'),
(14, 1, 'avd52trvbrq83t2v8vi9o60kmq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-07 16:39:31', '2026-11-07 16:39:31', '2026-11-07 21:39:31'),
(15, 1, 'g6jfo5k9n48tkld3v8ibcom78l', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-07 19:39:56', '2026-11-07 19:39:56', '2026-11-08 00:39:56'),
(16, 1, 'hfcdtmfm1f2e7rhv9j1gh05t7q', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-08 10:09:02', '2026-11-08 10:09:02', '2026-11-08 15:09:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_tool_access`
--

DROP TABLE IF EXISTS `user_tool_access`;
CREATE TABLE IF NOT EXISTS `user_tool_access` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `tool_id` int UNSIGNED NOT NULL,
  `access_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'temporary' COMMENT 'Origem do grant: paid|temporary|manual|promo',
  `status` enum('active','expired','cancelled','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'Estado do grant individual',
  `starts_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `access_days` smallint UNSIGNED DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revoked_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `payment_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_tool` (`user_id`,`tool_id`),
  KEY `idx_user_status_expiry` (`user_id`,`status`,`expires_at`),
  KEY `idx_access_expiry` (`expires_at`),
  KEY `fk_uta_tool` (`tool_id`),
  KEY `fk_uta_payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_tool_usage`
--

DROP TABLE IF EXISTS `user_tool_usage`;
CREATE TABLE IF NOT EXISTS `user_tool_usage` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED DEFAULT NULL,
  `tool_id` int UNSIGNED DEFAULT NULL,
  `tool_slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('started','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `processing_time_ms` int UNSIGNED DEFAULT NULL,
  `file_size_bytes` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_usage` (`user_id`,`created_at`),
  KEY `idx_user_usage_tool` (`user_id`,`tool_slug`),
  KEY `idx_usage_tool_date` (`tool_id`,`created_at`),
  KEY `idx_utu_status_date` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_tool_usage`
--

INSERT INTO `user_tool_usage` (`id`, `user_id`, `tool_id`, `tool_slug`, `status`, `processing_time_ms`, `file_size_bytes`, `created_at`) VALUES
(1, 1, 6, 'image-cropper', 'completed', 529, 1135347, '2026-11-07 15:37:22');

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `fk_admin_activity_user` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `payment_events`
--
ALTER TABLE `payment_events`
  ADD CONSTRAINT `fk_pe_payment` FOREIGN KEY (`payment_id`) REFERENCES `user_payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `fk_tools_categories_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `tool_access_rules`
--
ALTER TABLE `tool_access_rules`
  ADD CONSTRAINT `fk_tar_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tool_usage`
--
ALTER TABLE `tool_usage`
  ADD CONSTRAINT `fk_tu_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `tool_usage_counters`
--
ALTER TABLE `tool_usage_counters`
  ADD CONSTRAINT `fk_tuc_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tool_usage_limits`
--
ALTER TABLE `tool_usage_limits`
  ADD CONSTRAINT `fk_tul_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `fk_fav_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_files`
--
ALTER TABLE `user_files`
  ADD CONSTRAINT `fk_files_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_payments`
--
ALTER TABLE `user_payments`
  ADD CONSTRAINT `fk_pay_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_sess_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_tool_access`
--
ALTER TABLE `user_tool_access`
  ADD CONSTRAINT `fk_uta_payment` FOREIGN KEY (`payment_id`) REFERENCES `user_payments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uta_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_tool_usage`
--
ALTER TABLE `user_tool_usage`
  ADD CONSTRAINT `fk_utu_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_utu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
