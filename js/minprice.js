document.addEventListener('DOMContentLoaded', () => {
    const minPriceTableBody = document.getElementById('minPriceTableBody');
    const searchInput = document.getElementById('searchMinPrice');

    // Function to fetch and display items
    const fetchItems = async () => {
        try {
            const response = await fetch('../api/minprice.php?api=items');
            const result = await response.json();

            if (result.success) {
                minPriceTableBody.innerHTML = ''; // Clear existing rows
                result.data.forEach(item => {
                    const row = minPriceTableBody.insertRow();
                    row.dataset.itemId = item.item_id;
                    row.innerHTML = `
                        <td>${item.item_id}</td>
                        <td>${item.item_name}</td>
                        <td>${parseFloat(item.price).toFixed(2)}</td>
                        <td>
                            <input
                                type="number"
                                class="form-control min-price-input"
                                value="${parseFloat(item.min_price).toFixed(2)}"
                                step="0.01"
                                data-item-id="${item.item_id}"
                            />
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm update-min-price-btn" data-item-id="${item.item_id}">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </td>
                    `;
                });
            } else {
                Swal.fire('Error', result.message || 'Failed to fetch items.', 'error');
            }
        } catch (error) {
            console.error('Error fetching items:', error);
            Swal.fire('Error', 'An error occurred while fetching items.', 'error');
        }
    };

    // Function to handle minimum price update
    const updateMinPrice = async (itemId, newMinPrice) => {
        try {
            const response = await fetch('../api/minprice.php?api=updateMinPrice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ item_id: itemId, min_price: newMinPrice }),
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire('Success', 'Minimum price updated successfully!', 'success');
            } else {
                Swal.fire('Error', result.message || 'Failed to update minimum price.', 'error');
            }
        } catch (error) {
            console.error('Error updating minimum price:', error);
            Swal.fire('Error', 'An error occurred while updating minimum price.', 'error');
        }
    };

    // Event listener for update buttons
    minPriceTableBody.addEventListener('click', (event) => {
        if (event.target.classList.contains('update-min-price-btn') || event.target.closest('.update-min-price-btn')) {
            const button = event.target.closest('.update-min-price-btn');
            const itemId = button.dataset.itemId;
            const inputElement = minPriceTableBody.querySelector(`.min-price-input[data-item-id="${itemId}"]`);
            const newMinPrice = parseFloat(inputElement.value);

            if (!isNaN(newMinPrice) && newMinPrice >= 0) {
                updateMinPrice(itemId, newMinPrice);
            } else {
                Swal.fire('Invalid Input', 'Please enter a valid non-negative minimum price.', 'warning');
            }
        }
    });

    // Search functionality (simple client-side filter)
    searchInput.addEventListener('keyup', () => {
        searchTable('minPriceTable', searchInput.value);
    });

    // Generic search function for tables (can be moved to a common utility file if needed)
    window.searchTable = (tableId, filter) => {
        const table = document.getElementById(tableId);
        if (!table) return;

        const tr = table.getElementsByTagName('tr');
        filter = filter.toUpperCase();

        for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header row
            let td = tr[i].getElementsByTagName('td');
            let found = false;
            for (let j = 0; j < td.length - 1; j++) { // Loop through all cells except the last (actions)
                if (td[j]) {
                    const textValue = td[j].textContent || td[j].innerText;
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? '' : 'none';
        }
    };

    // Initial fetch of items when the page loads
    fetchItems();
});