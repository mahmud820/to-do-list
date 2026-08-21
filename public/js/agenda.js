// 🟢 TAMBAH AGENDA
$('#addAgendaForm').on('submit', function (e) {
    e.preventDefault();

    $.ajax({
        url: BASEURL + '/agenda/add',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (response) {
            console.log('AJAX add agenda response:', response);
            if (response && response.status === 'success') {
                Swal.fire({ title: 'Berhasil!', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false });
                $('#addAgendaModal').modal('hide');
                $('#addAgendaForm')[0].reset();
                setTimeout(() => location.reload(), 800);
            } else {
                Swal.fire('Gagal!', response.message || 'Terjadi kesalahan', 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', xhr.responseText);
            Swal.fire('Error!', 'Terjadi kesalahan server: ' + error, 'error');
        }
    });
});

// 🟡 EDIT AGENDA
$(document).on('click', '.btn-edit-agenda', function () {
    $('#edit_id').val($(this).data('id'));
    $('#edit_judul').val($(this).data('judul'));
    $('#edit_tanggal').val($(this).data('tanggal'));
    $('#editAgendaModal').modal('show');
});

$('#editAgendaForm').on('submit', function (e) {
    e.preventDefault();

    $.ajax({
        url: BASEURL + '/agenda/update',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (response) {
            console.log('AJAX update agenda response:', response);
            if (response && response.status === 'success') {
                Swal.fire({ title: 'Berhasil!', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false });
                $('#editAgendaModal').modal('hide');
                setTimeout(() => location.reload(), 800);
            } else {
                Swal.fire('Gagal!', response.message || 'Terjadi kesalahan', 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', xhr.responseText);
            Swal.fire('Error!', 'Terjadi kesalahan server: ' + error, 'error');
        }
    });
});

// 🔴 HAPUS AGENDA
$(document).on('click', '.btn-delete-agenda', function () {
    const id = $(this).data('id');

    Swal.fire({ title: 'Yakin ingin menghapus?', text: 'Agenda akan dihapus beserta itemnya!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
    .then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: BASEURL + '/agenda/delete',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (response) {
                    console.log('AJAX delete agenda response:', response);
                    if (response && response.status === 'success') {
                        Swal.fire({ title: 'Terhapus!', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                        setTimeout(() => location.reload(), 800);
                    } else {
                        Swal.fire('Gagal!', response.message || 'Terjadi kesalahan', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText);
                    Swal.fire('Error!', 'Terjadi kesalahan server: ' + error, 'error');
                }
            });
        }
    });
});

// ➕ TAMBAH ITEM
$(document).on('submit', '.add-item-form', function (e) {
    e.preventDefault();
    const $form = $(this);
    const agendaId = $form.data('agenda-id');
    const nama_item = $form.find('input[name="nama_item"]').val();

    $.ajax({
        url: BASEURL + '/agenda/addItem',
        type: 'POST',
        data: { agenda_id: agendaId, nama_item: nama_item },
        dataType: 'json',
        success: function (response) {
            console.log('AJAX add item response:', response);
            if (response && response.status === 'success') {
                $form[0].reset();
                setTimeout(() => location.reload(), 600);
            } else {
                Swal.fire('Gagal!', response.message || 'Terjadi kesalahan', 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', xhr.responseText);
            Swal.fire('Error!', 'Terjadi kesalahan server: ' + error, 'error');
        }
    });
});

// Toggle status item
$(document).on('change', '.toggle-item', function () {
    const id = $(this).data('id');
    $.ajax({
        url: BASEURL + '/agenda/toggleItem',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            console.log('AJAX toggle item response:', response);
            if (response && response.status === 'success') {
                setTimeout(() => location.reload(), 400);
            } else {
                Swal.fire('Gagal!', response.message || 'Terjadi kesalahan', 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', xhr.responseText);
            Swal.fire('Error!', 'Terjadi kesalahan server: ' + error, 'error');
        }
    });
});

// Hapus item
$(document).on('click', '.btn-delete-item', function () {
    const id = $(this).data('id');

    Swal.fire({ title: 'Yakin ingin menghapus item?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
    .then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: BASEURL + '/agenda/deleteItem',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (response) {
                    console.log('AJAX delete item response:', response);
                    if (response && response.status === 'success') {
                        setTimeout(() => location.reload(), 400);
                    } else {
                        Swal.fire('Gagal!', response.message || 'Terjadi kesalahan', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText);
                    Swal.fire('Error!', 'Terjadi kesalahan server: ' + error, 'error');
                }
            });
        }
    });
});
