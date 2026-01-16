<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;
use app\source\Email;

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

    public function recuperar($request, $response)
    {
        try {
            $form = $request->getParsedBody();

            if (!isset($form['identificador']) || empty(trim($form['identificador']))) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'O campo identificador é obrigatório!', 'id' => 0], 403);
            }

            $identificador = trim($form['identificador']);

            $user = SelectQuery::select()
                ->from('vw_usuario_contatos')
                ->where('cpf', '=', $identificador, 'or')
                ->where('email', '=', $identificador, 'or')
                ->where('celular', '=', $identificador, 'or')
                ->where('whatsapp', '=', $identificador)
                ->fetch();

            if (!isset($user) || empty($user) || count($user) <= 0) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Identificador não encontrado!', 'id' => 0], 404);
            }

            // Gerar código de 6 dígitos
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Armazenar código em sessão com timestamp
            $_SESSION['recuperacao_senha'] = [
                'id_usuario' => $user['id'],
                'codigo' => $codigo,
                'timestamp' => time(),
                'tentativas' => 0
            ];

            // Enviar email com o código
            try {
                $assunto = 'Código de recuperação de senha';
                $corpo = "<h3>Código de Recuperação de Senha</h3>";
                $corpo .= "<p>Olá, <strong>{$user['nome']} {$user['sobrenome']}</strong></p>";
                $corpo .= "<p>Seu código de recuperação é: <strong style='font-size: 24px; color: #007bff;'>{$codigo}</strong></p>";
                $corpo .= "<p>Este código expira em 15 minutos.</p>";
                $corpo .= "<p>Se você não solicitou isso, ignore este email.</p>";

                Email::add($assunto, $corpo, $user['nome'], $user['email'])->send();
            } catch (\Exception $e) {
                error_log('Erro ao enviar email de recuperação: ' . $e->getMessage());
                return $this->SendJson($response, ['status' => false, 'msg' => 'Erro ao enviar código por email!', 'id' => 0], 500);
            }

            return $this->SendJson($response, ['status' => true, 'msg' => 'Código enviado para o email! Verifique sua caixa de entrada.', 'id' => $user['id']], 200);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição:' . $e->getMessage(), 'id' => 0], 500);
        }
    }

    public function verificarCodigo($request, $response)
    {
        try {
            $form = $request->getParsedBody();

            if (!isset($form['codigo']) || empty(trim($form['codigo']))) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'O código é obrigatório!', 'id' => 0], 403);
            }

            // Verificar se existe sessão de recuperação
            if (!isset($_SESSION['recuperacao_senha'])) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Nenhuma solicitação de recuperação ativa!', 'id' => 0], 403);
            }

            $sessao = $_SESSION['recuperacao_senha'];

            // Verificar se o código expirou (15 minutos)
            if (time() - $sessao['timestamp'] > 900) {
                unset($_SESSION['recuperacao_senha']);
                return $this->SendJson($response, ['status' => false, 'msg' => 'Código expirado! Solicite um novo código.', 'id' => 0], 403);
            }

            // Verificar tentativas (máximo 5)
            if ($sessao['tentativas'] >= 5) {
                unset($_SESSION['recuperacao_senha']);
                return $this->SendJson($response, ['status' => false, 'msg' => 'Limite de tentativas excedido!', 'id' => 0], 403);
            }

            // Verificar código
            if (trim($form['codigo']) !== $sessao['codigo']) {
                $_SESSION['recuperacao_senha']['tentativas']++;
                return $this->SendJson($response, ['status' => false, 'msg' => 'Código inválido!', 'id' => 0], 403);
            }

            return $this->SendJson($response, ['status' => true, 'msg' => 'Código verificado com sucesso!', 'id' => $sessao['id_usuario']], 200);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição:' . $e->getMessage(), 'id' => 0], 500);
        }
    }

    public function atualizarSenha($request, $response)
    {
        try {
            $form = $request->getParsedBody();

            if (!isset($form['senha']) || empty($form['senha'])) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'A nova senha é obrigatória!', 'id' => 0], 403);
            }

            // Verificar se existe sessão de recuperação validada
            if (!isset($_SESSION['recuperacao_senha'])) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Sessão de recuperação inválida!', 'id' => 0], 403);
            }

            $sessao = $_SESSION['recuperacao_senha'];
            $id_usuario = $sessao['id_usuario'];

            // Atualizar senha
            $novaSenhaHash = password_hash($form['senha'], PASSWORD_DEFAULT);
            $updated = UpdateQuery::table('usuario')->set(['senha' => $novaSenhaHash])->where('id', '=', $id_usuario)->update();

            if (!$updated) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Erro ao atualizar a senha!', 'id' => $id_usuario], 500);
            }

            // Limpar sessão de recuperação
            unset($_SESSION['recuperacao_senha']);

            return $this->SendJson($response, ['status' => true, 'msg' => 'Senha atualizada com sucesso! Acesse com sua nova senha.', 'id' => $id_usuario], 200);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição:' . $e->getMessage(), 'id' => 0], 500);
        }
    }
}
