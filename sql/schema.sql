-- ============================================
-- SISTEMA DE VOTAÇÃO INSTITUCIONAL
-- Schema MySQL para Câmara
-- ============================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS sistema_votacao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_votacao;

-- ============================================
-- TABELA: administradores
-- ============================================
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Senha padrão: admin123 (hash bcrypt)
-- Usuário: admin
-- Para gerar novo hash: password_hash('sua_senha', PASSWORD_BCRYPT)
INSERT INTO administradores (usuario, senha, nome) VALUES 
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Administrador');

-- ============================================
-- TABELA: votacoes
-- ============================================
CREATE TABLE IF NOT EXISTS votacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    status ENUM('aberta', 'encerrada') DEFAULT 'encerrada',
    criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    aberta_em TIMESTAMP NULL,
    encerrada_em TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: votos
-- ============================================
CREATE TABLE IF NOT EXISTS votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacao_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    cargo VARCHAR(100),
    foto VARCHAR(255),
    voto ENUM('sim', 'nao') NOT NULL,
    ip_address VARCHAR(45),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (votacao_id) REFERENCES votacoes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cpf_votacao (votacao_id, cpf),
    INDEX idx_votacao (votacao_id),
    INDEX idx_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ÍNDICES ADICIONAIS
-- ============================================
CREATE INDEX idx_status ON votacoes(status);
CREATE INDEX idx_criado_em ON votos(criado_em);
