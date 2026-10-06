// 1. MENGHILANGKAN LOADING SCREEN OTOMATIS
window.addEventListener('load', function() {
    const loader = document.getElementById('loader');
    if(loader) {
        setTimeout(() => {
            loader.style.display = 'none';
        }, 800); 
    }
});

// KODE YANG BERJALAN SETELAH WEB DIMUAT
document.addEventListener("DOMContentLoaded", function() {
    
    // 2. JAM LIVE WIB DI NAVBAR
    function updateClock() {
        const clock = document.getElementById('liveClock');
        if(clock) {
            const now = new Date();
            clock.innerText = now.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' }) + ' WIB';
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 3. LOGIKA MEGA MENU (SUPER AUTO CLOSE)
    const menuBtn = document.getElementById('menuBtn');
    const megaMenu = document.getElementById('megaMenu');
    
    if (menuBtn && megaMenu) {
        menuBtn.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            if (megaMenu.style.display === 'block') {
                megaMenu.style.display = 'none';
            } else {
                megaMenu.style.display = 'block';
            }
        });

        const menuLinks = megaMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                megaMenu.style.display = 'none';
            });
        });

        document.addEventListener('click', function(e) {
            if (megaMenu.style.display === 'block' && !megaMenu.contains(e.target) && e.target !== menuBtn) {
                megaMenu.style.display = 'none';
            }
        });
    }

    // 4. FITUR COUNTDOWN TIMER JADWAL TURNAMEN
    function updateCountdowns() {
        const matchCards = document.querySelectorAll('.match-card');
        
        matchCards.forEach(card => {
            const targetDateStr = card.getAttribute('data-targetdate');
            if (!targetDateStr) return;
            
            const targetTime = new Date(targetDateStr).getTime();
            const now = new Date().getTime();
            const distance = targetTime - now;
            
            const timerSpan = card.querySelector('.countdown-clock');
            
            if (distance < 0) {
                if (timerSpan) timerSpan.innerText = "PERTANDINGAN DIMULAI / SELESAI";
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                if (timerSpan) {
                    timerSpan.innerText = `${days} Hari ${hours} Jam ${minutes} Menit ${seconds} Dtk`;
                }
            }
        });
    }
    setInterval(updateCountdowns, 1000);
    updateCountdowns();
    
    // 5. FITUR SLIDER SALAM PEMBUKA (3 SLIDE)
    const slides = [
        {
            title: "WELCOME TO <span>NAMDEL ESPORT</span>",
            text: "Selamat datang di web resmi divisi E-Sports SMAN 68 Jakarta. Kami mencari talenta-talenta terbaik yang memiliki mental juara, disiplin tinggi, dan kemauan keras untuk membawa nama sekolah ke puncak arena kompetitif."
        },
        {
            title: "VISI & <span>MISI KAMI</span>",
            text: "Membangun komunitas gamer yang sehat, kompetitif, dan suportif. Kami percaya bahwa E-Sports bukan sekadar bermain game, melainkan tentang kerja sama tim, strategi, dan pembentukan karakter kepemimpinan."
        },
        {
            title: "MENGUKIR <span>PRESTASI</span>",
            text: "Dari turnamen antar kelas hingga kompetisi tingkat nasional, NAMDEL ESPORT terus membuktikan diri. Mari bergabung dan jadilah bagian dari sejarah baru yang akan kami ukir di masa depan!"
        }
    ];

    let currentSlide = 0;
    const titleEl = document.getElementById("salamTitle");
    const textEl = document.getElementById("salamText");
    const dots = document.querySelectorAll(".indicator-dot");
    const prevBtn = document.getElementById("prevSlide");
    const nextBtn = document.getElementById("nextSlide");

    function updateSlide() {
        if (!titleEl || !textEl) return;
        titleEl.innerHTML = slides[currentSlide].title;
        textEl.innerText = slides[currentSlide].text;
        dots.forEach((dot, index) => {
            if(index === currentSlide) {
                dot.style.backgroundColor = "#ff6600";
            } else {
                dot.style.backgroundColor = "#ccc";
            }
        });
    }

    if (prevBtn && nextBtn) {
        nextBtn.addEventListener("click", () => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlide();
        });
        prevBtn.addEventListener("click", () => {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateSlide();
        });
        updateSlide();
    }
});


// ==========================================
// KUMPULAN FUNGSI POP-UP (MODAL & LIGHTBOX)
// ==========================================

// 6. FITUR GALERI LIGHTBOX (POP-UP FOTO BESAR)
function openLightbox(imageSrc, captionText) {
    const modal = document.getElementById("lightboxModal");
    const img = document.getElementById("lightboxImage");
    const cap = document.getElementById("lightboxCaption");
    
    if(modal && img && cap) {
        modal.style.display = "flex";
        img.src = imageSrc;
        cap.innerText = captionText;
    }
}
function closeLightbox() {
    const modal = document.getElementById("lightboxModal");
    if(modal) {
        modal.style.display = "none";
    }
}

// 7. MODAL JADWAL TURNAMEN
function openModal(title) {
    var modal = document.getElementById('rosterModal');
    var titleEl = document.getElementById('modalTournamentTitle');
    
    if (modal && titleEl) {
        titleEl.innerText = title; 
        modal.style.display = 'flex';
    }
}

function closeModal() {
    var modal = document.getElementById('rosterModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 8. MODAL PROFIL PEMAIN
function openPlayerModal(roleId) {
    var modal = document.getElementById('playerProfileModal');
    
    // Sembunyikan semua profil dulu
    var slides = document.getElementsByClassName('profile-slide-item');
    for (var i = 0; i < slides.length; i++) {
        slides[i].style.display = 'none';
    }
    
    // Cari ID profil yang sesuai ('profile-jungler', dll) dan munculkan
    var target = document.getElementById('profile-' + roleId);
    if (target) {
        target.style.display = 'flex';
    }
    
    // Tampilkan background hitam
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closePlayerModal() {
    var modal = document.getElementById('playerProfileModal');
    if (modal) {
        modal.style.display = 'none';
    }
}