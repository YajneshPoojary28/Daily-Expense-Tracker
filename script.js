// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Add expense form validation
    const expenseForm = document.querySelector('form[action="add_expense.php"]');
    if (expenseForm) {
        expenseForm.addEventListener('submit', function(e) {
            const amount = document.getElementById('amount');
            const title = document.getElementById('title');
            
            if (amount.value <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount greater than 0');
            }
            
            if (title.value.trim().length < 3) {
                e.preventDefault();
                alert('Title must be at least 3 characters long');
            }
        });
    }
    
    // Password match validation for registration
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            
            if (password.value !== confirm.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
            
            if (password.value.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Format currency input
    const amountInputs = document.querySelectorAll('input[type="number"]');
    amountInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
    
    // Toggle sidebar on mobile
    const toggleSidebar = () => {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    };
    
    // Add mobile menu button
    const header = document.querySelector('h1');
    if (header && window.innerWidth <= 768) {
        const menuBtn = document.createElement('button');
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        menuBtn.className = 'mobile-menu-btn';
        menuBtn.style.cssText = `
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 15px;
            font-size: 1.2rem;
        `;
        
        menuBtn.addEventListener('click', toggleSidebar);
        header.parentNode.insertBefore(menuBtn, header);
    }
});

// Chart functionality for dashboard (optional)
function initializeCharts() {
    const ctx = document.getElementById('expenseChart');
    if (ctx) {
        // You can add chart.js or other charting library here
        // This is just a placeholder
        console.log('Chart would be initialized here');
    }
}

// Export to CSV functionality
function exportToCSV(data, filename) {
    const csv = data.map(row => 
        row.map(cell => 
            typeof cell === 'string' ? `"${cell}"` : cell
        ).join(',')
    ).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print functionality
function printExpenses() {
    window.print();
}

// Date picker enhancements
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    });
});