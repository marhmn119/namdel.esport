// 1. MENGHILANGKAN LOADING SCREEN OTOMATIS
window.addEventListener('load', function() {
    const loader = document.getElementById('loader');
    if(loader) {
        setTimeout(() => {
            loader.style.display = 'none';
        }, 800); 
    }
});

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
});

// 5. MODAL JADWAL PERTANDINGAN
function openModal(title) {
    const modal = document.getElementById('rosterModal');
    const titleEl = document.getElementById('modalTournamentTitle');
    if(modal && titleEl) {
        titleEl.innerText = title;
        modal.style.display = 'flex';
    }
}
function closeModal() {
    const modal = document.getElementById('rosterModal');
    if(modal) modal.style.display = 'none';
}

// 6. MODAL PROFIL PEMAIN
function openPlayerModal(roleId) {
    const modal = document.getElementById('playerProfileModal');
    document.querySelectorAll('.profile-slide-item').forEach(el => el.style.display = 'none');
    
    const target = document.getElementById('profile-' + roleId);
    if(target) target.style.display = 'flex';
    if(modal) modal.style.display = 'flex';
}
function closePlayerModal() {
    const modal = document.getElementById('playerProfileModal');
    if(modal) modal.style.display = 'none';
}

// 7. FITUR GALERI LIGHTBOX (POP-UP FOTO BESAR)
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

// SLIDER SALAM PEMBUKA DENGAN EFEK GESER HALUS (SLIDE HORIZONTAL)
const salamSlides = [
    { title: "WELCOME TO <span>NAMDEL ESPORT</span>", text: "Selamat datang di web resmi divisi E-Sports SMAN 68 Jakarta. Kami mencari talenta-talenta terbaik yang memiliki mental juara, disiplin tinggi, dan kemauan keras untuk membawa nama sekolah ke puncak arena kompetitif." },
    { title: "VISI & <span>MISI</span>", text: "Membangun ekosistem e-sports sekolah yang solid, menjuarai turnamen pelajar tingkat nasional, dan membawa nama baik SMAN 68 Jakarta di skena kompetitif." },
    { title: "JOIN <span>NAMDEL FAMS</span>", text: "Siapkan mentalmu, asah mekanikmu, dan buktikan bahwa kamu layak menjadi bagian dari keluarga besar divisi E-Sports sekolah kita!" }
];
let currentSlideIndex = 0;
const btnPrev = document.getElementById('prevSlide');
const btnNext = document.getElementById('nextSlide');
const sTitle = document.getElementById('salamTitle');
const sText = document.getElementById('salamText');
const sWrapper = document.getElementById('salamContent');
const dots = document.querySelectorAll('.indicator-dot');

function updateSalamSlide(index, direction = 'next') {
    if(sTitle && sText && sWrapper) {
        // Tentukan arah geser (masuk dari kiri atau kanan)
        const outTransform = direction === 'next' ? 'translateX(-40px)' : 'translateX(40px)';
        const inTransform = direction === 'next' ? 'translateX(40px)' : 'translateX(-40px)';

        // Animasi keluar
        sWrapper.style.opacity = 0;
        sWrapper.style.transform = outTransform;
        
        setTimeout(() => {
            sTitle.innerHTML = salamSlides[index].title;
            sText.innerHTML = salamSlides[index].text;
            
            // Posisikan di arah sebaliknya tanpa transisi agar langsung siap masuk
            sWrapper.style.transition = 'none';
            sWrapper.style.transform = inTransform;
            
            setTimeout(() => {
                // Aktifkan kembali transisi untuk efek geser halus masuk ke tengah
                sWrapper.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                sWrapper.style.opacity = 1;
                sWrapper.style.transform = 'translateX(0)';
            }, 50);
        }, 300);

        dots.forEach((dot, i) => {
            dot.style.background = (i === index) ? "#ff6600" : "#30363d";
        });
    }
}

if(btnPrev && btnNext) {
    btnPrev.addEventListener('click', () => {
        currentSlideIndex = (currentSlideIndex === 0) ? salamSlides.length - 1 : currentSlideIndex - 1;
        updateSalamSlide(currentSlideIndex, 'prev');
    });
    btnNext.addEventListener('click', () => {
        currentSlideIndex = (currentSlideIndex === salamSlides.length - 1) ? 0 : currentSlideIndex + 1;
        updateSalamSlide(currentSlideIndex, 'next');
    });
}