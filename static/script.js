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

    // Leaderboard scrim internal: refresh from the server without reloading the page.
    const leaderboardRows = document.getElementById('leaderboardRows');
    if (leaderboardRows) {
        let leaderboardSort = 'points';
        const updatedLabel = document.getElementById('leaderboardUpdated');
        const mvpName = document.getElementById('weeklyMvpName');
        const mvpMeta = document.getElementById('weeklyMvpMeta');
        let leaderboardRefreshTimer;

        function addCell(row, value, className) {
            const cell = document.createElement('td');
            cell.textContent = value;
            if (className) cell.className = className;
            row.appendChild(cell);
        }

        function renderLeaderboard(data) {
            leaderboardRows.replaceChildren();
            if (!data.players.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 8;
                cell.className = 'leaderboard-empty';
                cell.textContent = 'Belum ada data scrim. Hasil pertandingan akan muncul di sini.';
                row.appendChild(cell);
                leaderboardRows.appendChild(row);
            } else {
                data.players.forEach(player => {
                    const row = document.createElement('tr');
                    addCell(row, String(player.rank).padStart(2, '0'), 'leaderboard-rank');

                    const nameCell = document.createElement('td');
                    nameCell.className = 'leaderboard-player';
                    const name = document.createElement('strong');
                    name.textContent = player.name;
                    const team = document.createElement('span');
                    team.textContent = player.team;
                    nameCell.append(name, team);
                    row.appendChild(nameCell);

                    addCell(row, player.points.toLocaleString('id-ID'), 'leaderboard-points');
                    addCell(row, player.kills);
                    addCell(row, player.deaths);
                    addCell(row, player.assists);
                    addCell(row, player.kda.toFixed(2));
                    addCell(row, player.weekly_mvp);
                    leaderboardRows.appendChild(row);
                });
            }

            if (mvpName && mvpMeta) {
                if (data.weekly_mvp) {
                    mvpName.textContent = data.weekly_mvp.name;
                    mvpMeta.textContent = `${data.weekly_mvp.team} • ${data.weekly_mvp.weekly_mvp} MVP`;
                } else {
                    mvpName.textContent = 'Belum ada MVP';
                    mvpMeta.textContent = 'Catatan scrim minggu ini akan tampil di sini.';
                }
            }
            if (updatedLabel) {
                updatedLabel.textContent = `Pekan ${new Date(`${data.week_start}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} – ${new Date(`${data.week_end}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} • Update ${new Date(data.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;
            }
        }

        async function loadLeaderboard() {
            window.clearTimeout(leaderboardRefreshTimer);
            try {
                const response = await fetch(`/api/leaderboard?sort=${encodeURIComponent(leaderboardSort)}`, { cache: 'no-store' });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                renderLeaderboard(await response.json());
            } catch (error) {
                console.error('Gagal memuat leaderboard:', error);
                if (updatedLabel) updatedLabel.textContent = 'Leaderboard gagal dimuat. Coba lagi sebentar.';
            } finally {
                leaderboardRefreshTimer = window.setTimeout(loadLeaderboard, 30000);
            }
        }

        document.querySelectorAll('.leaderboard-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                leaderboardSort = tab.dataset.sort;
                document.querySelectorAll('.leaderboard-tab').forEach(item => item.classList.toggle('active', item === tab));
                loadLeaderboard();
            });
        });

        loadLeaderboard();
    }

    // 3. LOGIKA MEGA MENU (SUPER AUTO CLOSE)
    const menuBtn = document.getElementById('menuBtn');
    const megaMenu = document.getElementById('megaMenu');
    
    if (menuBtn && megaMenu) {
        function setMenuOpen(isOpen) {
            megaMenu.style.display = isOpen ? 'block' : 'none';
            menuBtn.setAttribute('aria-expanded', String(isOpen));
        }

        menuBtn.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            setMenuOpen(megaMenu.style.display !== 'block');
        });

        const menuLinks = megaMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                setMenuOpen(false);
            });
        });

        document.addEventListener('click', function(e) {
            if (megaMenu.style.display === 'block' && !megaMenu.contains(e.target) && e.target !== menuBtn) {
                setMenuOpen(false);
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