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
    -- Tabela UF
    CREATE TABLE IF NOT EXISTS uf(
        id bigserial primary key,
        sigla text,
        nome text, 
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp
    );

    -- Tabela Cidade
    CREATE TABLE IF NOT EXISTS cidade(
        id bigserial primary key, 
        id_uf bigint,
        nome text,
        ibge text,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp,
        constraint cidade_id_uf foreign key (id_uf) references uf(id)
    );

    -- Tabela Cliente
    CREATE TABLE IF NOT EXISTS cliente(
        id bigserial primary key,
        nome_fantasia text,
        sobrenome_razao text,
        cpf_cnpj text,
        rg_ie text,
        data_nascimento_abertura date,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp
    );

    -- Tabela Usuario
    CREATE TABLE IF NOT EXISTS usuario (
        id bigserial primary key,
        nome text,
        sobrenome text,
        cpf text,
        rg text,
        senha text,
        codigo_recuperacao text,
        ativo boolean default false,
        administrador boolean default false,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp
    );

    -- Tabela Empresas
    CREATE TABLE IF NOT EXISTS empresas(
        id bigserial primary key,
        nome_fantasia text,
        sobrenome_razao text,
        cpf_cnpj text,
        rg_ie text,
        data_nascimento_abertura date,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp
    );

    -- Tabela Fornecedor
    CREATE TABLE IF NOT EXISTS fornecedor(
        id bigserial primary key,
        nome_fantasia text,
        sobrenome_razao text,
        cpf_cnpj text,
        rg_ie text,
        data_nascimento_abertura date,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp
    );

    -- Tabela Endereco
    CREATE TABLE IF NOT EXISTS endereco(
        id bigserial primary key, 
        id_cidade bigint,
        id_cliente bigint,
        id_usuario bigint,
        id_empresas bigint,
        id_fornecedor bigint,
        nome text,
        cep text,
        numero text,
        logradouro text,
        bairro text,
        complemento text,
        referencia text,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp,
        constraint endereco_id_cidade foreign key (id_cidade) references cidade(id),
        constraint endereco_id_usuario foreign key (id_usuario) references usuario(id),
        constraint endereco_id_cliente foreign key (id_cliente) references cliente(id),
        constraint endereco_id_empresas foreign key (id_empresas) references empresas(id),
        constraint endereco_id_fornecedor foreign key (id_fornecedor) references fornecedor(id)
    );

    -- Tabela Contato
    CREATE TABLE IF NOT EXISTS contato(
        id bigserial primary key,
        id_cliente bigint,
        id_usuario bigint,
        id_empresas bigint,
        id_fornecedor bigint,
        tipo text,
        contato text,
        endereco_contato text,
        data_cadastro timestamp default current_timestamp,
        data_alteracao timestamp default current_timestamp,
        constraint contato_id_usuario foreign key (id_usuario) references usuario(id),
        constraint contato_id_cliente foreign key (id_cliente) references cliente(id),
        constraint contato_id_empresas foreign key (id_empresas) references empresas(id),
        constraint contato_id_fornecedor foreign key (id_fornecedor) references fornecedor(id)
    );

    -- View vw_usuario_contatos
    CREATE OR REPLACE VIEW vw_usuario_contatos AS
    SELECT 
        u.id,
        u.nome,
        u.sobrenome,
        u.cpf,
        u.rg,
        u.ativo,
        u.administrador,
        u.senha,
        u.codigo_recuperacao,
        MAX(CASE WHEN c.tipo = 'email' THEN c.contato END) AS email,
        MAX(CASE WHEN c.tipo = 'celular' THEN c.contato END) AS celular,
        MAX(CASE WHEN c.tipo = 'whatsapp' THEN c.contato END) AS whatsapp,
        u.data_cadastro,
        u.data_alteracao
    FROM usuario u
    LEFT JOIN contato c ON c.id_usuario = u.id
    GROUP BY u.id, u.nome, u.sobrenome, u.cpf, u.rg, u.ativo, u.administrador, u.senha, u.codigo_recuperacao, u.data_cadastro, u.data_alteracao;
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