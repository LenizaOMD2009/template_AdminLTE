<?php

namespace app\controller;

use app\database\builder\DeleteQuery;
use app\database\builder\InsertQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;
use EmptyIterator;

class User extends Base
{

    public function lista($request, $response)
    {
        $dadosTemplate = [
            'titulo' => 'Lista de usuário'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('listuser'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function cadastro($request, $response)
    {
        $dadosTemplate = [
            'acao' => 'c',
            'titulo' => 'Cadastro e alteracao de usuário'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('user'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function alterar($request, $response, $args)
    {
        $id = $args['id'];
        $user = SelectQuery::select()->from('usuario')->where('id', '=', $id)->fetch();
        $dadosTemplate = [
            'acao' => 'e',
            'id' => $id,
            'titulo' => 'Cadastro e alteracao de usuário',
            'usuario' => $user
        ];
        return $this->getTwig()
            ->render($response, $this->setView('user'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function listuser($request, $response)
    {
        #Captura todas a variaveis de forma mais segura VARIAVEIS POST.
        $form = $request->getParsedBody();
        #Qual a coluna da tabela deve ser ordenada.
        $order = ($form['order'][0]['column'])
            ? $form['order'][0]['column']
            : 0;
        #Tipo de ordenação
        $orderType = $form['order'][0]['dir'] ?? 'desc';
        #Em qual registro se inicia o retorno dos registro, OFFSET
        $start = $form['start'];
        #Limite de registro a serem retornados do banco de dados LIMIT
        $length = $form['length'];
        $fields = [
            0 => 'id',
            1 => 'nome',
            2 => 'sobrenome',
            3 => 'cpf',
            4 => 'rg'
        ];
        #Capturamos o nome do capo a ser ordenado.
        $orderField = $fields[$order];
        #O termo pesquisado
        $term = $form['search']['value'];
        $query = SelectQuery::select('id,nome,sobrenome,cpf,rg')->from('usuario');
        if (!is_null($term) && ($term !== '')) {
            $query->where('usuario.nome', 'ilike', "%{$term}%", 'or')
                ->where('usuario.sobrenome', 'ilike', "%{$term}%", 'or')
                ->where('usuario.cpf', 'ilike', "%{$term}%", 'or')
                ->where('usuario.rg', 'ilike', "%{$term}%");
        }

        $users = $query
            ->order($orderField, $orderType)
            ->limit($length, $start)
            ->fetchAll();

        $userData = [];
        foreach ($users as $key => $value) {
            $userData[$key] = [
                $value['id'],
                $value['nome'],
                $value['sobrenome'],
                $value['cpf'],
                $value['rg'],
                "<a href='/usuario/alterar/{$value['id']}' class='btn btn-warning'>Editar</a>
                 <button type='button'  onclick='Delete(" . $value['id'] . ");' class='btn btn-danger'>Excluir</button>"
            ];
        }
        $data = [
            'status' => true,
            'recordsTotal' => count($users),
            'recordsFiltered' => count($users),
            'data' => $userData
        ];

        $payload = json_encode($data);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    public function insert($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $FieldAndValues = [
                'nome' => $form['nome'],
                'sobrenome' => $form['sobrenome'],
                'cpf' => $form['cpf'],
                'rg' => $form['rg'],
                'senha' => password_hash($form['senha'], PASSWORD_DEFAULT),
                #'ativo' => (isset($form['ativo']) and $form['ativo'] === 'true') ? true : false,
                #'administrador' => (isset($form['administrador']) and $form['administrador'] === 'true') ? true : false
            ];
            $IsInsert = InsertQuery::table('usuario')->save($FieldAndValues);
            if (!$IsInsert) {
                $data = [
                    'status' => false,
                    'msg' => 'Restrição: ' . $$IsInsert,
                    'id' => 0
                ];
                $payload = json_encode($data);
                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }

            $id = SelectQuery::select('id')->from('usuario')->order('id', 'desc')->fetch();

            $data = [
                'status' => true,
                'msg' => 'Cadastro realizado com sucesso! ',
                'id' => $id['id']
            ];
            $payload = json_encode($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
        }
    }
    public function delete($request, $response)
    {
        try {
            $id = $_POST['id'];
            
            // Primeiro, deleta registros relacionados em contato
            try {
                DeleteQuery::table('contato')
                    ->where('id_usuario', '=', $id)
                    ->delete();
            } catch (\Exception $e) {
                // Log ou ignore se não houver registros
            }

            // Depois, deleta registros relacionados em endereco
            try {
                DeleteQuery::table('endereco')
                    ->where('id_usuario', '=', $id)
                    ->delete();
            } catch (\Exception $e) {
                // Log ou ignore se não houver registros
            }

            // Finalmente, deleta o usuário
            $IsDelete = DeleteQuery::table('usuario')
                ->where('id', '=', $id)
                ->delete();

            if (!$IsDelete) {
                $data = ['status' => false, 'msg' => 'Erro ao deletar usuário', 'id' => $id];
                return $this->SendJson($response, $data, 200);
            }
            
            $data = ['status' => true, 'msg' => 'Usuário removido com sucesso!', 'id' => $id];
            return $this->SendJson($response, $data, 200);
            
        } catch (\Throwable $th) {
            $data = ['status' => false, 'msg' => 'Erro: ' . $th->getMessage(), 'id' => $_POST['id'] ?? 0];
            return $this->SendJson($response, $data, 500);
        }
    }
    public function update($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'];
            $FieldAndValues = [
                'nome' => $form['nome'],
                'sobrenome' => $form['sobrenome'],
                'cpf' => $form['cpf'],
                'rg' => $form['rg'],
                'senha' => password_hash($form['senha'], PASSWORD_DEFAULT),
                #'ativo' => (isset($form['ativo']) and $form['ativo'] === 'true') ? true : false,
                #'administrador' => (isset($form['administrador']) and $form['administrador'] === 'true') ? true : false
            ];
            $IsUpdate = UpdateQuery::table('usuario')->set($FieldAndValues)->where('id', '=', $id)->update();
            if (!$IsUpdate) {
                $data = [
                    'status' => false,
                    'msg' => 'Restrição: ' . $$IsUpdate,
                    'id' => 0
                ];
                $payload = json_encode($data);
                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
            $data = [
                'status' => true,
                'msg' => 'Dados alterados com sucesso! ',
                'id' => $id
            ];
            $payload = json_encode($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
        }
    }
}
