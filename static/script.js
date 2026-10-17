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
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').catch(error => {
            console.error('PWA service worker registration failed:', error);
        });
    }

    const heroEntryType = document.getElementById('heroEntryType');
    const heroResultField = document.getElementById('heroResultField');
    const heroMatchResult = document.getElementById('heroMatchResult');
    if (heroEntryType && heroResultField && heroMatchResult) {
        function updateHeroResultVisibility() {
            const isPick = heroEntryType.value === 'pick';
            heroResultField.hidden = !isPick;
            heroMatchResult.required = isPick;
        }

        heroEntryType.addEventListener('change', updateHeroResultVisibility);
        updateHeroResultVisibility();
    }

    const heroStatisticsTable = document.getElementById('heroStatisticsTable');
    const heroStatisticsData = document.getElementById('heroStatisticsData');
    const heroStatsEvent = document.getElementById('heroStatsEvent');
    if (heroStatisticsTable && heroStatisticsData && heroStatsEvent) {
        const statisticsByEvent = JSON.parse(heroStatisticsData.textContent);
        const heroStatisticsBody = heroStatisticsTable.tBodies[0];
        const topPickList = document.getElementById('heroTopPickList');
        const topBanList = document.getElementById('heroTopBanList');
        const topWinrateList = document.getElementById('heroTopWinrateList');
        let activeSort = null;

        function formatHeroWinrate(value) {
            return `${Number.isInteger(value) ? value : value.toFixed(1)}%`;
        }

        function makeHeroTopRow(hero, rank, value) {
            const item = document.createElement('li');
            const rankLabel = document.createElement('span');
            rankLabel.className = 'hero-top-rank';
            rankLabel.textContent = String(rank);
            const icon = document.createElement('span');
            icon.className = 'hero-table-icon';
            icon.textContent = hero.hero_name.slice(0, 2).toUpperCase();
            const name = document.createElement('strong');
            name.textContent = hero.hero_name;
            const metric = document.createElement('span');
            metric.className = 'hero-top-value';
            metric.textContent = value;
            item.append(rankLabel, icon, name, metric);
            return item;
        }

        function renderTopList(list, heroes, getValue, emptyMessage) {
            if (!list) return;
            const fragment = document.createDocumentFragment();
            if (!heroes.length) {
                const item = document.createElement('li');
                item.className = 'hero-top-empty';
                item.textContent = emptyMessage;
                fragment.appendChild(item);
            } else {
                heroes.forEach((hero, index) => {
                    fragment.appendChild(makeHeroTopRow(hero, index + 1, getValue(hero)));
                });
            }
            list.replaceChildren(fragment);
        }

        function sortHeroRows(key, direction) {
            const rows = [...heroStatisticsBody.rows];
            rows.sort((a, b) => {
                const first = a.dataset[key];
                const second = b.dataset[key];
                const comparison = key === 'name'
                    ? first.localeCompare(second, 'id')
                    : Number(first) - Number(second);
                return direction === 'asc' ? comparison : -comparison;
            });
            rows.forEach(row => heroStatisticsBody.appendChild(row));
            heroStatisticsTable.querySelectorAll('thead th').forEach(header => header.removeAttribute('aria-sort'));
            const button = heroStatisticsTable.querySelector(`thead button[data-sort="${key}"]`);
            if (button) button.closest('th').setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
        }

        function renderHeroStatistics() {
            const stats = statisticsByEvent[heroStatsEvent.value] || statisticsByEvent.all;
            const heroes = stats.heroes;
            renderTopList(
                topPickList,
                heroes.filter(hero => hero.picks > 0)
                    .sort((a, b) => b.picks - a.picks || a.hero_name.localeCompare(b.hero_name, 'id'))
                    .slice(0, 5),
                hero => String(hero.picks),
                'Belum ada data pick.'
            );
            renderTopList(
                topBanList,
                heroes.filter(hero => hero.bans > 0)
                    .sort((a, b) => b.bans - a.bans || a.hero_name.localeCompare(b.hero_name, 'id'))
                    .slice(0, 5),
                hero => String(hero.bans),
                'Belum ada data ban.'
            );
            renderTopList(
                topWinrateList,
                heroes.filter(hero => hero.picks >= 5)
                    .sort((a, b) => b.winrate - a.winrate || b.picks - a.picks || a.hero_name.localeCompare(b.hero_name, 'id'))
                    .slice(0, 5),
                hero => formatHeroWinrate(hero.winrate),
                'Perlu minimal 5 pick untuk masuk peringkat.'
            );

            const fragment = document.createDocumentFragment();
            heroes.forEach(hero => {
                const row = document.createElement('tr');
                row.dataset.name = hero.hero_name.toLocaleLowerCase('id');
                row.dataset.picks = String(hero.picks);
                row.dataset.bans = String(hero.bans);
                row.dataset.wins = String(hero.wins);
                row.dataset.winrate = String(hero.winrate);

                const nameCell = document.createElement('th');
                nameCell.scope = 'row';
                const icon = document.createElement('span');
                icon.className = 'hero-table-icon';
                icon.textContent = hero.hero_name.slice(0, 2).toUpperCase();
                nameCell.append(icon, document.createTextNode(hero.hero_name));
                row.appendChild(nameCell);

                ['picks', 'bans', 'wins'].forEach(key => {
                    const cell = document.createElement('td');
                    cell.textContent = String(hero[key]);
                    row.appendChild(cell);
                });
                const winrateCell = document.createElement('td');
                winrateCell.textContent = formatHeroWinrate(hero.winrate);
                row.appendChild(winrateCell);
                fragment.appendChild(row);
            });
            heroStatisticsBody.replaceChildren(fragment);
            if (activeSort) sortHeroRows(activeSort.key, activeSort.direction);

            const url = new URL(window.location.href);
            if (heroStatsEvent.value === 'all') url.searchParams.delete('hero_event');
            else url.searchParams.set('hero_event', heroStatsEvent.value);
            window.history.replaceState(null, '', url);
        }

        heroStatisticsTable.querySelectorAll('thead button[data-sort]').forEach(button => {
            button.addEventListener('click', () => {
                const key = button.dataset.sort;
                const direction = activeSort?.key === key && activeSort.direction === 'asc' ? 'desc' : 'asc';
                activeSort = { key, direction };
                sortHeroRows(key, direction);
            });
        });
        heroStatsEvent.addEventListener('change', renderHeroStatistics);
        renderHeroStatistics();
    }

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
                if (document.visibilityState === 'visible') {
                    leaderboardRefreshTimer = window.setTimeout(loadLeaderboard, 30000);
                }
            }
        }

        document.querySelectorAll('.leaderboard-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                leaderboardSort = tab.dataset.sort;
                document.querySelectorAll('.leaderboard-tab').forEach(item => item.classList.toggle('active', item === tab));
                if (document.visibilityState === 'visible') loadLeaderboard();
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                loadLeaderboard();
            } else {
                window.clearTimeout(leaderboardRefreshTimer);
            }
        });

        loadLeaderboard();
    }

    // 3. LOGIKA MEGA MENU (SUPER AUTO CLOSE)
    const menuBtn = document.getElementById('menuBtn');
    const megaMenu = document.getElementById('megaMenu');
    
    if (menuBtn && megaMenu) {
        function setMenuOpen(isOpen) {
            megaMenu.hidden = !isOpen;
            menuBtn.setAttribute('aria-expanded', String(isOpen));
        }

        menuBtn.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            setMenuOpen(megaMenu.hidden);
        });

        const menuLinks = megaMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                setMenuOpen(false);
            });
        });

        document.addEventListener('click', function(e) {
            if (!megaMenu.hidden && !megaMenu.contains(e.target) && e.target !== menuBtn) {
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
    document.querySelectorAll('.match-card').forEach(card => {
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openModal(card);
            }
        });
    });

    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                card.click();
            }
        });

        if (window.matchMedia('(hover: hover) and (pointer: fine)').matches
                && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            card.addEventListener('mousemove', event => {
                const bounds = card.getBoundingClientRect();
                const rotateX = ((event.clientY - bounds.top) / bounds.height - 0.5) * -8;
                const rotateY = ((event.clientX - bounds.left) / bounds.width - 0.5) * 8;
                card.style.transform = `perspective(700px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        }
    });

    document.querySelectorAll('.gallery-item, .highlight-card').forEach(card => {
        const open = () => openLightbox(
            card.dataset.image,
            card.dataset.caption || card.getAttribute('aria-label') || '',
            card
        );
        card.addEventListener('click', open);
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                open();
            }
        });
    });
    
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
            const isActive = index === currentSlide;
            dot.classList.toggle('active', isActive);
            dot.style.backgroundColor = isActive ? '#ff6600' : '#ccc';
            dot.setAttribute('aria-current', String(isActive));
        });
    }

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            updateSlide();
        });
    });

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

    function animateCounter(element) {
        const target = Number(element.dataset.target);
        const suffix = element.dataset.suffix || '';
        if (!Number.isFinite(target)) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            element.textContent = `${target}${suffix}`;
            return;
        }

        const duration = 1200;
        const start = performance.now();
        const update = now => {
            const progress = Math.min((now - start) / duration, 1);
            element.textContent = `${Math.floor(target * progress)}${suffix}`;
            if (progress < 1) window.requestAnimationFrame(update);
        };
        window.requestAnimationFrame(update);
    }

    const statsSection = document.querySelector('.stats-section');
    if (statsSection && 'IntersectionObserver' in window) {
        const statsObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.querySelectorAll('.stat-number').forEach(animateCounter);
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        statsObserver.observe(statsSection);
    }

    const scrollTopButton = document.getElementById('scrollTopBtn');
    if (scrollTopButton) {
        window.addEventListener('scroll', () => {
            scrollTopButton.classList.toggle('visible', window.scrollY > 500);
        }, { passive: true });
    }
});

window.toggleFaq = function(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = !answer.classList.contains('open');
    answer.classList.toggle('open', isOpen);
    btn.classList.toggle('active', isOpen);
    btn.setAttribute('aria-expanded', String(isOpen));
    answer.setAttribute('aria-hidden', String(!isOpen));
    answer.style.maxHeight = isOpen ? `${answer.scrollHeight}px` : '0';
};

window.addEventListener('resize', () => {
    document.querySelectorAll('.faq-answer.open').forEach(answer => {
        answer.style.maxHeight = `${answer.scrollHeight}px`;
    });
});

// PENCEGAHAN DOUBLE-SUBMIT PADA FORM (INDIKATOR LOADING)
(function() {
    var formSelectors = [
        '.join-form',       // Form Rekrutmen
        '#predictionForm'   // Form Prediksi
    ];
    formSelectors.forEach(function(selector) {
        var form = document.querySelector(selector);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            var submitBtn = form.querySelector('button[type="submit"], .btn-submit');
            if (!submitBtn) return;
            if (submitBtn.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }
            submitBtn.dataset.submitting = 'true';
            submitBtn.classList.add('btn-submitting');
            var originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sedang Mengirim...';
            // Restore button after 8 seconds in case of network issues
            setTimeout(function() {
                submitBtn.dataset.submitting = 'false';
                submitBtn.classList.remove('btn-submitting');
                submitBtn.textContent = originalText;
            }, 8000);
        });
    });
})();

// ==========================================
// KUMPULAN FUNGSI POP-UP (MODAL & LIGHTBOX)
// ==========================================

let activeDialog = null;
let dialogReturnFocus = null;

function showDialog(dialog, trigger = document.activeElement) {
    if (activeDialog && activeDialog !== dialog) closeActiveDialog();
    dialogReturnFocus = trigger instanceof HTMLElement && trigger !== document.body ? trigger : null;
    activeDialog = dialog;
    dialog.style.display = 'flex';
    dialog.setAttribute('aria-hidden', 'false');
    const closeButton = dialog.querySelector('.detail-modal-close, .close-btn, .close-lightbox');
    if (closeButton) closeButton.focus();
}

function closeActiveDialog() {
    if (!activeDialog) return;
    const dialog = activeDialog;
    const returnFocus = dialogReturnFocus;
    dialog.style.display = 'none';
    dialog.setAttribute('aria-hidden', 'true');
    activeDialog = null;
    dialogReturnFocus = null;
    if (returnFocus && returnFocus.isConnected) returnFocus.focus();
}

// 6. FITUR GALERI LIGHTBOX (POP-UP FOTO BESAR)
function openLightbox(imageSrc, captionText, trigger) {
    const modal = document.getElementById("lightboxModal");
    const img = document.getElementById("lightboxImage");
    const cap = document.getElementById("lightboxCaption");
    
    if(modal && img && cap) {
        img.src = imageSrc;
        img.alt = captionText;
        cap.innerText = captionText;
        showDialog(modal, trigger || document.activeElement);
    }
}
function closeLightbox() {
    const modal = document.getElementById("lightboxModal");
    if (modal && activeDialog === modal) closeActiveDialog();
}

// 7. MODAL JADWAL TURNAMEN
function openModal(matchCard) {
    const modal = document.getElementById('matchDetailModal');
    if (!modal || !matchCard || !matchCard.dataset) return;
    document.getElementById('matchModalTitle').textContent = matchCard.dataset.title || '';
    document.getElementById('matchModalCompetition').textContent = matchCard.dataset.competition || '';
    document.getElementById('matchModalTeams').textContent =
        `${matchCard.dataset.home} vs ${matchCard.dataset.away} • ${matchCard.dataset.score}`;
    document.getElementById('matchModalDescription').textContent = matchCard.dataset.description || '';
    document.getElementById('homeVoteLabel').textContent = matchCard.dataset.home;
    document.getElementById('awayVoteLabel').textContent = matchCard.dataset.away;
    const homePercent = Number(matchCard.dataset.homePercent) || 0;
    const awayPercent = Number(matchCard.dataset.awayPercent) || 0;
    document.getElementById('homeVotePercent').textContent = homePercent;
    document.getElementById('awayVotePercent').textContent = awayPercent;
    document.getElementById('homeVoteProgress').value = homePercent;
    document.getElementById('awayVoteProgress').value = awayPercent;
    document.getElementById('voteHome').textContent = matchCard.dataset.home;
    document.getElementById('voteAway').textContent = matchCard.dataset.away;
    document.getElementById('voteHome').value = matchCard.dataset.home;
    document.getElementById('voteAway').value = matchCard.dataset.away;
    document.getElementById('predictionForm').action = `/vote/${encodeURIComponent(matchCard.dataset.id)}`;
    document.getElementById('predictionBlock').hidden =
        matchCard.dataset.status !== 'upcoming' || Date.parse(matchCard.dataset.targetdate) <= Date.now();
    showDialog(modal, matchCard);
}

function closeModal() {
    var modal = document.getElementById('matchDetailModal');
    if (modal && activeDialog === modal) closeActiveDialog();
}

// 8. MODAL PROFIL PEMAIN
function openPlayerModal(roleId, trigger) {
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
    
    if (modal) {
        showDialog(modal, trigger || document.activeElement);
        window.requestAnimationFrame(() => {
            if (typeof window.initPlayerCharts === 'function') window.initPlayerCharts(roleId);
        });
    }
}

function closePlayerModal() {
    var modal = document.getElementById('playerProfileModal');
    if (modal && activeDialog === modal) closeActiveDialog();
}

document.addEventListener('keydown', event => {
    if (!activeDialog) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        closeActiveDialog();
        return;
    }
    if (event.key !== 'Tab') return;

    const focusable = [...activeDialog.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )].filter(element => element.getClientRects().length > 0);
    if (!focusable.length) {
        event.preventDefault();
        activeDialog.focus();
        return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && (document.activeElement === first || !activeDialog.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !activeDialog.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
    }
});

const commonRadarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        r: {
            angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
            pointLabels: { color: '#94a3b8', font: { family: 'Poppins', size: 10 } },
            min: 0,
            max: 100,
            ticks: { display: false, stepSize: 20 },
        },
    },
    plugins: { legend: { display: false } },
};

const playerData = {
    jungler: { data: [95, 80, 95, 85, 60], color: 'rgba(255, 102, 0, 1)', bg: 'rgba(255, 102, 0, 0.4)' },
    explaner: { data: [75, 95, 80, 75, 85], color: 'rgba(34, 197, 94, 1)', bg: 'rgba(34, 197, 94, 0.4)' },
    midlaner: { data: [85, 95, 75, 85, 70], color: 'rgba(168, 85, 247, 1)', bg: 'rgba(168, 85, 247, 0.4)' },
    roamer: { data: [50, 95, 95, 70, 60], color: 'rgba(59, 130, 246, 1)', bg: 'rgba(59, 130, 246, 0.4)' },
    goldlaner: { data: [95, 85, 80, 90, 90], color: 'rgba(234, 179, 8, 1)', bg: 'rgba(234, 179, 8, 0.4)' },
};

const radarCharts = {};

window.initPlayerCharts = function(roleId) {
    if (!window.Chart) {
        console.error('Chart.js gagal dimuat; statistik pemain tidak dapat ditampilkan.');
        // Show visible fallback message in each chart container
        (roleId ? [roleId] : Object.keys(playerData)).forEach(function(role) {
            var canvas = document.getElementById('radar-' + role);
            if (canvas) {
                var wrapper = canvas.parentElement;
                if (wrapper && !wrapper.querySelector('.chart-fallback')) {
                    var fallback = document.createElement('div');
                    fallback.className = 'chart-fallback';
                    fallback.style.cssText = 'text-align:center;padding:20px;color:#94a3b8;font-size:0.85rem;';
                    fallback.innerHTML = '⚠️ Grafik statistik tidak dapat dimuat.<br>Periksa koneksi internet Anda.';
                    wrapper.appendChild(fallback);
                }
            }
        });
        return;
    }
    (roleId ? [roleId] : Object.keys(playerData)).forEach(role => {
        const canvas = document.getElementById(`radar-${role}`);
        if (canvas && !radarCharts[role]) {
            const roleConfig = playerData[role] || {
                color: 'rgba(255, 102, 0, 1)',
                bg: 'rgba(255, 102, 0, 0.4)'
            };
            const chartData = canvas.dataset.stats
                ? canvas.dataset.stats.split(',').map(Number)
                : (playerData[role] ? playerData[role].data : [80, 80, 80, 80, 80]);
            radarCharts[role] = new window.Chart(canvas, {
                type: 'radar',
                data: {
                    labels: ['Farming', 'Teamfight', 'Objective', 'KDA', 'Push'],
                    datasets: [{
                        label: 'Stats',
                        data: chartData,
                        backgroundColor: roleConfig.bg,
                        borderColor: roleConfig.color,
                        borderWidth: 2,
                        pointBackgroundColor: roleConfig.color,
                    }],
                },
                options: commonRadarOptions,
            });
        } else if (radarCharts[role]) {
            radarCharts[role].resize();
        }
    });
};