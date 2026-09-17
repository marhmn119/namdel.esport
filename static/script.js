window.addEventListener('load', function() {
    // 1. LOADING SCREEN
    const loader = document.getElementById('loader');
    if (loader) {
        setTimeout(function() {
            loader.style.opacity = '0';
            setTimeout(function() {
                loader.style.display = 'none';
            }, 500); 
        }, 1000); 
    }

    // 2. MEGA MENU DROPDOWN TOGGLE
    const menuBtn = document.getElementById('menuBtn');
    const megaMenu = document.getElementById('megaMenu');
    const menuLinks = document.querySelectorAll('.mega-col a');

    if (menuBtn && megaMenu) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            megaMenu.classList.toggle('active');
            
            if (megaMenu.classList.contains('active')) {
                menuBtn.innerHTML = 'Tutup <span class="hamburger">✕</span>';
            } else {
                menuBtn.innerHTML = 'Menu <span class="hamburger">☰</span>';
            }
        });

        // Tutup menu kalau link di dalam menu diklik
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                megaMenu.classList.remove('active');
                menuBtn.innerHTML = 'Menu <span>☰</span>';
            });
        });

        // Tutup menu kalau klik di luar area menu
        document.addEventListener('click', function(e) {
            if (!megaMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                megaMenu.classList.remove('active');
                menuBtn.innerHTML = 'Menu <span>☰</span>';
            }
        });
    }

    // 3. INTERACTIVE SLIDE INDICATORS (Penanda Garis Slide)
    const indicators = document.querySelectorAll('.indicator-line');
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            indicators.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            
            if(index === 1) {
                document.getElementById('jadwal').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // 4. SMOOTH SCROLLING
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});