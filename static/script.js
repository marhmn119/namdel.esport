window.addEventListener('load', function() {
    // 1. Logika Loading Screen
    const loader = document.getElementById('loader');
    setTimeout(function() {
        loader.style.opacity = '0';
        setTimeout(function() {
            loader.style.display = 'none';
        }, 500);
    }, 1000); 

    // 2. Logika Mega Menu
    const menuBtn = document.getElementById('menuBtn');
    const megaMenu = document.getElementById('megaMenu');
    const menuLinks = document.querySelectorAll('.mega-content a');

    // Buka/Tutup menu saat tombol diklik
    menuBtn.addEventListener('click', function() {
        megaMenu.classList.toggle('active');
        // Ubah ikon hamburger jadi X kalau sedang aktif
        if (megaMenu.classList.contains('active')) {
            menuBtn.innerHTML = 'Tutup <span>✕</span>';
        } else {
            menuBtn.innerHTML = 'Menu <span>☰</span>';
        }
    });

    // Otomatis tutup menu kalau salah satu link diklik
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            megaMenu.classList.remove('active');
            menuBtn.innerHTML = 'Menu <span>☰</span>';
        });
    });
});