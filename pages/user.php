<?php
require_once __DIR__ . '/../config/db.php';
$is_api = isset($_GET['api']) ? (string)$_GET['api'] : '';
if ($is_api !== '') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json');
    if ($is_api === 'roles') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $res = $conn->query('SELECT role_id, role_name FROM roles ORDER BY role_id ASC');
            $out = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = ['role_id' => (int)$row['role_id'], 'role_name' => (string)$row['role_name']];
                }
            }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $role_name = isset($input['role_name']) ? trim((string)$input['role_name']) : '';
            if ($role_name === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'role_name required']);
                exit;
            }
            $stmt = $conn->prepare('INSERT INTO roles (role_name) VALUES (?)');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('s', $role_name);
            $ok = $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'role_id' => (int)$id]);
            exit;
        }
        if ($action === 'update') {
            $role_id = isset($input['role_id']) ? (int)$input['role_id'] : 0;
            $role_name = isset($input['role_name']) ? trim((string)$input['role_name']) : '';
            if (!$role_id || $role_name === '') {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('UPDATE roles SET role_name = ? WHERE role_id = ?');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('si', $role_name, $role_id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        if ($action === 'delete') {
            $role_id = isset($input['role_id']) ? (int)$input['role_id'] : 0;
            if (!$role_id) {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM users WHERE role_id = ?');
            $stmt->bind_param('i', $role_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && (int)$row['c'] > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Role in use']);
                exit;
            }
            $stmt = $conn->prepare('DELETE FROM roles WHERE role_id = ?');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('i', $role_id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false]);
        exit;
    } elseif ($is_api === 'users') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $res = $conn->query('SELECT u.user_id,u.fullname,u.email,u.role_id,u.is_active,u.created_at,r.role_name FROM users u LEFT JOIN roles r ON r.role_id=u.role_id ORDER BY u.user_id ASC');
            $out = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = [
                        'user_id' => (int)$row['user_id'],
                        'fullname' => (string)$row['fullname'],
                        'email' => (string)$row['email'],
                        'role_id' => (int)$row['role_id'],
                        'role_name' => (string)($row['role_name'] ?? ''),
                        'is_active' => (int)$row['is_active'],
                        'created_at' => (string)$row['created_at']
                    ];
                }
            }
            echo json_encode(['success' => true, 'data' => $out]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $action = isset($input['action']) ? (string)$input['action'] : '';
        if ($action === 'add') {
            $fullname = isset($input['fullname']) ? trim((string)$input['fullname']) : '';
            $email = isset($input['email']) ? trim((string)$input['email']) : '';
            $password = isset($input['password']) ? (string)$input['password'] : '';
            $role_id = isset($input['role_id']) ? (int)$input['role_id'] : 0;
            if ($fullname === '' || $email === '' || $password === '' || !$role_id) {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $exists = $res && $res->num_rows > 0;
            $stmt->close();
            if ($exists) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Email exists']);
                exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users(fullname,email,password,role_id,is_active) VALUES (?,?,?,?,1)');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('sssi', $fullname, $email, $hash, $role_id);
            $ok = $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'user_id' => (int)$id]);
            exit;
        }
        if ($action === 'update') {
            $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
            $fullname = isset($input['fullname']) ? trim((string)$input['fullname']) : '';
            $email = isset($input['email']) ? trim((string)$input['email']) : '';
            $role_id = isset($input['role_id']) ? (int)$input['role_id'] : 0;
            if (!$user_id || $fullname === '' || $email === '' || !$role_id) {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM users WHERE email = ? AND user_id <> ?');
            $stmt->bind_param('si', $email, $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && (int)$row['c'] > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Email exists']);
                exit;
            }
            $stmt = $conn->prepare('UPDATE users SET fullname = ?, email = ?, role_id = ? WHERE user_id = ?');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('ssii', $fullname, $email, $role_id, $user_id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        if ($action === 'toggle') {
            $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
            if (!$user_id) {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('SELECT is_active FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false]);
                exit;
            }
            $new = ((int)$row['is_active'] === 1) ? 0 : 1;
            $stmt = $conn->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
            $stmt->bind_param('ii', $new, $user_id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok, 'is_active' => (int)$new]);
            exit;
        }
        if ($action === 'delete') {
            $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
            if (!$user_id) {
                http_response_code(400);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false]);
                exit;
            }
            $stmt->bind_param('i', $user_id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false]);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false]);
        exit;
    }
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /SMS/index.php?page=login');
    exit;
}
if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Roles & Users Management</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/user.css" />
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/sidebar.php'; ?>
      <div class="main-content">
        <div class="header">
          <div class="header-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title" id="pageTitle">System Admin</h1>
          </div>
          <div class="profile-container">
            <div class="profile-icon">
              <i class="fas fa-user"></i>
            </div>
            <div class="profile-dropdown">
              <div class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
              </div>
              <div class="dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
              </div>
            </div>
          </div>
        </div>
        <div class="content">
    <div class="page-header">
      <h1><i class="fas fa-users-cog"></i> Admin Management</h1>
      <p>Manage system roles and users</p>
    </div>

    <div class="container">
      <!-- Tabs -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('roles')">
          <i class="fas fa-user-tag"></i> Roles
        </button>
        <button class="tab-btn" onclick="switchTab('users')">
          <i class="fas fa-users"></i> Users
        </button>
      </div>

      <!-- Roles Tab -->
      <div class="tab-content active" id="rolesTab">
        <!-- Add Role Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add New Role</h3>
          </div>
          <form id="addRoleForm">
            <div class="form-grid">
              <div class="form-group">
                <label>Role Name <span class="required">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  id="roleName"
                  placeholder="Enter role name"
                  required
                />
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-plus"></i> Add Role
            </button>
          </form>
        </div>

        <!-- Roles List -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Roles List</h3>
          </div>
          <div class="toolbar">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input
                type="text"
                placeholder="Search roles..."
                id="searchRoles"
                onkeyup="searchTable('rolesTable', this.value)"
              />
            </div>
          </div>
          <div class="table-container">
            <table id="rolesTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Role Name</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="rolesTableBody">
                <!-- Roles will be populated here -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Users Tab -->
      <div class="tab-content" id="usersTab">
        <!-- Add User Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add New User</h3>
          </div>
          <form id="addUserForm">
            <div class="form-grid">
              <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  id="userFullname"
                  placeholder="Enter full name"
                  required
                />
              </div>
              <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input
                  type="email"
                  class="form-control"
                  id="userEmail"
                  placeholder="Enter email address"
                  required
                />
              </div>
              <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input
                  type="password"
                  class="form-control"
                  id="userPassword"
                  placeholder="Enter password"
                  required
                />
              </div>
              <div class="form-group">
                <label>Role <span class="required">*</span></label>
                <select class="form-control" id="userRole" required>
                  <option value="">Select role</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-user-plus"></i> Add User
            </button>
          </form>
        </div>

        <!-- Users List -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Users List</h3>
          </div>
          <div class="toolbar">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input
                type="text"
                placeholder="Search users..."
                id="searchUsers"
                onkeyup="searchTable('usersTable', this.value)"
              />
            </div>
          </div>
          <div class="table-container">
            <table id="usersTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <!-- Users will be populated here -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
        </div>
      </div>
    </div>
   <script src="../js/sidebar.js"></script>
   <script src="../js/user.js?v=<?php echo filemtime(__DIR__.'/../js/user.js'); ?>"></script>
  </body>
</html>
