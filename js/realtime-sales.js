// Real-time Sales Monitoring
let salesInterval;
let lastSaleCount = 0;

// Initialize real-time updates
function initRealTimeSales() {
    // Check if we're on the sales page
    if (window.location.pathname.includes('sales.php')) {
        startRealTimeUpdates();
    }
}

// Start real-time updates
function startRealTimeUpdates() {
    // Update immediately
    updateSalesData();
    
    // Then update every 5 seconds
    salesInterval = setInterval(updateSalesData, 5000);
}

// Stop real-time updates
function stopRealTimeUpdates() {
    if (salesInterval) {
        clearInterval(salesInterval);
        salesInterval = null;
    }
}

// Update sales data
async function updateSalesData() {
    try {
        const response = await fetch('api/get-realtime-sales.php');
        const data = await response.json();
        
        if (data.success) {
            updateSalesTable(data.sales);
            updateStats(data.stats);
            showNewSaleNotification(data.new_sales);
        }
    } catch (error) {
        console.error('Error updating sales data:', error);
    }
}

// Update sales table
function updateSalesTable(sales) {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    // Add new sales rows
    sales.forEach(sale => {
        const row = createSaleRow(sale);
        tbody.appendChild(row);
    });
    
    // Update pagination info
    updatePaginationInfo(data.pagination);
}

// Create a sale row
function createSaleRow(sale) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${sale.invoice_number}</td>
        <td>${sale.customer_name || 'Walk-in Customer'}</td>
        <td>${sale.full_name}</td>
        <td>${sale.items_count} item(s)</td>
        <td>₱${parseFloat(sale.subtotal).toFixed(2)}</td>
        <td>₱${parseFloat(sale.tax).toFixed(2)}</td>
        <td>₱${parseFloat(sale.discount).toFixed(2)}</td>
        <td><strong>₱${parseFloat(sale.total_amount).toFixed(2)}</strong></td>
        <td><span class="badge badge-primary">${sale.payment_method}</span></td>
        <td>${formatDate(sale.sale_date)}</td>
        <td>
            <button class="btn btn-primary btn-sm" onclick="viewSaleDetails(${sale.sale_id})">View</button>
            <button class="btn btn-success btn-sm" onclick="viewReceiptModal('${sale.invoice_number}')">Receipt</button>
        </td>
    `;
    
    // Add animation for new sales
    row.style.animation = 'slideIn 0.3s ease-out';
    
    return row;
}

// Update statistics
function updateStats(stats) {
    // Update stats display if it exists
    const statsContainer = document.querySelector('.sales-stats');
    if (statsContainer) {
        statsContainer.innerHTML = `
            <div class="stat-item">
                <h4>Today's Sales</h4>
                <p>₱${parseFloat(stats.today_total).toFixed(2)}</p>
            </div>
            <div class="item">
                <h4>Total Sales</h4>
                <p>₱${parseFloat(stats.total_sales).toFixed(2)}</p>
            </div>
            <div class="item">
                <h4>Average Sale</h4>
                <p>₱${parseFloat(stats.average_sale).toFixed(2)}</p>
            </div>
        `;
    }
}

// Update pagination info
function updatePaginationInfo(pagination) {
    const counter = document.querySelector('.pagination-counter');
    if (counter) {
        counter.textContent = `Showing ${pagination.current_start}-${pagination.current_end} of ${pagination.total} sales`;
    }
    
    // Update pagination controls
    updatePaginationControls(pagination);
}

// Update pagination controls
function updatePaginationControls(pagination) {
    const paginationDiv = document.querySelector('.pagination');
    if (!paginationDiv) return;
    
    let paginationHTML = '';
    
    // Previous button
    if (pagination.current_page > 1) {
        paginationHTML += `<a href="?page=${pagination.current_page - 1}" class="btn btn-secondary btn-sm">« Previous</a>`;
    }
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            paginationHTML += `<span class="btn btn-primary btn-sm">${i}</span>`;
        } else {
            paginationHTML += `<a href="?page=${i}" class="btn btn-secondary btn-sm">${i}</a>`;
        }
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        paginationHTML += `<a href="?page=${pagination.current_page + 1}" class="btn btn-secondary btn-sm">Next »</a>`;
    }
    
    paginationDiv.innerHTML = paginationHTML;
}

// Show notification for new sales
function showNewSaleNotification(newSales) {
    if (newSales.length > 0 && newSales.length > lastSaleCount) {
        const latestSale = newSales[newSales.length - 1];
        showNotification(`New Sale: ${latestSale.invoice_number} - ₱${parseFloat(latestSale.total_amount).toFixed(2)}`);
        lastSaleCount = newSales.length;
    }
}

// Show notification
function showNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'realtime-notification';
    notification.textContent = message;
    notification.style.css = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--success);
        color: white;
        padding: 12px 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .realtime-notification {
        animation: slideIn 0.3s ease-out;
    }
    
    .sales-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .sales-stats .stat-item,
    .sales-stats .item {
        background: var(--white);
        padding: 16px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
    }
    
    .sales-stats h4 {
        margin: 0 0 8px 0;
        color: var(--text-secondary);
        font-size: 14px;
    }
    
    .sales-stats p {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--primary);
    }
`;
document.head.appendChild(style);

// Initialize when page loads
document.addEventListener('DOMContentLoaded', initRealTimeSales);

// Stop when page unloads
window.addEventListener('beforeunload', stopRealTimeUpdates);
