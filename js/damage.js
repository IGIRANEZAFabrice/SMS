document.addEventListener('DOMContentLoaded', function () {
    const itemSelect = document.getElementById('itemSelect');
    const damagedForm = document.getElementById('damagedForm');
    const damagedTableBody = document.getElementById('damagedTableBody');
    const searchDamaged = document.getElementById('searchDamaged');

    function fetchItems() {
        fetch('../api/items.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    itemSelect.innerHTML = '<option value="">Choose an item...</option>';
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.name} (${item.unit})`;
                        itemSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error fetching items:', error));
    }

    function fetchDamaged() {
        fetch('../api/damage.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    damagedTableBody.innerHTML = '';
                    data.data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.id}</td>
                            <td>${item.item_name}</td>
                            <td>${item.category_name}</td>
                            <td>${item.unit}</td>
                            <td class="text-right">${item.qty}</td>
                            <td>${item.message}</td>
                            <td>${item.created_at}</td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="deleteDamaged(${item.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        damagedTableBody.appendChild(row);
                    });
                }
            })
            .catch(error => console.error('Error fetching damaged items:', error));
    }

    damagedForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = {
            item_id: itemSelect.value,
            qty: document.getElementById('qtyDamaged').value,
            message: document.getElementById('damageMessage').value,
            created_at: document.getElementById('damageDate').value,
            action: 'add'
        };

        fetch('../api/damage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', 'Damaged item recorded successfully.', 'success');
                    damagedForm.reset();
                    fetchDamaged();
                } else {
                    Swal.fire('Error', data.message || 'Failed to record damaged item.', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'An error occurred.', 'error');
                console.error('Error submitting form:', error);
            });
    });

    window.deleteDamaged = function (id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../api/damage.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, action: 'delete' })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', 'The record has been deleted.', 'success');
                            fetchDamaged();
                        } else {
                            Swal.fire('Error', data.message || 'Failed to delete record.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'An error occurred.', 'error');
                        console.error('Error deleting record:', error);
                    });
            }
        });
    };

    searchDamaged.addEventListener('keyup', function () {
        const searchTerm = searchDamaged.value.toLowerCase();
        const rows = damagedTableBody.getElementsByTagName('tr');
        Array.from(rows).forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    fetchItems();
    fetchDamaged();
});