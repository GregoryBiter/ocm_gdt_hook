document.addEventListener('DOMContentLoaded', () => {
    const Toast = Swal.mixin({
        toast: true,
        position: "top",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
        }
      });
    
    window.Toast = Toast;
    window.sweetAlertHelper = {
        success: function (message) {
            Toast.fire({
                icon: "success",
                title: message
            });
        },
        error: function (message) {
            Toast.fire({
                icon: "error",
                title: message
            });
        },
        warning: function (message) {
            Toast.fire({
                icon: "warning",
                title: message
            });
        },
        info: function (message) {
            Toast.fire({
                icon: "info",
                title: message
            });
        },
        question: function (message) {
            Toast.fire({
                icon: "question",
                title: message
            });
        }
    };
});