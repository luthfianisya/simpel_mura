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

/**
 * Notifikasi kecil di kanan atas yang hilang sendiri (toastr bawaan tema). icon: success |
 * error | warning | info. Kalau toastr belum termuat, jatuh ke toast SweetAlert.
 */
function swalToast(title, icon, timer) {
    if (window.toastr) {
        const jenis = { success: 'success', error: 'error', warning: 'warning', info: 'info' }[icon] || 'success';
        const judul = { success: 'Berhasil', error: 'Gagal', warning: 'Perhatian', info: 'Info' }[jenis];
        return toastr[jenis](title, judul, {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            preventDuplicates: true,
            positionClass: 'toast-top-right',
            timeOut: timer || 3000,
            extendedTimeOut: 1000,
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',
        });
    }
    return Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon || 'success',
        title: title,
        showConfirmButton: false,
        timer: timer || 2500,
        timerProgressBar: false,
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
