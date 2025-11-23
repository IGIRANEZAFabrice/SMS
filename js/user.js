let roles = [];
let users = [];
const apiBase = "/SMS/pages/user.php";

async function fetchRoles() {
  const res = await fetch(`${apiBase}?api=roles`);
  const data = await res.json();
  roles = data && data.success ? data.data : [];
  loadRoles();
}

async function fetchUsers() {
  const res = await fetch(`${apiBase}?api=users`);
  const data = await res.json();
  users = data && data.success ? data.data : [];
  loadUsers();
}

// Tab switching
function switchTab(tab) {
  const tabs = document.querySelectorAll(".tab-btn");
  const contents = document.querySelectorAll(".tab-content");
  tabs.forEach((t) => t.classList.remove("active"));
  contents.forEach((c) => c.classList.remove("active"));
  if (tab === "roles") {
    tabs[0]?.classList.add("active");
  } else {
    tabs[1]?.classList.add("active");
  }
  document.getElementById(`${tab}Tab`).classList.add("active");
}

// Load roles into table
function loadRoles() {
  const tbody = document.getElementById("rolesTableBody");
  tbody.innerHTML = "";

  if (roles.length === 0) {
    tbody.innerHTML = `
            <tr>
              <td colspan="3">
                <div class="empty-state">
                  <i class="fas fa-user-tag"></i>
                  <p>No roles found. Add your first role!</p>
                </div>
              </td>
            </tr>
          `;
    return;
  }

  roles.forEach((role) => {
    const row = `
            <tr>
              <td>${role.role_id}</td>
              <td>${role.role_name}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editRole(${role.role_id})">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteRole(${role.role_id})">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </div>
              </td>
            </tr>
          `;
    tbody.innerHTML += row;
  });

  updateRoleDropdown();
}

// Load users into table
function loadUsers() {
  const tbody = document.getElementById("usersTableBody");
  tbody.innerHTML = "";

  if (users.length === 0) {
    tbody.innerHTML = `
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <i class="fas fa-users"></i>
                  <p>No users found. Add your first user!</p>
                </div>
              </td>
            </tr>
          `;
    return;
  }

  users.forEach((user) => {
    const role = roles.find((r) => r.role_id === user.role_id);
    const roleName = role ? role.role_name : "Unknown";
    const status =
      user.is_active === 1
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-danger">Disabled</span>';

    const row = `
            <tr>
              <td>${user.user_id}</td>
              <td>${user.fullname}</td>
              <td>${user.email}</td>
              <td>${roleName}</td>
              <td>${status}</td>
              <td>${user.created_at}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editUser(${
                    user.user_id
                  })">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm ${
                    user.is_active ? "btn-danger" : "btn-success"
                  }" 
                          onclick="toggleUserStatus(${user.user_id})">
                    <i class="fas fa-${user.is_active ? "ban" : "check"}"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteUser(${
                    user.user_id
                  })">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
    tbody.innerHTML += row;
  });
}

// Update role dropdown
function updateRoleDropdown() {
  const select = document.getElementById("userRole");
  select.innerHTML = '<option value="">Select role</option>';
  roles.forEach((role) => {
    select.innerHTML += `<option value="${role.role_id}">${role.role_name}</option>`;
  });
}

// Add Role
document.getElementById("addRoleForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const roleName = document.getElementById("roleName").value.trim();

  if (!roleName) return;
  fetch(`${apiBase}?api=roles`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "add", role_name: roleName }),
  })
    .then((r) => r.json())
    .then((resp) => {
      if (resp && resp.success) {
        document.getElementById("addRoleForm").reset();
        fetchRoles();
        Swal.fire({
          icon: "success",
          title: "Role Added!",
          text: `${roleName} has been added successfully`,
          timer: 2000,
          showConfirmButton: false,
        });
      }
    });
});

// Add User
document.getElementById("addUserForm").addEventListener("submit", (e) => {
  e.preventDefault();

  const fullname = document.getElementById("userFullname").value.trim();
  const email = document.getElementById("userEmail").value.trim();
  const password = document.getElementById("userPassword").value;
  const role_id = parseInt(document.getElementById("userRole").value);

  if (!fullname || !email || !password || !role_id) return;
  fetch(`${apiBase}?api=users`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "add", fullname, email, password, role_id }),
  })
    .then((r) => r.json())
    .then((resp) => {
      if (resp && resp.success) {
        document.getElementById("addUserForm").reset();
        fetchUsers();
        Swal.fire({
          icon: "success",
          title: "User Added!",
          text: `${fullname} has been added successfully`,
          timer: 2000,
          showConfirmButton: false,
        });
      } else {
        Swal.fire({ icon: "error", title: "Email Exists", text: "This email is already registered" });
      }
    });
});

// Edit Role
function editRole(roleId) {
  const role = roles.find((r) => r.role_id === roleId);
  if (!role) return;

  Swal.fire({
    title: "Edit Role",
    input: "text",
    inputValue: role.role_name,
    inputPlaceholder: "Enter role name",
    showCancelButton: true,
    confirmButtonText: "Update",
    confirmButtonColor: "#3b82f6",
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      const name = result.value.trim();
      fetch(`${apiBase}?api=roles`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "update", role_id: roleId, role_name: name }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchRoles();
            Swal.fire({
              icon: "success",
              title: "Updated!",
              text: "Role has been updated",
              timer: 2000,
              showConfirmButton: false,
            });
          }
        });
    }
  });
}

// Delete Role
function deleteRole(roleId) {
  // Check if role is being used
  const isUsed = users.some((u) => u.role_id === roleId);
  if (isUsed) {
    Swal.fire({
      icon: "error",
      title: "Cannot Delete",
      text: "This role is assigned to users",
    });
    return;
  }

  Swal.fire({
    title: "Delete Role?",
    text: "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    confirmButtonText: "Yes, delete it!",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`${apiBase}?api=roles`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", role_id: roleId }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchRoles();
            Swal.fire({
              icon: "success",
              title: "Deleted!",
              text: "Role has been deleted",
              timer: 2000,
              showConfirmButton: false,
            });
          } else {
            Swal.fire({ icon: "error", title: "Cannot Delete", text: resp && resp.message ? resp.message : "Error" });
          }
        });
    }
  });
}

// Edit User
function editUser(userId) {
  const user = users.find((u) => u.user_id === userId);
  if (!user) return;

  const roleOptions = roles
    .map(
      (r) =>
        `<option value="${r.role_id}" ${
          r.role_id === user.role_id ? "selected" : ""
        }>${r.role_name}</option>`
    )
    .join("");

  Swal.fire({
    title: "Edit User",
    html: `
            <input id="swal-fullname" class="swal2-input" value="${user.fullname}" placeholder="Full Name">
            <input id="swal-email" class="swal2-input" value="${user.email}" placeholder="Email" type="email">
            <select id="swal-role" class="swal2-input">
              ${roleOptions}
            </select>
          `,
    showCancelButton: true,
    confirmButtonText: "Update",
    confirmButtonColor: "#3b82f6",
    preConfirm: () => {
      return {
        fullname: document.getElementById("swal-fullname").value,
        email: document.getElementById("swal-email").value,
        role_id: parseInt(document.getElementById("swal-role").value),
      };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`${apiBase}?api=users`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "update", user_id: userId, fullname: result.value.fullname, email: result.value.email, role_id: result.value.role_id }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchUsers();
            Swal.fire({
              icon: "success",
              title: "Updated!",
              text: "User has been updated",
              timer: 2000,
              showConfirmButton: false,
            });
          } else {
            Swal.fire({ icon: "error", title: "Update Failed", text: resp && resp.message ? resp.message : "Error" });
          }
        });
    }
  });
}

// Toggle User Status
function toggleUserStatus(userId) {
  const user = users.find((u) => u.user_id === userId);
  if (!user) return;

  const newStatus = user.is_active === 1 ? 0 : 1;
  const statusText = newStatus === 1 ? "activate" : "disable";

  Swal.fire({
    title: `${statusText.charAt(0).toUpperCase() + statusText.slice(1)} User?`,
    text: `Do you want to ${statusText} ${user.fullname}?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3b82f6",
    confirmButtonText: `Yes, ${statusText}!`,
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`${apiBase}?api=users`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", user_id: userId }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchUsers();
            Swal.fire({
              icon: "success",
              title: "Updated!",
              text: `User has been ${statusText}d`,
              timer: 2000,
              showConfirmButton: false,
            });
          }
        });
    }
  });
}

// Delete User
function deleteUser(userId) {
  Swal.fire({
    title: "Delete User?",
    text: "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    confirmButtonText: "Yes, delete it!",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`${apiBase}?api=users`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", user_id: userId }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchUsers();
            Swal.fire({
              icon: "success",
              title: "Deleted!",
              text: "User has been deleted",
              timer: 2000,
              showConfirmButton: false,
            });
          }
        });
    }
  });
}

// Search Table
function searchTable(tableId, searchText) {
  const table = document.getElementById(tableId);
  const rows = table.getElementsByTagName("tr");

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const text = row.textContent.toLowerCase();
    if (text.includes(searchText.toLowerCase())) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  }
}

(async function init() {
  await fetchRoles();
  await fetchUsers();
})();
