document.addEventListener('DOMContentLoaded', function() {
    // ========== CART FUNCTIONALITY ==========
    const cartBtn = document.getElementById('cartBtn');
    if (cartBtn) {
        cartBtn.addEventListener('click', function() {
            // Replace with actual cart opening logic
            console.log('Cart clicked');
            // window.location.href = 'cart.php';
        });
    }

    // ========== COUNTDOWN TIMERS ==========
    function initializeCountdowns() {
        const countdownElements = document.querySelectorAll('[id^="countdown"]');
        
        if (countdownElements.length > 0) {
            // Set the date we're counting down to (24 hours from now)
            const countDownDate = new Date();
            countDownDate.setHours(countDownDate.getHours() + 24);

            // Update all countdown elements
            const countdownInterval = setInterval(function() {
                const now = new Date().getTime();
                const distance = countDownDate - now;

                // Time calculations
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Update each countdown element
                countdownElements.forEach(element => {
                    element.innerHTML = "Ends in: " + hours + "h " + minutes + "m " + seconds + "s";
                });

                // If the countdown is finished
                if (distance < 0) {
                    clearInterval(countdownInterval);
                    countdownElements.forEach(element => {
                        element.innerHTML = "EXPIRED";
                    });
                }
            }, 1000);
        }
    }
    initializeCountdowns();

    // ========== PRODUCT QUANTITY SELECTORS ==========
    document.querySelectorAll('.quantity-selector').forEach(selector => {
        const minusBtn = selector.querySelector('.quantity-minus');
        const plusBtn = selector.querySelector('.quantity-plus');
        const input = selector.querySelector('.quantity-input');

        minusBtn.addEventListener('click', () => {
            let value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        });

        plusBtn.addEventListener('click', () => {
            let value = parseInt(input.value);
            input.value = value + 1;
        });
    });

    // ========== NEWSLETTER FORM VALIDATION ==========
    const newsletterForm = document.querySelector('form[method="POST"]');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            const emailInput = this.querySelector('input[type="email"]');
            if (!emailInput.value || !emailInput.checkValidity()) {
                e.preventDefault();
                emailInput.classList.add('border-red-500');
                // Add error message
            }
        });
    }

    // ========== LAZY LOAD IMAGES ==========
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // ========== SMOOTH SCROLLING ==========
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});