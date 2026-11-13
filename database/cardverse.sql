CREATE DATABASE IF NOT EXISTS cardverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cardverse;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 senha VARCHAR(255) NOT NULL,
 moedas INT UNSIGNED NOT NULL DEFAULT 500,
 tipo ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cartas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(100) NOT NULL UNIQUE,
 descricao TEXT,
 imagem VARCHAR(255) NOT NULL,
 raridade ENUM('comum','rara','epica','lendaria') NOT NULL,
 ataque INT UNSIGNED NOT NULL DEFAULT 0,
 defesa INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS colecao (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 carta_id INT UNSIGNED NOT NULL,
 quantidade INT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY uq_colecao_usuario_carta (usuario_id,carta_id),
 CONSTRAINT fk_colecao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 CONSTRAINT fk_colecao_carta FOREIGN KEY (carta_id) REFERENCES cartas(id) ON DELETE RESTRICT,
 CONSTRAINT chk_colecao_quantidade CHECK (quantidade > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pacotes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(100) NOT NULL UNIQUE,
 descricao TEXT NULL,
 selo VARCHAR(30) NULL,
 destaque BOOLEAN NOT NULL DEFAULT FALSE,
 raridade_garantida ENUM('comum','rara','epica','lendaria') NULL,
 chance_lendaria TINYINT UNSIGNED NOT NULL DEFAULT 0,
 preco INT UNSIGNED NOT NULL,
 quantidade_cartas TINYINT UNSIGNED NOT NULL DEFAULT 5,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 CONSTRAINT chk_pacotes_quantidade CHECK (quantidade_cartas > 0),
 CONSTRAINT chk_pacotes_chance CHECK (chance_lendaria <= 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pacote_cartas (
 pacote_id INT UNSIGNED NOT NULL,
 carta_id INT UNSIGNED NOT NULL,
 PRIMARY KEY (pacote_id,carta_id),
 CONSTRAINT fk_pc_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE,
 CONSTRAINT fk_pc_carta FOREIGN KEY (carta_id) REFERENCES cartas(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS compras (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 pacote_id INT UNSIGNED NOT NULL,
 valor_pago INT UNSIGNED NOT NULL,
 token_compra CHAR(64) NOT NULL,
 data_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_compras_token (token_compra),
 CONSTRAINT fk_compra_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 CONSTRAINT fk_compra_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE RESTRICT,
 CONSTRAINT chk_compra_valor CHECK (valor_pago > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS aberturas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 pacote_id INT UNSIGNED NOT NULL,
 compra_id INT UNSIGNED NOT NULL,
 data_abertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 cartas_recebidas JSON NOT NULL,
 UNIQUE KEY uq_aberturas_compra (compra_id),
 CONSTRAINT fk_abertura_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
 CONSTRAINT fk_abertura_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE RESTRICT,
 CONSTRAINT fk_abertura_compra FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pacote_chances (
 pacote_id INT UNSIGNED NOT NULL,
 raridade ENUM('comum','rara','epica','lendaria') NOT NULL,
 peso TINYINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (pacote_id,raridade),
 CONSTRAINT fk_chance_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE,
 CONSTRAINT chk_chance_peso CHECK (peso <= 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trocas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 remetente_id INT UNSIGNED NOT NULL,
 destinatario_id INT UNSIGNED NOT NULL,
 carta_oferecida_id INT UNSIGNED NOT NULL,
 carta_solicitada_id INT UNSIGNED NOT NULL,
 status ENUM('pendente','aceita','recusada','cancelada') NOT NULL DEFAULT 'pendente',
 criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_troca_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
 CONSTRAINT fk_troca_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
 CONSTRAINT fk_troca_oferecida FOREIGN KEY (carta_oferecida_id) REFERENCES cartas(id) ON DELETE RESTRICT,
 CONSTRAINT fk_troca_solicitada FOREIGN KEY (carta_solicitada_id) REFERENCES cartas(id) ON DELETE RESTRICT,
 CONSTRAINT chk_troca_usuarios CHECK (remetente_id <> destinatario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resgates_diarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 data_resgate DATE NOT NULL,
 moedas_recebidas INT UNSIGNED NOT NULL DEFAULT 100,
 UNIQUE KEY uq_resgate_usuario_data (usuario_id,data_resgate),
 CONSTRAINT fk_resgate_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 CONSTRAINT chk_resgate_moedas CHECK (moedas_recebidas > 0)
) ENGINE=InnoDB;

INSERT INTO cartas (nome,descricao,imagem,raridade,ataque,defesa) VALUES
('Cavaleiro de Ferro','Um defensor de armadura resistente.','cavaleiro-ferro.png','comum',35,70),
('Arqueira Lunar','Ataca de longe com flechas brilhantes.','arqueira-lunar.png','comum',50,30),
('Golem de Pedra','Criatura resistente das montanhas.','golem-pedra.png','comum',25,85),
('Espadachim Errante','Guerreiro veloz de terras distantes.','espadachim-errante.png','comum',55,35),
('Mago das Mares','Controla correntes e tempestades.','mago-mares.png','rara',70,45),
('Lobo Fantasma','Caçador das florestas encantadas.','lobo-fantasma.png','rara',75,40),
('Guardia Solar','Protege aliados com energia solar.','guardia-solar.png','rara',65,65),
('Dragao das Sombras','Uma criatura ancestral das trevas.','dragao-sombras.png','epica',95,75),
('Rainha Estelar','Domina a magia das estrelas.','rainha-estelar.png','epica',90,80),
('Fenix Celestial','Ave lendaria que renasce das cinzas.','fenix-celestial.png','lendaria',120,100)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO pacotes (nome,descricao,selo,destaque,raridade_garantida,chance_lendaria,preco,quantidade_cartas,ativo) VALUES
('Pacote Mistico','Um pacote com 5 cartas misteriosas do universo CardVerse. Reúne cartas comuns, raras e épicas para começar ou ampliar a coleção.','Popular',FALSE,NULL,0,100,5,TRUE),
('Pacote Arcano','Cinco cartas imbuídas de magia arcana, com foco em raras e épicas e uma pequena chance de revelar uma lendária.','Raro+',FALSE,NULL,10,250,5,TRUE),
('Pacote Celestial','Cinco cartas celestiais com pelo menos uma épica garantida e chance de revelar a lendária Fênix Celestial.','Premium',TRUE,'epica',25,500,5,TRUE)
ON DUPLICATE KEY UPDATE
 nome=VALUES(nome),
 descricao=VALUES(descricao),
 selo=VALUES(selo),
 destaque=VALUES(destaque),
 raridade_garantida=VALUES(raridade_garantida),
 chance_lendaria=VALUES(chance_lendaria),
 preco=VALUES(preco),
 quantidade_cartas=VALUES(quantidade_cartas),
 ativo=VALUES(ativo);

DELETE pc FROM pacote_cartas pc
INNER JOIN pacotes p ON p.id = pc.pacote_id
WHERE p.nome IN ('Pacote Mistico','Pacote Arcano','Pacote Celestial');

INSERT IGNORE INTO pacote_cartas (pacote_id,carta_id)
SELECT p.id,c.id
FROM pacotes p
CROSS JOIN cartas c
WHERE (p.nome='Pacote Mistico' AND c.raridade IN ('comum','rara','epica'))
   OR (p.nome='Pacote Arcano' AND c.raridade IN ('rara','epica','lendaria'))
   OR (p.nome='Pacote Celestial' AND c.raridade IN ('epica','lendaria'));

INSERT INTO pacote_chances (pacote_id,raridade,peso)
SELECT p.id,'comum',55 FROM pacotes p WHERE p.nome='Pacote Mistico'
UNION ALL SELECT p.id,'rara',35 FROM pacotes p WHERE p.nome='Pacote Mistico'
UNION ALL SELECT p.id,'epica',10 FROM pacotes p WHERE p.nome='Pacote Mistico'
UNION ALL SELECT p.id,'rara',60 FROM pacotes p WHERE p.nome='Pacote Arcano'
UNION ALL SELECT p.id,'epica',40 FROM pacotes p WHERE p.nome='Pacote Arcano'
UNION ALL SELECT p.id,'epica',100 FROM pacotes p WHERE p.nome='Pacote Celestial'
ON DUPLICATE KEY UPDATE peso=VALUES(peso);
