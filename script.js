function switchToRegister() {
    document.getElementById('mainContainer').classList.add('register-active');
}

function switchToLogin() {
    document.getElementById('mainContainer').classList.remove('register-active');
}

function togglePasswordVisibility(inputId, iconElement) {
    const passwordInput = document.getElementById(inputId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}

function toggleTheme() {
    const root = document.documentElement;
    const themeBtn = document.getElementById('themeToggleBtn');
    const icon = themeBtn ? themeBtn.querySelector('i') : null;

    if (root.hasAttribute('data-theme')) {
        root.removeAttribute('data-theme');
        localStorage.setItem('selectedTheme', 'light');
        if (icon) { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); }
    } else {
        root.setAttribute('data-theme', 'dark');
        localStorage.setItem('selectedTheme', 'dark');
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('selectedTheme');
    const themeBtn = document.getElementById('themeToggleBtn');
    const icon = themeBtn ? themeBtn.querySelector('i') : null;
    
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
});

const signupForm = document.getElementById('signupForm');
if (signupForm) {
    signupForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const username = formData.get('username');
        const email = formData.get('email');
        const password = formData.get('password');
        const acceptTermsEl = document.getElementById('acceptTerms');
        const acceptTerms = acceptTermsEl ? acceptTermsEl.checked : false;

        if (!username || !email || !password) {
            alert('Please fill in all fields.');
            return;
        }
        if (!acceptTerms) {
            alert('You must accept the terms and conditions.');
            return;
        }
        if (password.length < 6) {
            alert('Password must be at least 6 characters long.');
            return;
        }

        fetch('signup_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Account created successfully! Redirecting...');
                window.location.href = 'dashboard.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        const username = this.querySelector('input[name="username"]').value.trim();
        const password = this.querySelector('input[name="password"]').value;

        if (!username || !password) {
            e.preventDefault();
            alert('Please fill in all fields.');
            return;
        }
    });
}

document.querySelectorAll('.social-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        alert('Social login in development');
    });
});

function toggleOrdersPanel(e) {
    if (e) e.preventDefault();
    const ordersPanel = document.getElementById('ordersPanel');
    if (ordersPanel) {
        ordersPanel.style.display = ordersPanel.style.display === 'none' ? 'block' : 'none';
    }
}

function viewOrderDetails(orderId) {
    let modal = document.getElementById('orderDetailModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'orderDetailModal';
        modal.className = 'order-modal';
        modal.onclick = function(e) {
            if (e.target === modal) closeOrderModal();
        };
        document.body.appendChild(modal);
        document.body.appendChild(modal);
    }
    
    fetch('get_order_details.php?order_id=' + orderId)
        .then(data => {
            if (data.success) {
                const product = data.product;
                const order = data.order;
                
                modal.innerHTML = `
                    <div class="order-modal-content">
                        <div class="order-modal-header">
                            <h2><i class="fas fa-box"></i> Order Details</h2>
                            <button class="close-modal" onclick="closeOrderModal()">&times;</button>
                        </div>
                        <div class="order-detail-item">
                            <img src="${product.image_url || 'https://via.placeholder.com/120x120?text=No+Image'}" alt="${product.title}" class="order-detail-img">
                            <div class="order-detail-info">
                                <h3>${product.title}</h3>
                                <p><strong>Order ID:</strong> #${order.id}</p>
                                <p><strong>Quantity:</strong> ${order.quantity}</p>
                                <p><strong>Unit Price:</strong> $${parseFloat(product.price).toFixed(2)}</p>
                                <p class="order-detail-price"><strong>Total:</strong> $${parseFloat(order.total_price).toFixed(2)}</p>
                                <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'})}</p>
                            </div>
                        </div>
                    </div>
                `;
                modal.style.display = 'block';
            } else {
                alert('Error loading order details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading order details');
        });
}

function closeOrderModal() {
    const modal = document.getElementById('orderDetailModal');
    if (modal) {
        modal.style.display = 'none';
    }
}