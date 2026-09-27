<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Order & Inventory System</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --success-text: #166534;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #991b1b;
            --warning-bg: #fffbeb;
            --warning-border: #fef3c7;
            --warning-text: #92400e;
            --radius: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            padding: 24px 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 24px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .badge {
            background: var(--warning-bg);
            color: var(--warning-text);
            border: 1px solid var(--warning-border);
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 14px;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 4px;
            color: var(--text);
        }

        input[type="text"],
        input[type="email"],
        input[type="number"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus {
            border-color: var(--primary);
        }

        .flex-row {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn:hover {
            background: var(--primary-hover);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-danger-sm {
            background: #ef4444;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .btn-danger-sm:hover {
            background: #dc2626;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            margin-top: 10px;
        }

        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-muted);
        }

        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-top: 14px;
            display: none;
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        .alert ul {
            margin-left: 18px;
            margin-top: 4px;
        }

        .order-card {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px;
            margin-top: 12px;
        }

        .order-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        .empty-state {
            text-align: center;
            padding: 16px;
            color: var(--text-muted);
            font-size: 0.875rem;
            border: 1px dashed var(--border);
            border-radius: 6px;
            margin-top: 10px;
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Store Order & Inventory Dashboard</h1>
        <p class="subtitle">Laravel PostgreSQL Take-Home Assignment UI</p>
    </header>

    <div class="grid">
        <!-- Section 1: Create Order -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Create Order</h2>
            </div>

            <form id="createOrderForm" onsubmit="handleCreateOrder(event)">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" placeholder="Alice Smith" required>
                </div>

                <div class="form-group">
                    <label for="customer_email">Customer Email</label>
                    <input type="email" id="customer_email" placeholder="alice@example.com" required>
                </div>

                <div class="form-group" style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px;">
                    <label>Add Order Line Items</label>
                    <div class="flex-row">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: var(--text-muted);">Product ID</label>
                            <input type="number" id="product_id" min="1" placeholder="e.g. 1">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: var(--text-muted);">Quantity</label>
                            <input type="number" id="quantity" min="1" value="1">
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addDraftItem()">+ Add Item</button>
                    </div>
                </div>

                <div id="draftItemsContainer">
                    <div class="empty-state" id="draftEmptyState">No items added to order draft yet.</div>
                    <table id="draftTable" style="display: none;">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="draftTableBody"></tbody>
                    </table>
                </div>

                <button type="submit" class="btn" id="submitOrderBtn" style="width: 100%; margin-top: 16px;">
                    <span id="submitOrderSpinner" class="spinner" style="display: none;"></span>
                    <span id="submitOrderText">Create Order</span>
                </button>
            </form>

            <div id="orderSuccessAlert" class="alert alert-success"></div>
            <div id="orderErrorAlert" class="alert alert-error"></div>
        </div>

        <!-- Section 2: Customer Order History -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Customer Order History</h2>
            </div>

            <form onsubmit="handleSearchOrders(event)">
                <div class="form-group">
                    <label for="search_email">Customer Email</label>
                    <div class="flex-row">
                        <input type="email" id="search_email" placeholder="alice@example.com" required style="flex: 1;">
                        <button type="submit" class="btn" id="searchOrdersBtn">
                            <span id="searchSpinner" class="spinner" style="display: none;"></span>
                            <span id="searchText">Search</span>
                        </button>
                    </div>
                </div>
            </form>

            <div id="historyErrorAlert" class="alert alert-error"></div>

            <div id="customerSummary" style="display: none; margin-top: 12px; font-size: 0.875rem; padding: 8px 12px; background: #f1f5f9; border-radius: 6px;">
                <strong>Customer:</strong> <span id="summaryName"></span> (<span id="summaryEmail"></span>)
            </div>

            <div id="historyContainer" style="margin-top: 12px;"></div>
        </div>

        <!-- Section 3: Low Stock Products -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Low Stock Products</h2>
                <span class="badge" id="thresholdBadge">Threshold: 5</span>
            </div>

            <button class="btn btn-secondary" onclick="loadLowStockProducts()" id="refreshStockBtn" style="margin-bottom: 12px;">
                <span id="refreshSpinner" class="spinner" style="display: none;"></span>
                <span>Refresh Inventory</span>
            </button>

            <div id="stockErrorAlert" class="alert alert-error"></div>

            <div id="stockTableContainer">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Loading low stock inventory...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // State for Draft Items in Order Form
    let draftItems = [];

    // Security Helper: Escape HTML characters to prevent XSS vulnerability
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper for API Calls
    async function apiFetch(url, options = {}) {
        options.headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers
        };
        const res = await fetch(url, options);
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    }

    // 1. Create Order Logic
    function addDraftItem() {
        const productIdInput = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');

        const productId = parseInt(productIdInput.value, 10);
        const quantity = parseInt(quantityInput.value, 10);

        if (!productId || productId < 1 || !quantity || quantity < 1) {
            alert('Please enter a valid Product ID and Quantity (>= 1).');
            return;
        }

        const existingIndex = draftItems.findIndex(i => i.product_id === productId);
        if (existingIndex > -1) {
            draftItems[existingIndex].quantity += quantity;
        } else {
            draftItems.push({ product_id: productId, quantity: quantity });
        }

        productIdInput.value = '';
        quantityInput.value = '1';

        renderDraftItems();
    }

    function removeDraftItem(index) {
        draftItems.splice(index, 1);
        renderDraftItems();
    }

    function renderDraftItems() {
        const emptyState = document.getElementById('draftEmptyState');
        const table = document.getElementById('draftTable');
        const tbody = document.getElementById('draftTableBody');

        if (draftItems.length === 0) {
            emptyState.style.display = 'block';
            table.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }

        emptyState.style.display = 'none';
        table.style.display = 'table';
        tbody.innerHTML = draftItems.map((item, idx) => `
            <tr>
                <td>#${escapeHtml(item.product_id)}</td>
                <td>${escapeHtml(item.quantity)}</td>
                <td><button type="button" class="btn-danger-sm" onclick="removeDraftItem(${idx})">Remove</button></td>
            </tr>
        `).join('');
    }

    async function handleCreateOrder(e) {
        e.preventDefault();
        const successAlert = document.getElementById('orderSuccessAlert');
        const errorAlert = document.getElementById('orderErrorAlert');
        const spinner = document.getElementById('submitOrderSpinner');
        const btn = document.getElementById('submitOrderBtn');

        successAlert.style.display = 'none';
        errorAlert.style.display = 'none';

        if (draftItems.length === 0) {
            errorAlert.innerHTML = '<strong>Error:</strong> Please add at least one product item to the order draft.';
            errorAlert.style.display = 'block';
            return;
        }

        const payload = {
            customer_name: document.getElementById('customer_name').value.trim(),
            customer_email: document.getElementById('customer_email').value.trim(),
            items: draftItems
        };

        btn.disabled = true;
        spinner.style.display = 'inline-block';

        const { ok, data } = await apiFetch('/api/orders', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        btn.disabled = false;
        spinner.style.display = 'none';

        if (ok) {
            const order = data.data;
            successAlert.innerHTML = `
                <strong>Order #${escapeHtml(order.id)} Created Successfully!</strong><br>
                Subtotal: $${escapeHtml(order.subtotal)} | Tax: $${escapeHtml(order.tax)} | <strong>Grand Total: $${escapeHtml(order.grand_total)}</strong>
            `;
            successAlert.style.display = 'block';

            // Reset Form & Draft
            draftItems = [];
            renderDraftItems();
            document.getElementById('createOrderForm').reset();
            loadLowStockProducts(); // Refresh stock table
        } else {
            let errorHtml = `<strong>Failed to Create Order:</strong> ${escapeHtml(data.message || 'Unknown error')}`;
            if (data.errors) {
                errorHtml += '<ul>';
                for (const key in data.errors) {
                    const messages = data.errors[key].map(msg => `<li>${escapeHtml(msg)}</li>`).join('');
                    errorHtml += messages;
                }
                errorHtml += '</ul>';
            }
            errorAlert.innerHTML = errorHtml;
            errorAlert.style.display = 'block';
        }
    }

    // 2. Customer Order History Logic
    async function handleSearchOrders(e) {
        e.preventDefault();
        const email = document.getElementById('search_email').value.trim();
        const errorAlert = document.getElementById('historyErrorAlert');
        const summary = document.getElementById('customerSummary');
        const container = document.getElementById('historyContainer');
        const spinner = document.getElementById('searchSpinner');
        const btn = document.getElementById('searchOrdersBtn');

        errorAlert.style.display = 'none';
        summary.style.display = 'none';
        container.innerHTML = '';

        btn.disabled = true;
        spinner.style.display = 'inline-block';

        const { ok, data } = await apiFetch(`/api/customers/orders?email=${encodeURIComponent(email)}`);

        btn.disabled = false;
        spinner.style.display = 'none';

        if (ok) {
            const customer = data.data.customer;
            const orders = data.data.orders;

            document.getElementById('summaryName').innerText = customer.name;
            document.getElementById('summaryEmail').innerText = customer.email;
            summary.style.display = 'block';

            if (orders.length === 0) {
                container.innerHTML = '<div class="empty-state">No past orders found for this customer.</div>';
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="order-meta">
                        <span><strong>Order #${escapeHtml(order.id)}</strong> — ${escapeHtml(new Date(order.created_at).toLocaleString())}</span>
                        <span>Grand Total: <strong>$${escapeHtml(order.grand_total)}</strong></span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 6px;">
                        Subtotal: $${escapeHtml(order.subtotal)} | Tax: $${escapeHtml(order.tax)}
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Item Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${order.items.map(item => `
                                <tr>
                                    <td>${escapeHtml(item.product ? item.product.name : 'Product #' + item.product_id)}</td>
                                    <td>${escapeHtml(item.quantity)}</td>
                                    <td>$${escapeHtml(item.unit_price)}</td>
                                    <td>$${escapeHtml(item.total)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `).join('');
        } else {
            errorAlert.innerText = data.message || 'Error fetching order history.';
            errorAlert.style.display = 'block';
        }
    }

    // 3. Low Stock Products Logic
    async function loadLowStockProducts() {
        const tbody = document.getElementById('stockTableBody');
        const badge = document.getElementById('thresholdBadge');
        const errorAlert = document.getElementById('stockErrorAlert');
        const spinner = document.getElementById('refreshSpinner');
        const btn = document.getElementById('refreshStockBtn');

        errorAlert.style.display = 'none';
        btn.disabled = true;
        spinner.style.display = 'inline-block';

        const { ok, data } = await apiFetch('/api/products/low-stock');

        btn.disabled = false;
        spinner.style.display = 'none';

        if (ok) {
            badge.innerText = `Threshold: ${escapeHtml(data.meta.threshold)}`;

            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No low-stock products found.</td></tr>';
                return;
            }

            tbody.innerHTML = data.data.map(p => `
                <tr>
                    <td>#${escapeHtml(p.id)}</td>
                    <td><code>${escapeHtml(p.code)}</code></td>
                    <td>${escapeHtml(p.name)}</td>
                    <td>$${escapeHtml(p.price)}</td>
                    <td><strong style="color: var(--error-text);">${escapeHtml(p.stock_on_hand)}</strong></td>
                </tr>
            `).join('');
        } else {
            errorAlert.innerText = data.message || 'Error fetching low stock inventory.';
            errorAlert.style.display = 'block';
        }
    }

    // Initial Load
    document.addEventListener('DOMContentLoaded', () => {
        loadLowStockProducts();
    });
</script>

</body>
</html>
