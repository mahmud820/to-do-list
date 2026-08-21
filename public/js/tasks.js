// 🟢 TAMBAH TASK
document.getElementById("addTaskForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
        const response = await fetch(BASEURL + "/tasks/add", {
            method: "POST",
            body: new FormData(this)
        });

        const result = await response.json();

        console.log("AJAX add response:", result);

        if (result.status === "success") {
            Swal.fire({
                title: "Berhasil!",
                text: result.message,
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });

            const modal = bootstrap.Modal.getInstance(
                document.getElementById("addTaskModal")
            );
            modal.hide();

            this.reset();

            setTimeout(() => location.reload(), 1000);
        } else {
            Swal.fire("Gagal!", result.message || "Terjadi kesalahan", "error");
        }
    } catch (error) {
        console.error(error);
        Swal.fire("Error!", "Terjadi kesalahan server", "error");
    }
});


// 🟡 UPDATE TASK
document.getElementById("editTaskForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
        const response = await fetch(BASEURL + "/tasks/update", {
            method: "POST",
            body: new FormData(this)
        });

        const result = await response.json();

        console.log("AJAX update response:", result);

        if (result.status === "success") {
            Swal.fire({
                title: "Berhasil!",
                text: result.message,
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });

            const modal = bootstrap.Modal.getInstance(
                document.getElementById("editTaskModal")
            );
            modal.hide();

            this.reset();

            setTimeout(() => location.reload(), 1000);
        } else {
            Swal.fire("Gagal!", result.message || "Terjadi kesalahan", "error");
        }
    } catch (error) {
        console.error(error);
        Swal.fire("Error!", "Terjadi kesalahan server", "error");
    }
});


// ✏️ Event Delegation untuk tombol Edit
document.addEventListener("click", function (e) {
    if (e.target.classList.contains("btn-edit")) {
        document.getElementById("edit_id").value =
            e.target.dataset.id;

        document.getElementById("edit_judul").value =
            e.target.dataset.judul;

        document.getElementById("edit_deskripsi").value =
            e.target.dataset.deskripsi;

        document.getElementById("edit_deadline").value =
            e.target.dataset.deadline;

        const modal = new bootstrap.Modal(
            document.getElementById("editTaskModal")
        );

        modal.show();
    }
});


// 🔴 DELETE TASK
document.addEventListener("click", function (e) {
    if (e.target.classList.contains("btn-delete")) {
        const id = e.target.dataset.id;

        Swal.fire({
            title: "Yakin ingin menghapus?",
            text: "Task yang dihapus tidak bisa dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal"
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch(BASEURL + "/tasks/delete", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `id=${encodeURIComponent(id)}`
                    });

                    const data = await response.json();

                    console.log("AJAX delete response:", data);

                    if (data.status === "success") {
                        Swal.fire({
                            title: "Terhapus!",
                            text: data.message,
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire(
                            "Gagal!",
                            data.message || "Terjadi kesalahan",
                            "error"
                        );
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire(
                        "Error!",
                        "Terjadi kesalahan server",
                        "error"
                    );
                }
            }
        });
    }
});