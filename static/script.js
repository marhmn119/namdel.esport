// Smooth Scrolling untuk menu navigasi
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Peringatan jika belum pilih role
const joinForm = document.querySelector('.join-form');
if (joinForm) {
    joinForm.addEventListener('submit', function(e) {
        const role = document.getElementById('role').value;
        if (role === "") {
            e.preventDefault();
            alert("Harap pilih role MLBB kamu terlebih dahulu!");
        }
    });
}