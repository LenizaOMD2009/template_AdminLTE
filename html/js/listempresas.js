const tabela = new $('#tabela').DataTable({
    paging: true,
    lengthChange: true,
    searching: true,
    ordering: true,
    info: true,
    autoWidth: false,
    responsive: true,
    stateSave: true,
    select: true,
    processing: true,
    serverSide: true,
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json',
        searchPlaceholder: 'Digite sua pesquisa...',
    },
    ajax: {
        url: '/empresas/listempresas',
        type: 'POST'
    }
});
async function Delete(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    const response = await fetch('/empresas/delete', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    
    if (!data.status) {
        Swal.fire({
            title: "Erro ao remover!",
            icon: "error",
            html: data.msg,
            timer: 3000,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        return;
    }
    Swal.fire({
        title: "Removido com sucesso!",
        icon: "success",
        html: data.msg,
        timer: 3000,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    tabela.ajax.reload();
}
window.Delete = Delete;
