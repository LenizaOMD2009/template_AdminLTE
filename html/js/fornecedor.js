import { Validate } from "./Validate.js";
import { Requests } from "./Requests.js";
const Salvar = document.getElementById('insert');

$('#cpf').inputmask({ "mask": ["999.999.999-99", "99.999.999/9999-99"] });
$('#tel').inputmask({ "mask": ["(99) 99999-9999"] });

Salvar.addEventListener('click', async () => {
    Validate.SetForm('form').Validate();

    const response = await Requests.SetForm('form').Post('/fornecedor/insert');
    console.log(response);
    if (!response.status) {
        Swal.fire({
            icon: "error",
            title: "Eita patrão!",
            text: response.msg,
        });
        return
    }

    Swal.fire({
        title: "Cadastro realizado com sucesso!",
        icon: "success",
        draggable: true
    });

});