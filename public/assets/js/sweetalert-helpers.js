'use strict';

/**
 * Konfirmasi berbasis SweetAlert2 untuk <form onsubmit="return confirmSubmit(this, '...')">.
 * form.submit() tidak memicu event 'submit' lagi, jadi aman dari infinite loop.
 */
function confirmSubmit(form, message, options) {
    options = options || {};
    Swal.fire({
        title: message,
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || 'Ya',
        cancelButtonText: 'Batal',
        confirmButtonColor: options.confirmButtonColor || '#dc3545',
        reverseButtons: true,
    }).then(function (result) {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}

/** Pengganti confirm() biasa, dipakai di kode async (mengembalikan Promise<boolean>). */
function swalConfirm(message, options) {
    options = options || {};
    return Swal.fire({
        title: message,
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || 'Ya',
        cancelButtonText: 'Batal',
        confirmButtonColor: options.confirmButtonColor || '#dc3545',
        reverseButtons: true,
    }).then(function (result) {
        return result.isConfirmed;
    });
}

/** Pengganti alert() untuk pesan error. */
function swalError(message) {
    return Swal.fire({
        title: 'Gagal',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK',
    });
}

/** Pengganti alert() untuk pesan sukses. */
function swalSuccess(message) {
    return Swal.fire({
        title: 'Berhasil',
        text: message,
        icon: 'success',
        confirmButtonText: 'OK',
    });
}
