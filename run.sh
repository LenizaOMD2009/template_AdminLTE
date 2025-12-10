#!/bin/bash

cd /home/template_AdiminLTE/

rm -R vendor/
rm -R composer.lock

# Configurar e executar o Composer
export COMPOSER_ALLOW_SUPERUSER=1
echo "Instalando dependências do Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader
composer update --no-interaction
composer dump-autoload -o

PG_USER="senac"
PG_PASS="senac"
PG_DB="template_adiminlte"
############################################################
# 1) Criar usuário se não existir
############################################################
create_user_if_not_exists() {
    echo ">> Verificando se o usuário '${PG_USER}' existe..."

    USER_EXISTS=$(sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${PG_USER}'")

    if [ "$USER_EXISTS" = "1" ]; then
        echo "   - Usuário já existe. Nada será feito."
    else
        echo "   - Usuário não existe. Criando usuário..."
        sudo -u postgres psql -c "CREATE USER ${PG_USER} WITH PASSWORD '${PG_PASS}';"
        echo "   - Usuário criado com sucesso."
    fi
}
############################################################
# 2) Criar banco se não existir e definir owner
############################################################
create_database_if_not_exists() {
    echo ">> Verificando se o banco '${PG_DB}' existe..."

    DB_EXISTS=$(sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${PG_DB}'")

    if [ "$DB_EXISTS" = "1" ]; then
        echo "   - Banco já existe. Garantindo que o owner é '${PG_USER}'..."
        sudo -u postgres psql -c "ALTER DATABASE ${PG_DB} OWNER TO ${PG_USER};"
    else
        echo "   - Banco não existe. Criando banco..."
        sudo -u postgres psql -c "CREATE DATABASE ${PG_DB} OWNER ${PG_USER};"
        echo "   - Banco criado com sucesso."
    fi
}
############################################################
# 3) Criar tabelas e view se não existirem
############################################################
create_schema_objects() {
    echo ">> Conectando ao banco '${PG_DB}' e criando objetos..."
sudo -u postgres psql -d "${PG_DB}" <<EOF
    -- Tabela usuario
    CREATE TABLE IF NOT EXISTS usuario (
        id bigserial PRIMARY KEY,
        nome text,
        sobrenome text,
        cpf text,
        rg text,
        data_nascimento date,
        senha text,
        ativo boolean DEFAULT false,
        administrador boolean DEFAULT false,
        codigo_verificacao text,
        data_cadastro timestamp DEFAULT CURRENT_TIMESTAMP,
        data_alteracao timestamp DEFAULT CURRENT_TIMESTAMP
    );
    -- Tabela contato
    CREATE TABLE IF NOT EXISTS contato (
        id bigserial PRIMARY KEY,
        id_usuario bigint,
        tipo text,
        contato text,
        data_cadastro timestamp,
        data_alteracao timestamp,
        CONSTRAINT contato_id_usuario_fkey FOREIGN KEY (id_usuario)
            REFERENCES public.usuario (id)
            ON UPDATE NO ACTION
            ON DELETE NO ACTION
    );
    -- View vw_usuario_contatos
    CREATE OR REPLACE VIEW vw_usuario_contatos AS
    SELECT u.id,
        u.nome,
        u.sobrenome,
        u.cpf,
        u.rg,
        u.senha,
        u.ativo,
        u.administrador,
        u.codigo_verificacao,
        MAX(CASE WHEN c.tipo = 'email' THEN c.contato ELSE NULL END) AS email,
        MAX(CASE WHEN c.tipo = 'celular' THEN c.contato ELSE NULL END) AS celular,
        MAX(CASE WHEN c.tipo = 'whatsapp' THEN c.contato ELSE NULL END) AS whatsapp,
        u.data_cadastro,
        u.data_alteracao
    FROM usuario u
    LEFT JOIN contato c ON c.id_usuario = u.id
    GROUP BY u.id, u.nome, u.sobrenome, u.cpf, u.rg, u.data_cadastro, u.data_alteracao;
EOF
    echo "   - Tabelas e view verificadas/criadas com sucesso."
}

############################################################
# Execução das funções
############################################################

create_user_if_not_exists
create_database_if_not_exists
create_schema_objects 

echo ">> Processo concluído!"

service nginx reload