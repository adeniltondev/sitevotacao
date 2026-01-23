-- Adicionar campo eleitor_id na tabela votos para rastreamento em votações anônimas
ALTER TABLE votos ADD COLUMN eleitor_id INT NULL AFTER votacao_id;

-- Adicionar índice
CREATE INDEX idx_eleitor_id ON votos(eleitor_id);

-- Permitir campos NULL para votações anônimas
ALTER TABLE votos MODIFY COLUMN nome VARCHAR(255) NULL;
ALTER TABLE votos MODIFY COLUMN cpf VARCHAR(14) NULL;
