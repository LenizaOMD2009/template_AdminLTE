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

            if (!identificador.trim()) {
                Swal.fire({ title: 'Atenção!', text: 'Informe o identificador.', icon: 'warning' });
                return;
            }
            if (!senha) {
                Swal.fire({ title: 'Atenção!', text: 'Informe a nova senha.', icon: 'warning' });
                return;
            }
            if (senha !== senhaConfirm) {
                Swal.fire({ title: 'Atenção!', text: 'As senhas não conferem.', icon: 'warning' });
                return;
            }

            const body = new URLSearchParams({ identificador: identificador.trim(), senha });

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
                Swal.fire({ title: 'Erro', text: (json && json.msg) ? json.msg : 'Erro ao recuperar senha.', icon: 'error' });
                return;
            }

            Swal.fire({ title: 'Sucesso!', text: json.msg, icon: 'success', timer: 2000 });

            // Fecha o modal
            const modalEl = document.getElementById('recuperarsenha');
            if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                const modal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                modal.hide();
            } else if (window.$) {
                window.$('#recuperarsenha').modal('hide');
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ title: 'Erro', text: 'Ocorreu um erro inesperado.', icon: 'error' });
        }
    });
}