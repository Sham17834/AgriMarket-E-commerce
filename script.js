document.addEventListener('DOMContentLoaded', function() {
    // Select all anchor links with hash (#)
    const links = document.querySelectorAll('a[href*="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Calculate the target position
                const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
                
                // Smooth scroll animation
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
});

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