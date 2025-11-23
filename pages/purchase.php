<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Purchase Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/purchase.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <div class="container">
        <!-- Alert -->
        <div id="alert" class="alert"></div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-plus-circle"></i>
                Create Purchase Request
            </h1>
            <p class="page-subtitle">Fill in the details below to create a new purchase request</p>
        </div>

        <!-- Form Grid -->
        <div class="form-grid">
            <!-- Left: Form Details -->
            <div>
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h3>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-truck"></i>
                            Supplier <span class="required">*</span>
                        </label>
                        <select class="form-select" id="supplierSelect" required>
                            <option value="">-- Select Supplier --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar"></i>
                            Request Date <span class="required">*</span>
                        </label>
                        <input type="date" class="form-input" id="requestDate" required>
                    </div>
                </div>

                <!-- Items Selection -->
                <div class="form-section" style="margin-top: 20px;">
                    <h3 class="section-title">
                        <i class="fas fa-boxes"></i>
                        Select Items
                    </h3>

                    <div class="items-search">
                        <input type="text" class="form-input" id="itemSearch" placeholder="Search items...">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                    </div>

                    <div class="items-list" id="itemsList">
                        <!-- Items will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Right: Selected Items -->
            <div class="selected-items">
                <div class="selected-header">
                    <h3 style="font-size: 18px; color: var(--blue-dark);">
                        <i class="fas fa-shopping-cart"></i> Selected Items
                    </h3>
                    <span class="selected-count" id="selectedCount">0</span>
                </div>

                <div class="selected-items-list" id="selectedItemsList">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No items selected</p>
                    </div>
                </div>

                <button class="btn btn-primary" id="submitBtn" disabled>
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
                <button class="btn btn-secondary" id="clearBtn">
                    <i class="fas fa-redo"></i> Clear All
                </button>
            </div>
        </div>
    </div>

   <script src="../js/purchase.js"></script>
</body>
</html>
