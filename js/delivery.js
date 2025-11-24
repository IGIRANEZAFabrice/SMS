document.addEventListener('DOMContentLoaded', () => {
    // Main components
    const tableBody = document.getElementById('requestsTableBody');
    const searchInput = document.getElementById('searchInput');

    // Modal components
    const modal = document.getElementById('receiveModal');
    const modalTitle = document.getElementById('modalRequestId');
    const modalItemsContainer = document.getElementById('modalItemsContainer');
    const receiveSubmitBtn = document.getElementById('receiveSubmitBtn');
    const closeBtns = document.querySelectorAll('.close-btn');

    let allRequests = [];
    let currentRequestId = null;

    // --- Initial Load ---
    async function loadRequests() {
        try {
            const response = await fetch('delivery.php?api=1&fetch=requests');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            allRequests = result.data;
            renderTable();
        } catch (error) {
            console.error('Error loading requests:', error);
            Swal.fire('Error', 'Could not load purchase requests: ' + error.message, 'error');
        }
    }

    // --- Rendering ---
    function renderTable(filter = '') {
        const lowerFilter = filter.toLowerCase();
        const filteredRequests = allRequests.filter(req =>
            req.request_id.toString().includes(lowerFilter) ||
            req.supplier_name.toLowerCase().includes(lowerFilter)
        );

        if (filteredRequests.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center">No approved requests found.</td></tr>`;
            return;
        }

        tableBody.innerHTML = filteredRequests.map(req => `
            <tr data-id="${req.request_id}">
                <td>#${req.request_id}</td>
                <td>${req.supplier_name}</td>
                <td>${req.requested_by}</td>
                <td>${new Date(req.request_date).toLocaleDateString()}</td>
                <td><span class="status status-${req.status}">${req.status}</span></td>
                <td>
                    <button class="btn btn-primary btn-sm view-btn">
                        <i class="fas fa-eye"></i> View & Receive
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // --- Modal Handling ---
    function openModal(requestId) {
        currentRequestId = requestId;
        modalTitle.textContent = requestId;
        modal.style.display = 'flex';
        loadModalItems(requestId);
    }

    function closeModal() {
        modal.style.display = 'none';
        modalItemsContainer.innerHTML = '<div class="loader"></div>'; // Reset for next time
        currentRequestId = null;
    }

    async function loadModalItems(requestId) {
        try {
            const response = await fetch(`delivery.php?api=1&fetch=items&request_id=${requestId}`);
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            modalItemsContainer.innerHTML = result.data.map(item => `
                <div class="modal-item" data-item-id="${item.item_id}">
                    <div class="item-name">${item.item_name}</div>
                    <div class="item-qty-req">Requested: ${item.qty_requested}</div>
                    <div class="item-inputs">
                        <div class="form-group">
                            <label>Quantity Received:</label>
                            <input type="number" class="form-control qty-received" min="0" step="1" value="${item.qty_requested}">
                        </div>
                        <div class="form-group">
                            <label>Price per Unit:</label>
                            <input type="number" class="form-control price" min="0.01" step="0.01" placeholder="Enter cost...">
                        </div>
                    </div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading modal items:', error);
            modalItemsContainer.innerHTML = '<p class="text-danger">Could not load items.</p>';
        }
    }

    // --- Event Listeners ---
    searchInput.addEventListener('input', () => renderTable(searchInput.value));
    
    tableBody.addEventListener('click', e => {
        const viewButton = e.target.closest('.view-btn');
        if (viewButton) {
            const row = viewButton.closest('tr');
            const requestId = row.dataset.id;
            openModal(requestId);
        }
    });

    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    receiveSubmitBtn.addEventListener('click', handleReceiveSubmit);


    // --- API Submission ---
    async function handleReceiveSubmit() {
        const itemsToReceive = [];
        const itemElements = modalItemsContainer.querySelectorAll('.modal-item');

        let allPricesEntered = true;
        itemElements.forEach(el => {
            const qty_received = parseFloat(el.querySelector('.qty-received').value);
            const price = parseFloat(el.querySelector('.price').value);

            if (qty_received > 0) {
                if (isNaN(price) || price <= 0) {
                    allPricesEntered = false;
                }
                itemsToReceive.push({
                    item_id: parseInt(el.dataset.itemId),
                    qty_received: qty_received,
                    price: price,
                });
            }
        });
        
        if (!allPricesEntered) {
            Swal.fire('Missing Prices', 'Please enter a valid price for all items you are receiving.', 'warning');
            return;
        }

        if (itemsToReceive.length === 0) {
            Swal.fire('No Items', 'No items have quantities specified for receiving.', 'info');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: "This will update stock levels and cannot be easily undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, receive them!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const payload = {
                        request_id: currentRequestId,
                        items: itemsToReceive,
                    };

                    const response = await fetch('delivery.php?api=1', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const resData = await response.json();
                    if (!resData.success) {
                        throw new Error(resData.message);
                    }

                    Swal.fire('Success!', resData.message, 'success');
                    closeModal();
                    loadRequests(); // Refresh the main table

                } catch (error) {
                    console.error('Submission Error:', error);
                    Swal.fire('Error', 'Failed to receive items: ' + error.message, 'error');
                }
            }
        });
    }

    // --- Initial Load ---
    loadRequests();
});
