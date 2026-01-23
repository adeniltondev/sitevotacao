-- Adicionar suporte para votação anônima e nominal
-- Execute este arquivo no banco de dados

-- Adicionar campo tipo_votacao na tabela votacoes
ALTER TABLE votacoes 
ADD COLUMN tipo_votacao ENUM('nominal', 'anonima') DEFAULT 'nominal' 
AFTER descricao;

-- Adicionar índice para melhor performance
CREATE INDEX idx_tipo_votacao ON votacoes(tipo_votacao);

-- Comentário explicativo
-- nominal: mostra quem votou (padrão)
-- anonima: oculta identidade dos votantes
