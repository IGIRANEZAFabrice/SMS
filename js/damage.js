document.addEventListener("DOMContentLoaded", function () {
  const damagedForm = document.getElementById("damagedForm");
  const damagedTableBody = document.getElementById("damagedTableBody");
  const searchDamaged = document.getElementById("searchDamaged");

  // Handle form submission
  if (damagedForm) {
    damagedForm.addEventListener("submit", function (e) {
      e.preventDefault();

      // Show loading state
      const submitBtn = damagedForm.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

      // Prepare form data
      const formData = new FormData(damagedForm);
      formData.append('action', 'add');

      // Convert FormData to JSON for the API
      const jsonData = {};
      formData.forEach((value, key) => {
        jsonData[key] = value;
      });

      fetch("damage.php?api=1", {
        method: "POST",
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(jsonData)
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Show success message
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Item has been recorded as damaged successfully!',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              // Reset form and reload data
              damagedForm.reset();
              loadDamagedItems();
            });
          } else {
            throw new Error(data.message || 'Failed to record damaged item');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'An error occurred while processing your request.',
            confirmButtonText: 'OK'
          });
        })
        .finally(() => {
          // Reset button state
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        });
    });
  }

  // Function to load damaged items
  function loadDamagedItems() {
    console.log('Loading damaged items...');

    fetch("damage.php?api=1", {
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      }
    })
    .then(response => {
      // Try to parse the JSON body, and if not ok, pass the error message along
      return response.json().then(data => {
        if (!response.ok) {
          return Promise.reject(data); // Reject with the JSON error data from the server
        }
        return data; // Forward the data for successful responses
      });
    })
    .then(data => {
      // This block only handles successful responses with { success: true }
      if (data && data.success) {
        if (Array.isArray(data.data)) {
          console.log(`Loaded ${data.data.length} damaged items`);
          updateDamagedTable(data.data);
        } else {
          console.error('Data is not an array:', data.data);
          updateDamagedTable([]);
        }
      } else {
        // Handle cases where the server returns a 200 OK with a success: false message
        throw new Error(data && data.message ? data.message : 'Invalid response format');
      }
    })
    .catch(error => {
      // This single catch block now handles network errors and JSON-formatted server errors
      console.error("Error loading damaged items:", error);
      const errorMessage = error.message || 'An unknown error occurred.';
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to load damaged items: ' + errorMessage,
        confirmButtonText: 'OK'
      });
      updateDamagedTable([]); // Clear the table on error
    });
  }

  // Function to update the damaged items table
  function updateDamagedTable(items) {
    if (!damagedTableBody) {
      console.error('Table body element not found');
      return;
    }

    console.log('Updating table with items:', items);

    if (!items || items.length === 0) {
      damagedTableBody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center">No damaged items found</td>
        </tr>`;
      return;
    }

    damagedTableBody.innerHTML = items.map(item => `
      <tr>
        <td>${item.id || ''}</td>
        <td>${item.item_name || 'N/A'}</td>
        <td>${item.category_name || 'N/A'}</td>
        <td>${item.unit || 'N/A'}</td>
        <td class="text-right">${item.qty ? parseFloat(item.qty).toFixed(2) : '0.00'}</td>
        <td>${item.message || 'No message'}</td>
        <td>${item.created_at || 'N/A'}</td>
        <td>
          <button class="btn btn-danger btn-sm" onclick="deleteDamaged(${item.id})">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>`).join('');
  }

  // Handle search functionality
  if (searchDamaged) {
    searchDamaged.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase();
      const rows = damagedTableBody.getElementsByTagName("tr");

      Array.from(rows).forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? "" : "none";
      });
    });
  }

  // Delete damaged item
  window.deleteDamaged = function (id) {
    if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
      return;
    }

    fetch('damage.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'delete',
        id: id
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadDamagedItems();
        } else {
          alert(data.message || 'Failed to delete record');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the record');
      });
  };

  // Initial load of damaged items
  loadDamagedItems();

  // Also load items when the page becomes visible again
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      loadDamagedItems();
    }
  });
});
