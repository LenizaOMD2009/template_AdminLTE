class Requests {
    static form;
    static SetForm(id) {
        this.form = document.getElementById(id);
        if (!this.form) {
            throw new Error("O formulário não foi encontrado!");
        }
        return this;
    }
    
    static async Post(url) {
        try {
            // Converte FormData para um corpo x-www-form-urlencoded (mais compatível com PHP)
            const formData = new FormData(this.form);
            const jsonData = {};

            for (let [key, value] of formData.entries()) {
                jsonData[key] = value;
            }

            console.log('Enviando requisição POST para:', url);
            console.log('Dados:', jsonData);

            const option = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(jsonData),
                credentials: 'same-origin',
                cache: 'default'
            };

            const response = await fetch(url, option);

            console.log('Status da resposta:', response.status, response.statusText);

            const contentType = response.headers.get('content-type') || '';

            // Se o servidor retornou JSON, parseamos independente do status HTTP
            if (contentType.includes('application/json')) {
                const jsonResponse = await response.json();
                console.log('Resposta JSON recebida:', jsonResponse);
                // Retorna o JSON mesmo quando status != 200 para que o frontend mostre a mensagem
                return jsonResponse;
            }

            // Se não for JSON e não OK, jogamos o erro com texto do servidor
            if (!response.ok) {
                const text = await response.text();
                console.error(`Erro HTTP ${response.status}:`, response.statusText);
                console.error('Resposta do servidor (texto):', text);
                throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
            }

            // Resposta não-JSON, mas OK — retornamos o texto bruto
            const text = await response.text();
            return { status: true, msg: text };

        } catch (error) {
            console.error('Erro em Requests.Post:', error);
            throw error;
        }
    }
}
export { Requests };