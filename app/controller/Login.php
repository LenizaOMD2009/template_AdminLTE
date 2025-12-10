<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;

class Login extends Base
{
    public function login($request, $response)
    {
        try {

            $dadosTemplate = [
                'titulo' => 'Autenticação'
            ];
            return $this->getTwig()
                ->render($response, $this->setView('login'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            die;
        }
    }

    public function precadastro($request, $response)
    {
        try {
            #Captura os dados do form
            $form = $request->getParsedBody();
            #Capturar os dados do usuário.
            $dadosUsuario = [
                'nome' => $form['nome'],
                'sobrenome' => $form['sobrenome'],
                'cpf' => $form['cpf'],
                'rg' => $form['rg'],
                'senha' => password_hash($form['senhaCadastro'], PASSWORD_DEFAULT)
            ];
            $IsInseted = InsertQuery::table('usuario')->save($dadosUsuario);
            if (!$IsInseted) {
                return $this->SendJson(
                    $response,
                    ['status' => false, 'msg' => 'Erro ao inserir usuário', 'id' => 0],
                    403
                );
            }
            #Captura o código do ultimo usuário cadastrado na tabela de usuário
            $id = SelectQuery::select('id')->from('usuario')->order('id', 'desc')->fetch();
            #Colocamos o ID do ultimo usuário cadastrado na varaivel $id_usuario.
            $id_usuario = $id['id'];
            #Finalizar o pré-cadastro.
            #Cadastrar todos os contatos: E-mail, Celular, WhastaApp.
            $dadosContato = [];
            #Inserindo o Email.
            $dadosContato = [
                'id_usuario' => $id_usuario,
                'tipo' => 'email',
                'contato' => $form['email']
            ];
            InsertQuery::table('contato')->save($dadosContato);
            $dadosContato = [];
            #Inserindo o whatsapp.
            $dadosContato = [
                'id_usuario' => $id_usuario,
                'tipo' => 'whatsapp',
                'contato' => $form['whatsapp']

            ];
            InsertQuery::table('contato')->save($dadosContato);
            $dadosContato = [];
            #Inserindo o Celular.
            $dadosContato = [
                'id_usuario' => $id_usuario,
                'tipo' => 'celular',
                'contato' => $form['celular']

            ];
            InsertQuery::table('contato')->save($dadosContato);
            return $this->SendJson($response, ['status' => true, 'msg' => 'Pré-cadastro realizado com sucesso!', 'id' => $id_usuario], 201);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => true, 'msg' => 'Restrição:' . $e->getMessage(), 'id' => 0], 500);
        }
    }

    public function autenticar($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            if (!isset($form['login']) || empty($form['login'])) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'O campo login é obrigatório!', 'id' => 0], 403);
            }
            if (!isset($form['senha']) || empty($form['senha'])) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'O campo senha é obrigatório!', 'id' => 0], 403);
            }
            $user = SelectQuery::select()
                ->from('vw_usuario_contatos')
                ->where('cpf', '=', $form['login'], 'or')
                ->where('email', '=', $form['login'], 'or')
                ->where('celular', '=', $form['login'], 'or')
                ->where('whatsapp', '=', $form['login'])
                ->fetch();
            if (!isset($user) || empty($user) || count($user) <= 0) {
                return $this->SendJson(
                    $response,
                    ['status' => false, 'msg' => 'Usuário ou senha inválidos!', 'id' => 0],
                    403
                );
            }
            if (!$user['ativo']) {
                return $this->SendJson(
                    $response,
                    ['status' => false, 'msg' => 'Por enquanto você ainda não tem permissão de acessar o sistema!', 'id' => $user['id']],
                    403
                );
            }
            if (!password_verify($form['senha'], $user['senha'])) {
                return $this->SendJson(
                    $response,
                    ['status' => false, 'msg' => 'Usuário ou senha inválidos!', 'id' => 0],
                    403
                );
            }
            if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
                UpdateQuery::table('usuario')->set(['senha' => password_hash($form['senha'], PASSWORD_DEFAULT)])->where('id', '=', $user['id'])->update();
            }
            #Criar a sessão do usuário.
            $_SESSION['usuario'] = [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'sobrenome' => $user['sobrenome'],
                'cpf' => $user['cpf'],
                'rg' => $user['rg'],
                'senha' => $user['senha'],
                'ativo' => $user['ativo'],
                'logado' => true
            ];
            return $this->SendJson(
                $response,
                ['status' => true, 'msg' => 'Autenticação realizada com sucesso!', 'id' => $user['id']],
                200
            );
            #Autenticação realizada com sucesso
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição:' . $e->getMessage(), 'id' => 0], 500);
        }
    }
}
