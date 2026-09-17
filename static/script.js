// Menghilangkan Loading Screen secara perlahan setelah halaman dimuat
window.addEventListener('load', function() {
    const loader = document.getElementById('loader');
    
    // Memberikan jeda 1 detik agar efek loading logonya terasa
    setTimeout(function() {
        loader.style.opacity = '0'; // Bikin memudar
        
        setTimeout(function() {
            loader.style.display = 'none'; // Hilangkan sepenuhnya
        }, 500); // Tunggu sampai transisi memudar selesai
        
    }, 1000); 
});