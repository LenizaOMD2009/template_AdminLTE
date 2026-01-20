class Validate {
    static form;
    
    // Validar CPF
    static validateCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        
        let soma = 0;
        let resto;
        
        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;
        
        soma = 0;
        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;
        
        return true;
    }
    
    // Validar CNPJ
    static validateCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
        
        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
        
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(0))) return false;
        
        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(1))) return false;
        
        return true;
    }
    
    // Validar CPF ou CNPJ
    static validateCPFOrCNPJ(value) {
        const cleaned = value.replace(/\D/g, '');
        if (cleaned.length === 11) {
            return this.validateCPF(value);
        } else if (cleaned.length === 14) {
            return this.validateCNPJ(value);
        }
        return false;
    }
    
    static SetForm(id) {
        // checa dependências
        if (typeof jQuery === 'undefined') {
            throw new Error('jQuery não encontrado. Certifique-se de incluir jQuery antes deste script.');
        }
        if (typeof jQuery.validator === 'undefined') {
            throw new Error('jQuery Validation plugin não encontrado. Inclua o plugin jquery.validate.js.');
        }
        
        // Adiciona método customizado para CPF/CNPJ
        jQuery.validator.addMethod('cpfOrCnpj', (value) => {
            if (!value) return true;
            return this.validateCPFOrCNPJ(value);
        }, 'CPF ou CNPJ inválido');
        
        // Adiciona método customizado para CPF
        jQuery.validator.addMethod('cpf', (value) => {
            if (!value) return true;
            return this.validateCPF(value);
        }, 'CPF inválido');
        
        // Adiciona método customizado para RG
        jQuery.validator.addMethod('rg', (value) => {
            if (!value) return true;
            return /^\d{1,2}\.\d{3}\.\d{3}$|^\d{7,8}$/.test(value);
        }, 'RG inválido');
        
        // Adiciona método customizado para data
        jQuery.validator.addMethod('validDate', (value) => {
            if (!value) return true;
            const date = new Date(value);
            return date instanceof Date && !isNaN(date);
        }, 'Data inválida');
        
        // configura defaults do plugin (continua compatível com seu código original)
        jQuery.validator.setDefaults({
            // rules podem ser definidos via atributos HTML ou ao chamar .validate({ rules: ... }) no form
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function (element, errorClass, validClass) {
                jQuery(element).addClass('is-invalid');
            },
            unhighlight: function (element, errorClass, validClass) {
                jQuery(element).removeClass('is-invalid');
            }
        });
        this.form = jQuery(`#${id}`);
        if (!this.form || this.form.length === 0) {
            throw new Error("Formulário não encontrado!");
        }
        // inicializa o validator para o formulário (gera o validator e aplica regras/placements)
        // não sobrescrevemos regras existentes: se você quiser regras por JS, pode passar um objeto aqui
        this.form.validate();
        return this;
    }
    static Validate() {
        if (!this.form || this.form.length === 0) {
            throw new Error("Formulário não inicializado. Chame Validate.SetForm(id) primeiro.");
        }
        // garante que o validator esteja inicializado
        if (!this.form.data('validator')) {
            this.form.validate();
        }
        // retorna boolean indicando se o formulário é válido
        return this.form.valid();
    }
}
export { Validate };