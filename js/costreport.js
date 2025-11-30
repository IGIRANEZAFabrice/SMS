let reportData = {
    sales: [],
    categories: []
};
let revenueVsCOGSChart, profitByCategoryChart;

document.addEventListener('DOMContentLoaded', () => {
    loadReportData();
});

function formatCurrency(amount) {
    if (typeof amount !== 'number') {
        amount = parseFloat(amount) || 0;
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

async function loadReportData() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const categoryId = document.getElementById('categoryFilter').value;

    let url = 'costreport.php?api=cogs-report';
    if (dateFrom) url += `&from=${dateFrom}`;
    if (dateTo) url += `&to=${dateTo}`;
    if (categoryId) url += `&category=${categoryId}`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        if (data.success) {
            reportData = data.data;
            renderAll();
        } else {
            console.error('Failed to load report data:', data.message);
        }
    } catch (error) {
        console.error('Error fetching report data:', error);
    }
}

function renderAll() {
    loadCategoryFilter();
    calculateAndRenderStatistics();
    loadCharts();
    loadReceiptTable();
    loadItemTable();
    loadCategoryTable();
}

function loadCategoryFilter() {
    const select = document.getElementById("categoryFilter");
    const currentVal = select.value;
    select.innerHTML = '<option value="">All Categories</option>';
    reportData.categories.forEach(cat => {
        select.innerHTML += `<option value="${cat.cat_id}">${cat.cat_name}</option>`;
    });
    select.value = currentVal;
}

function calculateAndRenderStatistics() {
    let totalRevenue = 0;
    let totalCOGS = 0;
    const receiptCodes = new Set();

    reportData.sales.forEach(sale => {
        totalRevenue += sale.qty * sale.sale_price;
        totalCOGS += sale.qty * sale.cost_price;
        receiptCodes.add(sale.receipt_code);
    });

    const grossProfit = totalRevenue - totalCOGS;
    const profitMargin = totalRevenue > 0 ? (grossProfit / totalRevenue) * 100 : 0;

    document.getElementById("totalRevenue").textContent = formatCurrency(totalRevenue);
    document.getElementById("totalCOGS").textContent = formatCurrency(totalCOGS);
    document.getElementById("grossProfit").textContent = formatCurrency(grossProfit);
    document.getElementById("profitMargin").textContent = `${profitMargin.toFixed(1)}%`;
    document.getElementById("totalReceipts").textContent = receiptCodes.size;
}

function loadCharts() {
    // Destroy existing charts if they exist
    if (revenueVsCOGSChart) revenueVsCOGSChart.destroy();
    if (profitByCategoryChart) profitByCategoryChart.destroy();
    
    // --- Revenue vs COGS Chart ---
    const monthlyData = reportData.sales.reduce((acc, sale) => {
        const month = new Date(sale.sale_date).toLocaleString('default', { month: 'short', year: '2-digit' });
        if (!acc[month]) {
            acc[month] = { revenue: 0, cogs: 0 };
        }
        acc[month].revenue += sale.qty * sale.sale_price;
        acc[month].cogs += sale.qty * sale.cost_price;
        return acc;
    }, {});

    const labels = Object.keys(monthlyData);
    const revenueData = labels.map(l => monthlyData[l].revenue);
    const cogsData = labels.map(l => monthlyData[l].cogs);
    
    const ctx1 = document.getElementById("revenueVsCOGSChart").getContext("2d");
    revenueVsCOGSChart = new Chart(ctx1, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                { label: "Revenue", data: revenueData, borderColor: "#10b981", backgroundColor: "rgba(16, 185, 129, 0.1)", tension: 0.4, fill: true },
                { label: "COGS", data: cogsData, borderColor: "#ef4444", backgroundColor: "rgba(239, 68, 68, 0.1)", tension: 0.4, fill: true }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // --- Profit by Category Chart ---
    const categoryStats = calculateCategoryStats();
    const ctx2 = document.getElementById("profitByCategoryChart").getContext("2d");
    profitByCategoryChart = new Chart(ctx2, {
        type: "bar",
        data: {
            labels: categoryStats.map(c => c.categoryName),
            datasets: [{
                label: "Gross Profit",
                data: categoryStats.map(c => c.profit),
                backgroundColor: ["#3b82f6", "#10b981", "#f59e0b", "#8b5cf6", "#ef4444"],
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
}

function calculateCategoryStats() {
    const stats = {};
    reportData.categories.forEach(cat => {
        stats[cat.cat_id] = { categoryName: cat.cat_name, revenue: 0, cogs: 0, profit: 0, qtySold: 0 };
    });

    reportData.sales.forEach(sale => {
        if(stats[sale.cat_id]) {
            const q = parseFloat(sale.qty) || 0;
            const sp = parseFloat(sale.sale_price) || 0;
            const cp = parseFloat(sale.cost_price) || 0;
            const revenue = q * sp;
            const cogs = q * cp;
            stats[sale.cat_id].revenue += revenue;
            stats[sale.cat_id].cogs += cogs;
            stats[sale.cat_id].profit += revenue - cogs;
            stats[sale.cat_id].qtySold += q;
        }
    });
    return Object.values(stats).filter(s => s.qtySold > 0);
}

function loadReceiptTable() {
    const tbody = document.getElementById("receiptTableBody");
    tbody.innerHTML = "";
    
    const receiptStats = reportData.sales.reduce((acc, sale) => {
        if (!acc[sale.receipt_code]) {
            acc[sale.receipt_code] = { date: sale.sale_date, items: 0, revenue: 0, cogs: 0, created_by: sale.created_by };
        }
        acc[sale.receipt_code].items++;
        acc[sale.receipt_code].revenue += sale.qty * sale.sale_price;
        acc[sale.receipt_code].cogs += sale.qty * sale.cost_price;
        return acc;
    }, {});

    let totalItems = 0;
    let totalRevenue = 0;
    let totalCOGS = 0;
    for (const code in receiptStats) {
        const r = receiptStats[code];
        const grossProfit = r.revenue - r.cogs;
        const margin = r.revenue > 0 ? (grossProfit / r.revenue) * 100 : 0;
        tbody.innerHTML += `
            <tr>
              <td><strong>${code}</strong></td>
              <td>${new Date(r.date).toLocaleDateString()}</td>
              <td>${r.items} item(s)</td>
              <td class="text-right"><strong>${formatCurrency(r.revenue)}</strong></td>
              <td class="text-right">${formatCurrency(r.cogs)}</td>
              <td class="text-right" style="color: ${grossProfit > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(grossProfit)}</strong></td>
              <td class="text-right">${margin.toFixed(1)}%</td>
              <td>${r.created_by}</td>
            </tr>`;
        totalItems += r.items;
        totalRevenue += r.revenue;
        totalCOGS += r.cogs;
    }

    const totalGross = totalRevenue - totalCOGS;
    const totalMargin = totalRevenue > 0 ? (totalGross / totalRevenue) * 100 : 0;
    tbody.innerHTML += `
        <tr class="totals-row">
          <td><strong>Total</strong></td>
          <td></td>
          <td><strong>${totalItems} item(s)</strong></td>
          <td class="text-right"><strong>${formatCurrency(totalRevenue)}</strong></td>
          <td class="text-right"><strong>${formatCurrency(totalCOGS)}</strong></td>
          <td class="text-right" style="color: ${totalGross > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(totalGross)}</strong></td>
          <td class="text-right"><strong>${totalMargin.toFixed(1)}%</strong></td>
          <td></td>
        </tr>`;
}

function loadItemTable() {
    const tbody = document.getElementById("itemTableBody");
    tbody.innerHTML = "";

    const itemStats = reportData.sales.reduce((acc, sale) => {
        if (!acc[sale.item_id]) {
            acc[sale.item_id] = { name: sale.item_name, category: sale.cat_name, qty: 0, totalRevenue: 0, totalCOGS: 0 };
        }
        const q = parseFloat(sale.qty) || 0;
        const sp = parseFloat(sale.sale_price) || 0;
        const cp = parseFloat(sale.cost_price) || 0;
        acc[sale.item_id].qty += q;
        acc[sale.item_id].totalRevenue += q * sp;
        acc[sale.item_id].totalCOGS += q * cp;
        return acc;
    }, {});

    let sumQty = 0;
    let sumRevenue = 0;
    let sumCOGS = 0;
    for(const id in itemStats) {
        const i = itemStats[id];
        const grossProfit = i.totalRevenue - i.totalCOGS;
        const avgSalePrice = i.totalRevenue / i.qty;
        const avgCostPrice = i.totalCOGS / i.qty;
        const unitProfitLoss = avgSalePrice - avgCostPrice; // Calculate Unit P/L
        const totalProfitLoss = grossProfit; // Total P/L is simply Gross Profit
        const margin = i.totalRevenue > 0 ? (grossProfit / i.totalRevenue) * 100 : 0;

        tbody.innerHTML += `
            <tr>
              <td><strong>${i.name}</strong></td>
              <td>${i.category}</td>
              <td class="text-right"><strong>${i.qty}</strong></td>
              <td class="text-right">${formatCurrency(avgSalePrice)}</td>
              <td class="text-right">${formatCurrency(avgCostPrice)}</td>
              <td class="text-right" style="color: ${unitProfitLoss > 0 ? 'var(--green)' : 'var(--red)'}">${formatCurrency(unitProfitLoss)}</td>
              <td class="text-right" style="color: ${totalProfitLoss > 0 ? 'var(--green)' : 'var(--red)'}">${formatCurrency(totalProfitLoss)}</td>
              <td class="text-right"><strong>${formatCurrency(i.totalRevenue)}</strong></td>
              <td class="text-right">${formatCurrency(i.totalCOGS)}</td>
              <td class="text-right" style="color: ${grossProfit > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(grossProfit)}</strong></td>
              <td>${margin.toFixed(1)}%</td>
            </tr>`;
        sumQty += i.qty;
        sumRevenue += i.totalRevenue;
        sumCOGS += i.totalCOGS;
    }

    const sumGross = sumRevenue - sumCOGS;
    const sumMargin = sumRevenue > 0 ? (sumGross / sumRevenue) * 100 : 0;
    tbody.innerHTML += `
        <tr class="totals-row">
          <td><strong>Total</strong></td>
          <td></td>
          <td class="text-right"><strong>${sumQty}</strong></td>
          <td class="text-right"></td>
          <td class="text-right"></td>
          <td class="text-right"></td>
          <td class="text-right" style="color: ${sumGross > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(sumGross)}</strong></td>
          <td class="text-right"><strong>${formatCurrency(sumRevenue)}</strong></td>
          <td class="text-right"><strong>${formatCurrency(sumCOGS)}</strong></td>
          <td class="text-right" style="color: ${sumGross > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(sumGross)}</strong></td>
          <td><strong>${sumMargin.toFixed(1)}%</strong></td>
        </tr>`;
}

function loadCategoryTable() {
    const tbody = document.getElementById("categoryTableBody");
    tbody.innerHTML = "";
    const categoryStats = calculateCategoryStats();

    let totalItemsDistinct = new Set(reportData.sales.map(s => s.item_id)).size;
    let totalQty = 0;
    let totalRevenue = 0;
    let totalCOGS = 0;
    categoryStats.forEach(stat => {
        const grossProfit = stat.revenue - stat.cogs;
        const margin = stat.revenue > 0 ? (grossProfit / stat.revenue) * 100 : 0;
        const itemsSold = new Set(reportData.sales.filter(s => s.cat_id === reportData.categories.find(c=>c.cat_name === stat.categoryName).cat_id).map(s => s.item_id)).size;
        
        tbody.innerHTML += `
            <tr>
              <td><strong>${stat.categoryName}</strong></td>
              <td class="text-right">${itemsSold}</td>
              <td class="text-right"><strong>${stat.qtySold}</strong></td>
              <td class="text-right"><strong>${formatCurrency(stat.revenue)}</strong></td>
              <td class="text-right">${formatCurrency(stat.cogs)}</td>
              <td class="text-right" style="color: ${grossProfit > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(grossProfit)}</strong></td>
              <td class="text-right">${margin.toFixed(1)}%</td>
              <td>...</td>
            </tr>`;
        totalQty += stat.qtySold;
        totalRevenue += stat.revenue;
        totalCOGS += stat.cogs;
    });

    const totalGross = totalRevenue - totalCOGS;
    const totalMargin = totalRevenue > 0 ? (totalGross / totalRevenue) * 100 : 0;
    tbody.innerHTML += `
        <tr class="totals-row">
          <td><strong>Total</strong></td>
          <td class="text-right"><strong>${totalItemsDistinct}</strong></td>
          <td class="text-right"><strong>${totalQty}</strong></td>
          <td class="text-right"><strong>${formatCurrency(totalRevenue)}</strong></td>
          <td class="text-right"><strong>${formatCurrency(totalCOGS)}</strong></td>
          <td class="text-right" style="color: ${totalGross > 0 ? 'var(--green)' : 'var(--red)'}"><strong>${formatCurrency(totalGross)}</strong></td>
          <td class="text-right"><strong>${totalMargin.toFixed(1)}%</strong></td>
          <td></td>
        </tr>`;
}

function switchTab(evt, tabName) {
    document.querySelectorAll(".tab-btn").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
    evt.currentTarget.classList.add("active");
    document.getElementById(tabName + "Tab").classList.add("active");
}

function applyFilters() {
    loadReportData();
}

function getActiveTable() {
    const active = document.querySelector('.tab-content.active');
    if (!active) return null;
    return active.querySelector('table');
}

function tableToArrays(tbl) {
    const headCells = Array.from(tbl.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = Array.from(tbl.querySelectorAll('tbody tr')).map(tr => Array.from(tr.children).map(td => td.textContent.trim()));
    return { headCells, rows };
}

function exportReportExcel() {
    const table = getActiveTable();
    if (!table) return;
    const { headCells, rows } = tableToArrays(table);
    let csv = headCells.join(',') + '\n';
    rows.forEach(r => { csv += r.map(v => '"' + v.replace(/"/g, '""') + '"').join(',') + '\n'; });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const activeId = document.querySelector('.tab-content.active')?.id || 'report';
    a.href = url;
    a.download = `COGS_${activeId}_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

function exportReportPDF() {
    const table = getActiveTable();
    if (!table || !window.jspdf) return;
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');
    const { headCells, rows } = tableToArrays(table);
    doc.setFontSize(14);
    doc.text('COGS Report', 40, 40);
    doc.autoTable({
        head: [headCells],
        body: rows,
        startY: 60,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [59, 130, 246], textColor: 255 },
    });
    const activeId = document.querySelector('.tab-content.active')?.id || 'report';
    doc.save(`COGS_${activeId}_${new Date().toISOString().slice(0,10)}.pdf`);
}
