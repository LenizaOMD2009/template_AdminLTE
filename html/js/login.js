import { Validate } from "./Validate.js";
import { Requests } from "./Requests.js";

const preCadastro = document.getElementById('precadastro');
const Login = document.getElementById('entrar');

function applyMask(selector, mask) {
    try {
        // robinherbots/inputmask (jquery plugin)
        if (typeof window.$ !== 'undefined' && window.$.fn && typeof window.$.fn.inputmask === 'function') {
            window.$(selector).inputmask({ mask: Array.isArray(mask) ? mask : [mask] });
            return;
        }

        // standalone Inputmask
        if (typeof window.Inputmask !== 'undefined') {
            const m = Array.isArray(mask) ? mask[0] : mask;
            window.Inputmask({ mask: m }).mask(document.querySelectorAll(selector));
            return;
        }

        // jQuery Mask Plugin (uses .mask)
        if (typeof window.$ !== 'undefined' && window.$.fn && typeof window.$.fn.mask === 'function') {
            const m = Array.isArray(mask) ? mask[0].replace(/9/g, '0') : mask.replace(/9/g, '0');
            window.$(selector).mask(m);
            return;
        }

        console.warn('Nenhum plugin de máscara disponível para', selector);
    } catch (e) {
        console.error('Erro aplicando máscara em', selector, e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    applyMask('#cpf', '999.999.999-99');
    applyMask('#celular', '(99) 99999-9999');
    applyMask('#whatsapp', '(99) 99999-9999');
    applyMask('#rg', '99999999');

    if (preCadastro) {
        preCadastro.addEventListener('click', async () => {
            try {
                const response = await Requests.SetForm('form').Post('/login/precadastro');
                if (!response.status) {
                    Swal.fire({
                        title: "Atenção!",
                        text: response.msg,
                        icon: "error",
                        timer: 3000
                    });
                    return;
                }

                Swal.fire({
                    title: "Sucesso!",
                    text: response.msg,
                    icon: "success",
                    timer: 3000
                });

                // Fechar modal via Bootstrap
                const modalEl = document.getElementById('pre-cadastro');
                if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    const modal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                    modal.hide();
                } else if (window.$) {
                    window.$('#pre-cadastro').modal('hide');
                }
            } catch (error) {
                console.log(error);
            }
        });
    }
});


// end DOMContentLoaded handler
const form = document.getElementById('form');

if (Login) {
    Login.addEventListener('click', async () => {
        try {
            const response = await Requests.SetForm('form').Post('/login/autenticar');
            if (!response.status) {
                Swal.fire({
                    title: "Atenção!",
                    text: response.msg,
                    icon: "error",
                    timer: 3000
                });
                return;
            }

            Swal.fire({
                title: "Sucesso!",
                text: response.msg,
                icon: "success",
                timer: 2000
            }).then(() => {
                window.location.href = '/';
            });
        } catch (error) {
            console.log(error);
        }
    });
} else {
    console.warn('Formulário de login não encontrado: #form');
}

// Recuperar senha
const recuperarBtn = document.getElementById('recuperarEnviar');

if (recuperarBtn) {
    recuperarBtn.addEventListener('click', async () => {
        try {
            const identificador = (document.getElementById('identificador') || {}).value || '';
            const senha = (document.getElementById('senharec') || {}).value || '';
            const senhaConfirm = (document.getElementById('senharecconfirm') || {}).value || '';
            const msgErro = document.getElementById('msgErroEtapa1');

            if (!identificador.trim()) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = 'Informe o identificador.';
                return;
            }
            if (!senha) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = 'Informe a nova senha.';
                return;
            }
            if (senha !== senhaConfirm) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = 'As senhas não conferem.';
                return;
            }

            const body = new URLSearchParams({ identificador: identificador.trim() });

            const option = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body,
                credentials: 'same-origin'
            };

            const resp = await fetch('/login/recuperar', option);
            const json = await resp.json();

            if (!json || !json.status) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = (json && json.msg) ? json.msg : 'Erro ao recuperar senha.';
                return;
            }

            // Armazenar dados na sessão do navegador
            sessionStorage.setItem('recupSenha', JSON.stringify({
                identificador: identificador.trim(),
                senha: senha
            }));

            // Ir para etapa 2
            document.getElementById('etapa1').classList.add('d-none');
            document.getElementById('footerEtapa1').classList.add('d-none');
            document.getElementById('etapa2').classList.remove('d-none');
            document.getElementById('footerEtapa2').classList.remove('d-none');
            document.getElementById('codigoVerificacao').focus();

            Swal.fire({
                title: 'Sucesso!',
                text: json.msg,
                icon: 'success',
                timer: 2000
            });
        } catch (error) {
            console.error(error);
            Swal.fire({ title: 'Erro', text: 'Ocorreu um erro inesperado.', icon: 'error' });
        }
    });
}

// Verificar código
const verificarCodigoBtn = document.getElementById('verificarCodigo');

if (verificarCodigoBtn) {
    verificarCodigoBtn.addEventListener('click', async () => {
        try {
            const codigo = (document.getElementById('codigoVerificacao') || {}).value || '';
            const msgErro = document.getElementById('msgErroEtapa2');

            if (!codigo.trim()) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = 'Informe o código.';
                return;
            }

            if (codigo.length !== 6 || isNaN(codigo)) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = 'O código deve ter 6 dígitos.';
                return;
            }

            const body = new URLSearchParams({ codigo: codigo.trim() });

            const option = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body,
                credentials: 'same-origin'
            };

            const resp = await fetch('/login/verificarCodigo', option);
            const json = await resp.json();

            if (!json || !json.status) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = (json && json.msg) ? json.msg : 'Código inválido.';
                return;
            }

            // Código verificado com sucesso - ir para etapa 3
            const recupData = JSON.parse(sessionStorage.getItem('recupSenha'));

            // Atualizar senha
            const bodyAtualizar = new URLSearchParams({ senha: recupData.senha });

            const optionAtualizar = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: bodyAtualizar,
                credentials: 'same-origin'
            };

            const respAtualizar = await fetch('/login/atualizarSenha', optionAtualizar);
            const jsonAtualizar = await respAtualizar.json();

            if (!jsonAtualizar || !jsonAtualizar.status) {
                msgErro.classList.remove('d-none');
                msgErro.textContent = (jsonAtualizar && jsonAtualizar.msg) ? jsonAtualizar.msg : 'Erro ao atualizar senha.';
                return;
            }

            // Limpar dados armazenados
            sessionStorage.removeItem('recupSenha');

            // Ir para etapa 3
            document.getElementById('etapa2').classList.add('d-none');
            document.getElementById('footerEtapa2').classList.add('d-none');
            document.getElementById('etapa3').classList.remove('d-none');
            document.getElementById('footerEtapa3').classList.remove('d-none');

            // Redirecionar após 2 segundos
            setTimeout(() => {
                const modalEl = document.getElementById('recuperarsenha');
                if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    const modal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                    modal.hide();
                }
                // Limpar inputs
                document.getElementById('identificador').value = '';
                document.getElementById('senharec').value = '';
                document.getElementById('senharecconfirm').value = '';
                document.getElementById('codigoVerificacao').value = '';
                // Voltar para etapa 1
                document.getElementById('etapa1').classList.remove('d-none');
                document.getElementById('footerEtapa1').classList.remove('d-none');
                document.getElementById('etapa3').classList.add('d-none');
                document.getElementById('footerEtapa3').classList.add('d-none');
                document.getElementById('msgErroEtapa1').classList.add('d-none');
                document.getElementById('msgErroEtapa2').classList.add('d-none');
            }, 2000);
        } catch (error) {
            console.error(error);
            const msgErro = document.getElementById('msgErroEtapa2');
            msgErro.classList.remove('d-none');
            msgErro.textContent = 'Ocorreu um erro inesperado.';
        }
    });
}