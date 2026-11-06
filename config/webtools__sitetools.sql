-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 06/11/2026 às 20:24
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(7, 1, 'login', 'Login administrativo realizado.', '::1', '2026-11-02 19:11:34');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `tool_slug` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `icon` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT 'build',
  `handler` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `access_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'free|login|paid|development|unavailable',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tools`
--

INSERT INTO `tools` (`id`, `name`, `slug`, `description`, `category`, `icon`, `handler`, `meta_title`, `meta_description`, `is_active`, `access_type`, `created_at`, `updated_at`) VALUES
(1, 'Contador de Palavras', 'contador-de-palavras', 'Conte palavras e caracteres de um texto.', 'texto', 'text_fields', 'word-counter', 'Contador de Palavras Online Grátis', 'Conte palavras, caracteres e espaços online gratuitamente.', 1, 'free', '2026-10-22 19:21:38', NULL),
(2, 'Redimensionar Imagem', 'redimensionar-imagem', 'Altere as dimensões de suas imagens.', 'imagem', 'photo_size_select_large', 'image-resizer', 'Redimensionar Imagem Online', 'Redimensione imagens online de maneira simples.', 1, 'free', '2026-10-22 19:21:38', NULL),
(4, 'Image Compressor', 'image-compressor', 'Compress JPG, PNG and WebP images online. Adjust image quality, compare file sizes and download compressed images.', 'image', 'compress', 'image-compressor', 'Image Compressor Online - SiteTools', 'Compress JPG, PNG and WebP images online. Adjust image quality, compare file sizes and download compressed images.', 1, 'free', '2026-10-25 18:59:23', NULL),
(5, 'Image Converter', 'image-converter', 'Convert images online between JPG, PNG and WebP formats. Preview your image and download the converted file for free.', 'imagem', 'sync_alt', 'image-converter', 'Image Converter Online - SiteTools', 'Convert images online between JPG, PNG and WebP formats. Preview your image and download the converted file for free.', 1, 'free', '2026-10-25 19:12:16', NULL),
(6, 'Image Cropper', 'image-cropper', 'Crop images online with a free interactive image cropping tool. Adjust the crop area, choose aspect ratios and download your cropped image.', 'imagem', 'crop', 'image-cropper', 'Image Cropper Online - SiteTools', 'Crop JPG, PNG and WebP images online. Choose custom crop areas and aspect ratios, then download your cropped image for free.', 1, 'unavailable', '2026-10-27 21:49:34', NULL),
(7, 'Converter PDF para WORD', 'pdf-to-word', 'Converta os seus arquivos PDFs para WORD.', 'document', 'build', 'pdf-to-word', 'Converter PDF para WORD', 'Converta os seus arquivos PDFs para WORD.', 1, 'development', '2026-10-27 23:23:49', NULL),
(8, 'Download de Vídeo do YouTube', 'download-video-youtube', 'Baixe vídeos do YouTube em diferentes qualidades e formatos, de forma rápida e simples.', 'downloads', 'smart_display', 'download-video-youtube', 'Download de Vídeo do YouTube | SiteTools', 'Baixe vídeos do YouTube em MP4 e outros formatos. Escolha a qualidade desejada e faça o download facilmente.', 1, 'paid', '2026-10-29 02:12:40', '2026-10-29 02:12:40'),
(9, 'Download de Áudio do YouTube', 'download-audio-youtube', 'Extraia e baixe apenas o áudio de vídeos do YouTube em MP3, M4A e outros formatos.', 'downloads', 'headphones', 'download-audio-youtube', 'Download de Áudio do YouTube | SiteTools', 'Baixe o áudio de vídeos do YouTube em MP3 e outros formatos de alta qualidade.', 1, 'paid', '2026-10-29 22:12:47', '2026-10-29 22:12:47'),
(10, 'Download de Vídeo do Instagram', 'download-video-instagram', 'Baixe Reels, posts com vídeo, IGTV e shorts do Instagram de forma rápida e simples.', 'downloads', 'photo_camera', 'download-video-instagram', 'Download de Vídeo do Instagram | SiteTools', 'Baixe Reels, vídeos e shorts do Instagram em MP4. Rápido, simples e gratuito.', 1, 'free', '2026-10-30 00:28:28', '2026-10-30 00:28:28'),
(11, 'Download de Vídeo do TikTok', 'download-video-tiktok', 'Baixe vídeos do TikTok em diferentes qualidades, com ou sem áudio, de forma rápida e simples.', 'downloads', 'smart_display', 'download-video-tiktok', 'Download de Vídeo do TikTok | SiteTools', 'Baixe vídeos do TikTok em MP4 com áudio. Rápido, simples e gratuito.', 1, 'free', '2026-10-30 01:23:28', '2026-10-30 01:23:28'),
(12, 'Compactar PDF', 'compactar-pdf', 'Reduza o tamanho de documentos PDF mantendo a qualidade legível.', 'pdf', 'compress', 'compactar-pdf', 'Compactar PDF | SiteTools', 'Comprima arquivos PDF online de forma rápida e simples.', 1, 'login', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(13, 'Unir PDFs', 'unir-pdf', 'Combine vários documentos PDF em um único arquivo.', 'pdf', 'merge', 'unir-pdf', 'Unir PDFs | SiteTools', 'Junte vários PDFs em um só arquivo online.', 1, 'login', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(14, 'Separar PDF', 'separar-pdf', 'Extraia páginas de um PDF em arquivos separados.', 'pdf', 'call_split', 'separar-pdf', 'Separar PDF | SiteTools', 'Separe páginas de um PDF em arquivos individuais.', 1, 'login', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(15, 'Proteger PDF', 'proteger-pdf', 'Adicione senha de proteção ao seu documento PDF.', 'pdf', 'lock', 'proteger-pdf', 'Proteger PDF | SiteTools', 'Bloqueie seu PDF com senha de forma rápida.', 1, 'free', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(16, 'Desbloquear PDF', 'desbloquear-pdf', 'Remova a senha de um PDF protegido (é necessário conhecer a senha).', 'pdf', 'lock_open', 'desbloquear-pdf', 'Desbloquear PDF | SiteTools', 'Desbloqueie PDFs protegidos com a senha correta.', 1, 'free', '2026-10-30 02:01:15', '2026-10-30 02:01:15'),
(17, 'Formatar Texto', 'formatar-texto', 'Converta texto em maiúsculas, minúsculas, título, camelCase, snake_case e outros formatos.', 'texto', 'text_fields', 'formatar-texto', 'Formatar Texto | SiteTools', 'Transforme texto em maiúsculas, minúsculas e vários outros formatos online.', 1, 'free', '2026-10-31 00:17:42', '2026-10-31 00:17:42'),
(18, 'Remover Espaços', 'remover-espacos', 'Remova espaços desnecessários, linhas em branco e organize seu texto automaticamente.', 'texto', 'space_bar', 'remover-espacos', 'Remover Espaços | SiteTools', 'Limpe espaços extras e organize textos online de forma rápida.', 1, 'free', '2026-10-31 00:32:58', '2026-10-31 00:32:58'),
(19, 'Gerador Lorem Ipsum', 'gerador-lorem', 'Gere textos de exemplo (Lorem Ipsum ou português) para preencher layouts e protótipos.', 'texto', 'notes', 'gerador-lorem', 'Gerador Lorem Ipsum | SiteTools', 'Gere parágrafos, frases ou palavras de Lorem Ipsum e texto fictício em português.', 1, 'free', '2026-10-31 00:44:58', '2026-10-31 00:44:58'),
(20, 'Word para PDF', 'word-to-pdf', 'Converta documentos Word (DOC/DOCX) em arquivos PDF.', 'pdf', 'picture_as_pdf', 'word-to-pdf', 'Word para PDF | SiteTools', 'Converta DOC e DOCX para PDF online de forma rápida.', 1, 'free', '2026-11-02 16:26:46', '2026-11-02 16:26:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_access_rules`
--

DROP TABLE IF EXISTS `tool_access_rules`;
CREATE TABLE IF NOT EXISTS `tool_access_rules` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` bigint UNSIGNED NOT NULL,
  `access_mode` enum('free','temporary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free',
  `guest_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tool_access_rule_tool` (`tool_id`),
  KEY `idx_tool_access_mode` (`access_mode`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tool_access_rules`
--

INSERT INTO `tool_access_rules` (`id`, `tool_id`, `access_mode`, `guest_allowed`, `created_at`, `updated_at`) VALUES
(1, 1, 'free', 1, '2026-11-06 16:49:26', NULL),
(2, 2, 'free', 1, '2026-11-06 16:49:26', NULL),
(3, 4, 'free', 1, '2026-11-06 16:49:26', NULL),
(4, 5, 'free', 1, '2026-11-06 16:49:26', NULL),
(5, 6, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(6, 7, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(7, 8, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(8, 9, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(9, 10, 'free', 1, '2026-11-06 16:49:26', NULL),
(10, 11, 'free', 1, '2026-11-06 16:49:26', NULL),
(11, 12, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(12, 13, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(13, 14, '', 0, '2026-11-06 16:49:26', '2026-11-06 17:05:35'),
(14, 15, 'free', 1, '2026-11-06 16:49:26', NULL),
(15, 16, 'free', 1, '2026-11-06 16:49:26', NULL),
(16, 17, 'free', 1, '2026-11-06 16:49:26', NULL),
(17, 18, 'free', 1, '2026-11-06 16:49:26', NULL),
(18, 19, 'free', 1, '2026-11-06 16:49:26', NULL),
(19, 20, 'free', 1, '2026-11-06 16:49:26', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tool_usage`
--

DROP TABLE IF EXISTS `tool_usage`;
CREATE TABLE IF NOT EXISTS `tool_usage` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id` bigint UNSIGNED DEFAULT NULL,
  `tool_slug` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `processing_time_ms` int UNSIGNED DEFAULT NULL,
  `file_size_bytes` bigint UNSIGNED DEFAULT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tool_usage_tool` (`tool_id`),
  KEY `idx_tool_usage_created` (`created_at`),
  KEY `idx_tool_usage_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`, `updated_at`, `email_verified_at`, `verify_token`, `verify_token_expires_at`, `reset_token`, `reset_token_expires_at`) VALUES
(1, 'Matheus Breno', 'mbreno278@gmail.com', '$2y$10$d8s983waNQCAAw9R0trlVOKoCfHUfuniULnx/5yfrsPXXapUEf8xq', '2026-11-02 16:47:56', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Breno Sousa', 'mbreno289@gmail.com', '$2y$10$LTf4l2eaVcWKGFraYy5mlOeyLmPosBjrqilm1ma5BHgqEX8aEXIC2', '2026-11-02 17:29:15', NULL, NULL, '15f197b53f06e46c4fefc695245871015022f0c479be6a65d2db1ef600d08065', '2026-11-02 21:29:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_favorites`
--

DROP TABLE IF EXISTS `user_favorites`;
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `tool_id` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_favorite` (`user_id`,`tool_id`),
  KEY `idx_favorites_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_files`
--

DROP TABLE IF EXISTS `user_files`;
CREATE TABLE IF NOT EXISTS `user_files` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `tool_id` bigint UNSIGNED DEFAULT NULL,
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
  KEY `idx_user_files_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_payments`
--

DROP TABLE IF EXISTS `user_payments`;
CREATE TABLE IF NOT EXISTS `user_payments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `tool_id` bigint UNSIGNED NOT NULL,
  `provider` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_transaction_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `status` enum('pending','paid','failed','refunded','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_transaction` (`provider`,`provider_transaction_id`),
  KEY `idx_user_payments` (`user_id`,`created_at`),
  KEY `idx_payment_status` (`status`)
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `created_at`, `last_activity`, `expires_at`) VALUES
(8, 1, 'g26ag7u6trgodi29frk3hhi7d0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-06 16:00:38', '2026-11-06 16:00:38', '2026-11-06 21:00:38'),
(9, 1, '9a4904i0btjg1poquun65qvg2c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-06 17:10:04', '2026-11-06 17:10:04', '2026-11-06 22:10:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_tool_access`
--

DROP TABLE IF EXISTS `user_tool_access`;
CREATE TABLE IF NOT EXISTS `user_tool_access` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `tool_id` bigint UNSIGNED NOT NULL,
  `access_type` enum('free','temporary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'temporary',
  `status` enum('active','expired','cancelled','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `starts_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `payment_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_tool` (`user_id`,`tool_id`),
  KEY `idx_user_status_expiry` (`user_id`,`status`,`expires_at`),
  KEY `idx_access_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_tool_usage`
--

DROP TABLE IF EXISTS `user_tool_usage`;
CREATE TABLE IF NOT EXISTS `user_tool_usage` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `tool_id` bigint UNSIGNED DEFAULT NULL,
  `tool_slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('started','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `processing_time_ms` int UNSIGNED DEFAULT NULL,
  `file_size_bytes` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_usage` (`user_id`,`created_at`),
  KEY `idx_user_usage_tool` (`user_id`,`tool_slug`),
  KEY `idx_usage_tool_date` (`tool_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `fk_admin_activity_user` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
