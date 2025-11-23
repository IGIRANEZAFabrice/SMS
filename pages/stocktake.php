<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Take</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/stcktake.css">
</head>
<body>
    <div class="container">
        <!-- Alert -->
        <div id="alert" class="alert"></div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clipboard-check"></i>
                Stock Take
            </h1>
            <p class="page-subtitle">Search for items and update physical count</p>
        </div>

        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Left: Search & Items -->
            <div>
                <div class="search-section">
                    <h3 class="section-title">
                        <i class="fas fa-search"></i>
                        Search Items
                    </h3>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search items by name...">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                    </div>

                    <div class="items-list" id="itemsList">
                        <!-- Items will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Right: Counted Items -->
            <div class="counted-section">
                <div class="counted-header">
                    <h3 style="font-size: 18px; color: var(--blue-dark);">
                        <i class="fas fa-check-circle"></i> Counted Items
                    </h3>
                    <span class="counted-count" id="countedCount">0</span>
                </div>

                <div class="counted-list" id="countedList">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No items counted yet</p>
                    </div>
                </div>

                <button class="btn btn-success" id="saveBtn" disabled>
                    <i class="fas fa-save"></i> Save Stock Take
                </button>
                <button class="btn btn-secondary" id="clearBtn" style="margin-top: 10px;">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            </div>
        </div>
    </div>

    <!-- Count Modal -->
    <div id="countModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calculator"></i> Stock Count</h3>
                <button class="close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-item-name" id="modalItemName">Item Name</div>
                
                <div class="stock-info">
                    <div class="stock-info-card system">
                        <div class="stock-info-label">System Stock</div>
                        <div class="stock-info-value" id="modalSystemStock">0</div>
                    </div>
                    <div class="stock-info-card counted">
                        <div class="stock-info-label">Physical Count</div>
                        <div class="stock-info-value" id="modalPhysicalCount">0</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-keyboard"></i> Enter Physical Count
                    </label>
                    <input type="number" 
                           class="form-input" 
                           id="physicalCountInput" 
                           placeholder="0" 
                           min="0" 
                           step="0.01"
                           autofocus>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="saveCountBtn">
                    <i class="fas fa-check"></i> Save Count
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

   <script src="../js/stocktake.js"></script>
</body>
</html>