CREATE DATABASE IF NOT EXISTS cardverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cardverse;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 senha VARCHAR(255) NOT NULL,
 moedas INT UNSIGNED NOT NULL DEFAULT 500,
 pontos INT UNSIGNED NOT NULL DEFAULT 0,
 experiencia INT UNSIGNED NOT NULL DEFAULT 0,
 avatar VARCHAR(255) NULL,
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


CREATE TABLE IF NOT EXISTS niveis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nivel INT UNSIGNED NOT NULL UNIQUE,
    experiencia_necessaria INT UNSIGNED NOT NULL UNIQUE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS conquistas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    icone VARCHAR(255) NULL,

    pontos INT UNSIGNED NOT NULL DEFAULT 0,
    experiencia INT UNSIGNED NOT NULL DEFAULT 0,

    criterio VARCHAR(50) NOT NULL,
    valor_criterio INT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS usuarios_conquistas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_usuario INT UNSIGNED NOT NULL,
    id_conquista INT UNSIGNED NOT NULL,

    data_desbloqueio DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_usuario_conquista
        UNIQUE (id_usuario, id_conquista),

    CONSTRAINT fk_uc_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_uc_conquista
        FOREIGN KEY (id_conquista)
        REFERENCES conquistas(id)
) ENGINE=InnoDB;



CREATE TABLE IF NOT EXISTS missoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL,

    descricao VARCHAR(255) NOT NULL,

    icone VARCHAR(100) NULL,

    tipo ENUM('diaria', 'semanal', 'especial') NOT NULL,

    criterio VARCHAR(50) NOT NULL,

    quantidade_necessaria INT UNSIGNED NOT NULL,

    recompensa_moedas INT UNSIGNED NOT NULL DEFAULT 0,

    recompensa_pontos INT UNSIGNED NOT NULL DEFAULT 0,

    recompensa_xp INT UNSIGNED NOT NULL DEFAULT 0,

    ativa BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_missoes_tipo_ativa (tipo, ativa),

    CHECK (quantidade_necessaria > 0)

) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS usuarios_missoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT UNSIGNED NOT NULL,

    missao_id INT UNSIGNED NOT NULL,

    progresso INT UNSIGNED NOT NULL DEFAULT 0,

    periodo_inicio DATETIME NOT NULL,

    periodo_fim DATETIME NOT NULL,

    atribuida_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    concluida_em DATETIME NULL,

    resgatada_em DATETIME NULL,

    INDEX idx_usuario_missoes_periodo
        (usuario_id, periodo_inicio, periodo_fim),

    CONSTRAINT fk_usuarios_missoes_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_usuarios_missoes_missao
        FOREIGN KEY (missao_id)
        REFERENCES missoes(id)
        ON DELETE RESTRICT,

    CHECK (periodo_fim > periodo_inicio)

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


-- niveis
INSERT INTO niveis (nivel, experiencia_necessaria)
VALUES
(1, 0),
(2, 100),
(3, 250),
(4, 450),
(5, 700),
(6, 1000),
(7, 1400),
(8, 1850),
(9, 2400),
(10, 3000);

-- conquistas
INSERT INTO conquistas
(nome, descricao, icone, pontos, experiencia, criterio, valor_criterio)
VALUES
(
    'Primeira Compra',
    'Compre seu primeiro pacote.',
    'primeira-compra.png',
    10,
    50,
    'comprar_pacotes',
    1
),
(
    'Colecionador',
    'Compre 5 pacotes.',
    'colecionador.png',
    25,
    100,
    'comprar_pacotes',
    5
),
(
    'Fã de carteirinha',
    'Compre 10 pacotes.',
    'Fa-de-carteirinha.png',
    50,
    200,
    'comprar_pacotes',
    10
),
(
    'Grande Investidor',
    'Compre 50 pacotes.',
    'grande-investidor.png',
    100,
    400,
    'comprar_pacotes',
    50
),
(
    'Mega Investidor',
    'Compre 100 pacotes.',
    'mega-investidor.png',
    250,
    600,
    'comprar_pacotes',
    100
),
(
    'Mestre dos pacotes',
    'Compre 250 pacotes.',
    'mestre-dos-pacotes.png',
    750,
    800,
    'comprar_pacotes',
    250
),
(
    'Magnata da coleção',
    'Compre 500 pacotes.',
    'magnata-da-colecao.png',
    1000,
    1100,
    'comprar_pacotes',
    500
),
(
    'Lenda dos pacotes',
    'Compre 1000 pacotes.',
    'lenda-dos-pacotes.png',
    1000,
    1500,
    'comprar_pacotes',
    1000
),
(
    'Colecionador Implacável',
    'Compre 10000 pacotes.',
    'colecionador-implacavel.png',
    1500,
    2400,
    'comprar_pacotes',
    10000
),
(
    'Viciado em Cartas',
    'Compre 100000 pacotes.',
    'viciado-em-cartas.png',
    3500,
    5500,
    'comprar_pacotes',
    100000
);

--Missoes


-- =========================================
-- DESATIVAR CATÁLOGO ANTERIOR
-- =========================================

UPDATE missoes
SET ativa = FALSE;


-- =========================================
-- NOVAS MISSÕES DO CARDVERSE
-- =========================================

INSERT INTO missoes
(
    nome,
    descricao,
    icone,
    tipo,
    criterio,
    quantidade_necessaria,
    recompensa_moedas,
    recompensa_pontos,
    recompensa_xp,
    ativa
)
VALUES

(
    'Comprador Diário I',
    'Compre 1 pacote durante o dia.',
    'bi bi-bag-check',
    'diaria',
    'comprar_pacotes',
    1,
    15,
    2,
    8,
    TRUE
),

(
    'Comprador Diário II',
    'Compre 2 pacotes durante o dia.',
    'bi bi-bag-check-fill',
    'diaria',
    'comprar_pacotes',
    2,
    25,
    4,
    12,
    TRUE
),

(
    'Comprador Diário III',
    'Compre 3 pacotes durante o dia.',
    'bi bi-basket2',
    'diaria',
    'comprar_pacotes',
    3,
    40,
    6,
    18,
    TRUE
),

(
    'Primeira Abertura',
    'Abra 1 pacote durante o dia.',
    'bi bi-box-open',
    'diaria',
    'abrir_pacotes',
    1,
    10,
    2,
    8,
    TRUE
),

(
    'Explorador Diário',
    'Abra 2 pacotes durante o dia.',
    'bi bi-box-seam',
    'diaria',
    'abrir_pacotes',
    2,
    20,
    4,
    12,
    TRUE
),

(
    'Maratona Diária',
    'Abra 3 pacotes durante o dia.',
    'bi bi-boxes',
    'diaria',
    'abrir_pacotes',
    3,
    30,
    6,
    18,
    TRUE
),

(
    'Resgate Diário',
    'Resgate sua recompensa diária.',
    'bi bi-calendar-check',
    'diaria',
    'resgatar_diario',
    1,
    15,
    2,
    8,
    FALSE
),
(
    'Ritmo de Compras',
    'Compre 15 pacotes durante a semana.',
    'bi bi-bag-check',
    'semanal',
    'comprar_pacotes',
    15,
    180,
    25,
    90,
    TRUE
),

(
    'Comprador Persistente',
    'Compre 25 pacotes durante a semana.',
    'bi bi-bag-check-fill',
    'semanal',
    'comprar_pacotes',
    25,
    300,
    40,
    150,
    TRUE
),

(
    'Investidor Semanal',
    'Compre 50 pacotes durante a semana.',
    'bi bi-cart-check',
    'semanal',
    'comprar_pacotes',
    50,
    550,
    75,
    260,
    TRUE
),

(
    'Magnata Semanal',
    'Compre 100 pacotes durante a semana.',
    'bi bi-gem',
    'semanal',
    'comprar_pacotes',
    100,
    1000,
    120,
    420,
    TRUE
),
(
    'Ritmo de Aberturas',
    'Abra 15 pacotes durante a semana.',
    'bi bi-box-seam',
    'semanal',
    'abrir_pacotes',
    15,
    130,
    20,
    75,
    TRUE
),

(
    'Maratona de Aberturas',
    'Abra 25 pacotes durante a semana.',
    'bi bi-boxes',
    'semanal',
    'abrir_pacotes',
    25,
    220,
    35,
    130,
    TRUE
),

(
    'Mestre das Aberturas',
    'Abra 50 pacotes durante a semana.',
    'bi bi-box2-heart',
    'semanal',
    'abrir_pacotes',
    50,
    400,
    60,
    220,
    TRUE
),

(
    'Lenda das Aberturas',
    'Abra 100 pacotes durante a semana.',
    'bi bi-stars',
    'semanal',
    'abrir_pacotes',
    100,
    750,
    100,
    380,
    TRUE
),
(
    'Presença Semanal',
    'Resgate recompensas diárias em 3 dias diferentes.',
    'bi bi-calendar-week',
    'semanal',
    'resgatar_diario',
    3,
    120,
    20,
    80,
    FALSE
),

(
    'Presença Constante',
    'Resgate recompensas diárias em 5 dias diferentes.',
    'bi bi-calendar-check',
    'semanal',
    'resgatar_diario',
    5,
    200,
    35,
    140,
    FALSE
),

(
    'Presença Perfeita',
    'Resgate recompensas diárias em 7 dias diferentes.',
    'bi bi-calendar-check-fill',
    'semanal',
    'resgatar_diario',
    7,
    300,
    55,
    210,
    FALSE
);
