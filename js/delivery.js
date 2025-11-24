document.addEventListener('DOMContentLoaded', () => {
    // Main components
    const deliveryGrid = document.getElementById('deliveryGrid');
    const searchInput = document.getElementById('searchInput');

    // Modal components
    const modal = document.getElementById('receiveModal');
    const modalRequestId = document.getElementById('modalRequestId');
    const modalItemsContainer = document.getElementById('modalItemsContainer');
    const receiveSubmitBtn = document.getElementById('receiveSubmitBtn');
    const closeBtns = document.querySelectorAll('.close-btn');

    let allRequests = [];
    let currentRequestId = null;

    async function loadRequests() {
        try {
            const response = await fetch('delivery.php?api=1&fetch=requests');
            const responseText = await response.text();

            // Log the raw response for debugging
            console.log('Raw response:', responseText.substring(0, 200) + '...');

            // Check if response is HTML (which would indicate an error)
            if (responseText.trim().startsWith('<!DOCTYPE') || responseText.includes('</html>')) {
                const errorMatch = responseText.match(/<title>([^<]+)<\/title>/i);
                const errorMessage = errorMatch ? errorMatch[1] : 'Server returned an HTML error page';
                throw new Error(`Server Error: ${errorMessage}`);
            }

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                console.error('Response was:', responseText);
                throw new Error('Invalid response from server. Please try again.');
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} - ${result.message || 'Unknown error'}`);
            }

            if (!result.success) {
                throw new Error(result.message || 'Request failed');
            }

            allRequests = result.data || [];
            renderCards();

        } catch (error) {
            console.error('Error loading requests:', error);
            const errorHtml = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                    <h3>Error Loading Requests</h3>
                    <p>${error.message}</p>
                    <p>Status: ${error.status || 'N/A'}</p>
                    <div class="mt-3">
                        <button class="btn btn-secondary" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i> Try Again
                        </button>
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login Page
                        </a>
                    </div>
                </div>`;

            deliveryGrid.innerHTML = errorHtml;

            // If it's a session issue, redirect to login
            if (error.message.includes('session') || error.message.includes('login') || error.message.includes('401')) {
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 3000);
            }
        }
    }

    function getStatusBadge(status) {
        const statusMap = {
            'pending': { class: 'pending', text: 'Pending' },
            'approved': { class: 'approved', text: 'Approved' },
            'received': { class: 'received', text: 'Received' }
        };
        const statusInfo = statusMap[status] || { class: 'secondary', text: status };
        return `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
    }

    function renderCards(filter = '') {
        const lowerFilter = filter.toLowerCase();
        const filteredRequests = allRequests.filter(req =>
            req.request_id.toString().includes(lowerFilter) ||
            req.supplier_name.toLowerCase().includes(lowerFilter) ||
            req.status.toLowerCase().includes(lowerFilter)
        );

        if (filteredRequests.length === 0) {
            deliveryGrid.innerHTML = '<div class="empty-state"><i class="fas fa-box-open fa-3x"></i><p>No deliveries match your search.</p></div>';
            return;
        }

        deliveryGrid.innerHTML = filteredRequests.map(req => `
            <div class="delivery-card status-${req.status}" data-id="${req.request_id}">
                <div class="card-header">
                    <h3><span class="request-id">#${req.request_id}</span></h3>
                    ${getStatusBadge(req.status)}
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="label">Supplier</span>
                        <span class="value">${req.supplier_name}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Request Date</span>
                        <span class="value">${new Date(req.request_date).toLocaleDateString()}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Items</span>
                        <span class="value">${req.item_count || 0} types</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Total Qty</span>
                        <span class="value">${parseFloat(req.total_qty || 0).toFixed(2)}</span>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary view-btn" ${req.status === 'received' ? 'disabled' : ''}>
                        <i class="fas ${req.status === 'received' ? 'fa-check-circle' : 'fa-dolly-flatbed'}"></i> 
                        ${req.status === 'received' ? 'Received' : 'View & Receive'}
                    </button>
                </div>
            </div>
        `).join('');
    }

    function openModal(requestId) {
        currentRequestId = requestId;
        modalRequestId.textContent = requestId;
        modal.style.display = 'flex';
        loadModalItems(requestId);
    }

    function closeModal() {
        modal.style.display = 'none';
        modalItemsContainer.innerHTML = '<div class="loader"></div>';
        currentRequestId = null;
    }

    async function loadModalItems(requestId) {
        try {
            const response = await fetch(`delivery.php?api=1&fetch=items&request_id=${requestId}`);
            const responseText = await response.text();

            // Check if response is HTML (which would indicate an error)
            if (responseText.trim().startsWith('<!DOCTYPE') || responseText.includes('</html>')) {
                throw new Error('Session expired. Please refresh the page and try again.');
            }

            const result = JSON.parse(responseText);
            if (!result.success) throw new Error(result.message || 'Failed to load items');

            const request = allRequests.find(req => req.request_id == requestId);
            const isReceived = request?.status === 'received';
            receiveSubmitBtn.style.display = isReceived ? 'none' : 'block';

            modalItemsContainer.innerHTML = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Requested</th>
                            <th class="text-right" width="120px">Received Qty</th>
                            <th class="text-right" width="140px">Price/Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${result.data.map(item => `
                            <tr class="modal-item" data-item-id="${item.item_id}">
                                <td>${item.item_name}</td>
                                <td class="text-right">${parseFloat(item.qty_requested).toFixed(2)}</td>
                                <td>
                                    <input type="number" class="form-control text-right qty-received" min="0" value="${parseFloat(item.qty_requested).toFixed(2)}" ${isReceived ? 'readonly' : ''}>
                                </td>
                                <td>
                                    <input type="number" class="form-control text-right price" min="0.01" step="0.01" placeholder="0.00" ${isReceived ? 'readonly' : ''}>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (error) {
            console.error('Error loading modal items:', error);
            modalItemsContainer.innerHTML = '<p class="text-danger">Could not load items.</p>';
        }
    }

    async function handleReceiveSubmit() {
        const itemsToReceive = [];
        let allPricesEntered = true;

        modalItemsContainer.querySelectorAll('.modal-item').forEach(el => {
            const qty_received = parseFloat(el.querySelector('.qty-received').value);
            const price = parseFloat(el.querySelector('.price').value);

            if (qty_received > 0) {
                if (isNaN(price) || price <= 0) allPricesEntered = false;
                itemsToReceive.push({
                    item_id: parseInt(el.dataset.itemId),
                    qty_received,
                    price,
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
            text: "This will update stock levels and cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, receive them!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('delivery.php?api=1', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: currentRequestId, items: itemsToReceive })
                    });
                    const resData = await response.json();
                    if (!resData.success) throw new Error(resData.message);

                    Swal.fire('Success!', resData.message, 'success');
                    closeModal();
                    loadRequests();
                } catch (error) {
                    Swal.fire('Error', 'Failed to receive items: ' + error.message, 'error');
                }
            }
        });
    }

    searchInput.addEventListener('input', () => renderCards(searchInput.value));
    deliveryGrid.addEventListener('click', e => {
        const card = e.target.closest('.delivery-card');
        if (card) {
            openModal(card.dataset.id);
        }
    });
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });
    receiveSubmitBtn.addEventListener('click', handleReceiveSubmit);

    loadRequests();
});

