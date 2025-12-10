
import { Requests } from "./Requests.js";
import { Validate } from "./Validate.js";

const InsertButton = document.getElementById('insert');

$('#cpf_cnpj').inputmask({ "mask": ["999.999.999-99", "99.999.999/9999-99"] });

InsertButton.addEventListener('click', async () => {
    /*const IsValid = Validate
        .SetForm('form')
        .Validate();
    console.log(IsValid);*/
    const response = await Requests.SetForm('form').Post('/cliente/insert');
});
/*
const Salvar = document.getElementById('salvar');

Salvar.addEventListener('click', async () => {
    Validate.SetForm('form').Validate();
    console.log(response);
});*/