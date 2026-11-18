<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff6600">
    <title>NAMDEL ESPORT - SMAN 68</title>
    <link rel="icon" type="image/png" href="{{ asset('static/logonest.png') }}">
    <link rel="manifest" href="{{ asset('static/manifest.json') }}">
    <link rel="stylesheet" href="{{ asset('static/style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <meta name="description" content="Website resmi NAMDEL ESPORT, tim esports SMAN 68 Jakarta. Jadwal, roster MLBB dan HOK, prestasi, dan rekrutmen.">
    <meta property="og:title" content="NAMDEL ESPORT - SMAN 68">
    <meta property="og:image" content="{{ asset('static/logonest.png') }}">
</head>
<body>
    @if (session('success') || session('error'))
        <div class="flash-container" role="status">
            @foreach (['success', 'error'] as $type)
                @if (session($type))
                    <div class="flash flash-{{ $type }}">{{ session($type) }}</div>
                @endif
            @endforeach
        </div>
    @endif

    <nav>
        <div class="logo-container">
            <img class="nav-logo nav-logo-school" src="{{ asset('static/logo68.png') }}" alt="SMAN 68" width="70" height="70">
            <img class="nav-logo nav-logo-team" src="{{ asset('static/logonest.png') }}" alt="NEST" width="70" height="70">
        </div>
        <div class="nav-links-center">
            <a href="#jadwal">Jadwal</a><a href="#statistik">Statistik</a>
            <a href="#prestasi">Prestasi</a><a href="#roster">Roster</a>
        </div>
        <div class="nav-right-controls">
            <button class="menu-btn" id="menuBtn" aria-controls="megaMenu" aria-expanded="false">Menu <span>☰</span></button>
        </div>
    </nav>
    <div id="megaMenu" class="mega-menu-panel" hidden>
        <div class="mega-menu-grid">
        <div class="mega-menu-column"><a href="#home">Beranda</a><a href="#jadwal">Jadwal Turnamen</a><a href="#statistik">Statistik Tim</a></div>
        <div class="mega-menu-column"><a href="#recap-turnamen">Recap Turnamen</a><a href="#roster">Roster</a><a href="#galeri">Galeri</a></div>
        <div class="mega-menu-column"><a href="#komunitas">Komunitas</a><a href="#prestasi">Prestasi</a><a href="#join">Rekrutmen</a></div>
        <div class="mega-menu-column"><a href="#faq">FAQ</a><a href="#merchandise">Merchandise</a><a href="#partnership">Sponsor</a><a href="#kontak">Kontak</a><a href="{{ route('login') }}">Admin</a></div>
        </div>
    </div>

    <header id="home" class="hero">
        <div class="hero-content">
            <p class="section-subtitle">SMAN 68 JAKARTA • ESPORTS</p>
            <h1>NAMDEL <span>ESPORT</span></h1>
            <p>Kompetitif, suportif, dan terus mengukir prestasi bersama.</p>
            <a href="#jadwal" class="hero-action hero-action-primary">LIHAT JADWAL <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    @if ($tickers->isNotEmpty())
        <div class="ticker-section" aria-label="Pengumuman">
            @foreach ($tickers as $ticker)
                <span>{{ $ticker->content }}</span>
            @endforeach
        </div>
    @endif

    <section id="salam" class="salam-section">
        <div class="salam-content-wrapper">
            <p class="section-subtitle">SELAMAT DATANG DI WEBSITE RESMI</p>
            <h2 class="center-title">WELCOME TO <span>NAMDEL ESPORT</span></h2>
            <p class="salam-text">Tim esports SMAN 68 Jakarta. Kami mencari talenta terbaik dengan mental juara, disiplin tinggi, dan semangat kerja sama.</p>
        </div>
    </section>

    <section id="jadwal" class="schedule-section">
        <div class="schedule-container">
            <div class="schedule-left"><p class="section-subtitle">NEXT UP / MATCH CENTER</p><h2>SAATNYA BERLAGA.</h2></div>
            <div class="schedule-right">
                @foreach (['MLBB', 'HOK'] as $game)
                    <div class="match-game-column">
                        <div class="match-header"><span>{{ $game }} • PERTANDINGAN TERBARU</span><span>WIB / UTC+7</span></div>
                        <div class="match-game-panel" id="match-panel-{{ strtolower($game) }}" role="region" aria-label="Jadwal {{ $game }}">
                            @forelse ($matches->where('game', $game) as $match)
                                <article class="match-card" role="group" tabindex="0"
                                    data-id="{{ $match->id }}" data-title="{{ $match->title }}" data-competition="{{ $match->competition }}"
                                    data-home="{{ $match->team_home }}" data-away="{{ $match->team_away }}"
                                    data-description="{{ $match->description }}" data-status="{{ $match->status }}"
                                    data-score="{{ $match->score_home }} - {{ $match->score_away }}"
                                    data-home-percent="{{ $match->home_percent }}" data-away-percent="{{ $match->away_percent }}"
                                    data-targetdate="{{ $match->date }}T{{ substr($match->time ?: '00:00:00', 0, 8) }}+07:00">
                                    <div class="match-date">{{ \Illuminate\Support\Carbon::parse($match->date)->locale('id')->translatedFormat('d M Y') }}</div>
                                    <div class="match-info">
                                        <p class="league">{{ $match->competition }}</p><h3>{{ $match->title }}</h3>
                                        @if ($match->team_home || $match->team_away)
                                            <p>{{ $match->team_home ?: 'Tim belum ditentukan' }} <span>VS</span> {{ $match->team_away ?: 'Tim belum ditentukan' }}</p>
                                        @else
                                            <p>Tim pertandingan akan diumumkan.</p>
                                        @endif
                                        @if ($match->status === 'finished')
                                            <p class="match-score">{{ $match->team_home }} {{ $match->score_home }} - {{ $match->score_away }} {{ $match->team_away }}</p>
                                        @else
                                            <p class="match-timer-text">⏱️ <span class="countdown-clock">Menghitung waktu...</span></p>
                                        @endif
                                    </div>
                                    <div class="match-time">{{ substr($match->time ?: '', 0, 5) }} WIB</div>
                                    @if ($match->status === 'upcoming' && $match->team_home && $match->team_away)
                                        <form action="{{ route('vote', $match->id) }}" method="POST" class="match-vote-form">
                                            @csrf
                                            <span>Prediksi pemenang:</span>
                                            <button type="submit" name="winner" value="{{ $match->team_home }}">{{ $match->team_home }}</button>
                                            <button type="submit" name="winner" value="{{ $match->team_away }}">{{ $match->team_away }}</button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <div class="schedule-empty-state"><div class="empty-state-icon">📅</div><h3>Belum Ada Jadwal {{ $game }}</h3><p>Jadwal pertandingan {{ $game }} akan segera diumumkan.</p></div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div id="matchDetailModal" class="detail-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="matchModalTitle" onclick="if(event.target === this) closeModal()">
        <div class="detail-modal-content">
            <button type="button" class="detail-modal-close" onclick="closeModal()" aria-label="Tutup">&times;</button>
            <p id="matchModalCompetition" class="section-subtitle"></p>
            <h2 id="matchModalTitle"></h2>
            <p id="matchModalTeams"></p>
            <p id="matchModalDescription"></p>
            <div id="predictionBlock" hidden>
                <h3>Prediksi Pemenang</h3>
                <p><span id="homeVoteLabel"></span> <span id="homeVotePercent"></span>%</p>
                <progress id="homeVoteProgress" max="100" value="0"></progress>
                <p><span id="awayVoteLabel"></span> <span id="awayVotePercent"></span>%</p>
                <progress id="awayVoteProgress" max="100" value="0"></progress>
                <form id="predictionForm" method="POST">@csrf
                    <button type="submit" name="winner" id="voteHome"></button>
                    <button type="submit" name="winner" id="voteAway"></button>
                </form>
            </div>
        </div>
    </div>

    <section id="media-center" class="media-section">
        <div class="media-section-inner">
            <div class="media-section-header"><h2 class="center-title">MATCH <span>VODS &amp; HIGHLIGHTS</span></h2><p>Saksikan kembali pertandingan dan cuplikan kegiatan NAMDEL ESPORT.</p></div>
            <div class="media-grid"><div class="media-video-card"><div class="media-video-wrapper">
                <iframe src="{{ $highlightVideoUrl }}" title="Rekaman pertandingan esports" frameborder="0" allowfullscreen loading="lazy"></iframe>
            </div></div></div>
        </div>
    </section>

    <section id="statistik" class="stats-section">
        <h2 class="center-title" style="text-align:center;margin-bottom:50px;color:white">STATISTIK TIM</h2>
        <div class="game-team-stats-grid">
            @foreach (['MLBB', 'HOK'] as $game)
                @php($stats = $teamStatistics[$game])
                <article class="game-team-stats-card"><p class="game-stats-eyebrow">STATISTIK TIM</p><h3>{{ $game }}</h3>
                    <div class="team-stat-numbers">
                        <div><strong>{{ $stats['matches'] }}</strong><span>Match</span></div>
                        <div><strong>{{ $stats['wins'] }}</strong><span>Menang</span></div>
                        <div><strong>{{ $stats['losses'] }}</strong><span>Kalah</span></div>
                        <div><strong>{{ $stats['winrate'] }}%</strong><span>Win rate</span></div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="recap-turnamen" class="tournament-recaps-section">
        <div class="recap-section-heading"><p class="section-subtitle">TOURNAMENT ARCHIVE</p><h2 class="center-title">RECAP <span>TURNAMEN</span></h2><p>Bracket, hasil pertandingan, dan performa tim di setiap turnamen.</p></div>
        @foreach (['MLBB', 'HOK'] as $game)
            <div class="recap-game-group"><h3>{{ $game }} <span>RECAP</span></h3><div class="recap-card-grid">
                @forelse ($recaps->where('game', $game) as $recap)
                    <article class="recap-card"><div class="recap-card-heading"><div><span>{{ \Illuminate\Support\Carbon::parse($recap->event_date)->locale('id')->translatedFormat('d M Y') }}</span><h4>{{ $recap->event_name }}</h4></div><strong>{{ $game }}</strong></div>
                        @if ($recap->summary)<p class="recap-summary">{{ $recap->summary }}</p>@endif
                        <div class="recap-images">
                            @if ($recap->bracket_image)<a href="{{ asset('static/uploads/'.$recap->bracket_image) }}" target="_blank" rel="noopener"><img loading="lazy" src="{{ asset('static/uploads/'.$recap->bracket_image) }}" alt="Bracket {{ $recap->event_name }}"><span>Bracket</span></a>@endif
                            @if ($recap->result_image)<a href="{{ asset('static/uploads/'.$recap->result_image) }}" target="_blank" rel="noopener"><img loading="lazy" src="{{ asset('static/uploads/'.$recap->result_image) }}" alt="Hasil {{ $recap->event_name }}"><span>Hasil Pertandingan</span></a>@endif
                        </div>
                        @if ($recap->team_stats)<div class="recap-stat-note"><strong>Statistik Tim</strong><p>{{ $recap->team_stats }}</p></div>@endif
                        @if ($recap->hero_stats->isNotEmpty())<div class="recap-hero-stats"><strong>Hero turnamen</strong><ul>@foreach ($recap->hero_stats as $hero)<li>{{ $hero->hero_name }} <span>Pick {{ $hero->picks }} · Ban {{ $hero->bans }} · Win {{ $hero->wins }}</span></li>@endforeach</ul></div>@endif
                    </article>
                @empty
                    <p class="recap-empty-state">Recap turnamen {{ $game }} belum tersedia.</p>
                @endforelse
            </div></div>
        @endforeach
    </section>

    <section id="roster" class="roster-section roster-section-styled"><div class="roster-container">
        @foreach (['MLBB', 'HOK'] as $game)
            <div class="team-roster-group" style="margin-bottom:50px"><div class="roster-heading"><div><p class="roster-eyebrow">NEST • TIM UTAMA</p><h2 class="roster-title">ROSTER <span>{{ $game }}</span></h2></div></div>
                <div class="roster-grid" id="roster-lineup-{{ strtolower($game) }}">
                    @forelse ($rosterByGame->get($game, collect()) as $player)
                        <article class="card clickable-card roster-card" role="button" tabindex="0" data-profile-id="{{ $player->role_id }}" aria-label="Lihat profil {{ $player->name }}, {{ $player->role }}"><img loading="lazy" src="{{ asset('static/'.$player->photo) }}" alt="{{ $player->name }}, {{ $player->role }}" width="375" height="666" class="roster-card-img"><div class="roster-card-info"><h3 class="roster-card-name">{{ $player->name }}</h3><p class="roster-card-role">{{ $player->role }}</p></div></article>
                    @empty
                        <p class="recap-empty-state">Roster {{ $game }} akan segera diumumkan.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div></section>

    <div id="playerProfileModal" class="modal player-profile-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Profil pemain" onclick="if(event.target === this) closePlayerModal()">
        <div class="modal-content player-profile-modal-content">
            <button type="button" onclick="closePlayerModal()" class="close-btn" aria-label="Tutup profil pemain">&times;</button>
            @foreach ($roster as $player)
                <div class="profile-slide-item" id="profile-{{ $player->role_id }}" style="display:none">
                    <div class="player-profile-summary">
                        <div class="player-profile-copy">
                            <h2 class="player-name">{{ $player->name }}</h2>
                            <div class="player-role-tag">{{ $player->role_tag }}</div>
                            <p class="player-description">{{ $player->description }}</p>
                            @if ($player->signature_heroes)
                                <div class="player-signature" style="margin-top:20px">
                                    <strong style="color:#ffcc00;display:block;margin-bottom:10px">Signature Heroes:</strong>
                                    <div class="hero-showcase">
                                        @foreach ($player->signature_heroes as $hero)
                                            <div class="hero-item">
                                                @if (!empty($hero['img']))<img src="{{ asset('static/'.$hero['img']) }}" alt="{{ $hero['name'] ?? 'Hero' }}" class="hero-img">@endif
                                                <div class="hero-stats"><span class="hero-name">{{ $hero['name'] ?? '' }}</span>@if (!empty($hero['wr']))<span class="hero-wr">{{ $hero['wr'] }}</span>@endif</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <img loading="lazy" class="player-portrait" src="{{ asset('static/'.$player->photo) }}" alt="{{ $player->name }}" width="375" height="666">
                    </div>
                    <div class="player-stat-panel"><h3>Statistik Gaya Main</h3><div class="player-chart-wrap"><canvas id="radar-{{ $player->role_id }}" data-stats="{{ $player->stats }}"></canvas></div></div>
                </div>
            @endforeach
        </div>
    </div>

    <section id="galeri" class="gallery-section gallery-section-styled"><h2 class="center-title">DOKUMENTASI <span>KEGIATAN</span></h2><div class="gallery-container gallery-container-styled"><div class="gallery-track gallery-track-styled">
        @foreach ($gallery as $item)<article class="gallery-item gallery-item-styled" role="button" tabindex="0" data-image="{{ asset('static/'.$item->image) }}" data-caption="{{ $item->title }}" aria-label="Buka gambar {{ $item->title }}"><img loading="lazy" src="{{ asset('static/'.$item->image) }}" alt="{{ $item->title }}" class="gallery-item-img"><p class="gallery-item-caption">{{ $item->title }}</p></article>@endforeach
    </div></div></section>
    <div id="lightboxModal" class="lightbox-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="lightboxCaption" onclick="if(event.target === this) closeLightbox()">
        <button type="button" class="close-lightbox" onclick="closeLightbox()" aria-label="Tutup gambar">&times;</button>
        <img class="lightbox-content" id="lightboxImage" src="" alt="">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>

    <section id="legacy" class="legacy-section dark-section"><h2 class="center-title" style="text-align:left;padding-left:10%">JEJAK <span>LANGKAH</span></h2>
        <div class="timeline-container">
            <div class="timeline-item"><div class="timeline-year">2021</div><div class="timeline-info"><h3>Awal Mula NAMDEL ESPORT Berdiri</h3><p>Divisi esports SMAN 68 Jakarta mulai berkembang untuk meraih prestasi di bidang esports.</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2022</div><div class="timeline-info"><h3>Pembentukan Tim Kompetitif</h3><p>Penyeleksian tim, latihan intensif, dan persiapan mengikuti kejuaraan.</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2023</div><div class="timeline-info"><h3>Awal Perjalanan Turnamen</h3><p>Komunikasi dan kerja sama tim menjadi bagian dari perjalanan kompetitif.</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2024-2025</div><div class="timeline-info"><h3>Pengembangan Tim dan Kompetisi</h3><p>Latihan, scrim, dan evaluasi strategi untuk meningkatkan kerja sama.</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2026</div><div class="timeline-info"><h3>Perjalanan Baru</h3><p>Membangun tim yang lebih kuat melalui latihan, komunikasi, dan kekeluargaan.</p></div></div>
        </div>
    </section>

    <section id="prestasi" class="achievement-section"><h2 class="center-title">PRESTASI <span>KAMI</span></h2><div class="achievement-grid">
        @forelse ($achievements as $achievement)<article class="achievement-card"><span>{{ $achievement->icon }}</span><h3>{{ $achievement->title }}</h3><p>{{ $achievement->event }} • {{ $achievement->year }}</p></article>@empty<p>Prestasi akan segera diumumkan.</p>@endforelse
    </div></section>

    <section id="join" class="join-section dark-section"><div class="join-container"><p class="section-subtitle">OPEN RECRUITMENT</p><h2>GABUNG <span>NAMDEL ESPORT</span></h2><p>Tunjukkan kemampuan dan jadilah bagian dari tim esports SMAN 68 Jakarta.</p>
        <form action="{{ route('register') }}" method="POST" enctype="multipart/form-data" class="join-form">
            @csrf
            <label for="name">Nama lengkap</label><input id="name" name="nama" maxlength="100" value="{{ old('nama') }}" required>
            <label for="role">Role</label><select id="role" name="role" required><option value="">Pilih role</option>@foreach (['Jungler','Roamer','Mid Laner','Gold Laner','Exp Laner','Coach/Manager'] as $role)<option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>@endforeach</select>
            <label for="contact">WhatsApp</label><input id="contact" name="whatsapp" placeholder="08xx atau 628xx" value="{{ old('whatsapp') }}" required>
            <label for="foto">Bukti rank (JPG/PNG/WEBP, maks. 5 MB)</label><input id="foto" type="file" name="foto_rank" accept=".jpg,.jpeg,.png,.webp" required>
            @if ($errors->any())<ul class="form-errors">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
            <button type="submit" class="save-btn">Kirim Pendaftaran</button>
        </form>
    </div></section>

    <section id="komunitas" class="community-section community-section-styled"><div class="community-content community-inner">
        <div class="community-text-left community-text-block"><h2>GABUNG<br><span>NAMDEL FAMS</span></h2><p>Jadilah bagian dari perjalanan kami. Dapatkan update turnamen, mabar bareng, dan dukung tim kebanggaan SMAN 68 Jakarta!</p></div>
        <div class="community-cards-right community-buttons">
            <a href="https://whatsapp.com/channel/0029VbD4U4zAu3aIU58Wzm2v" target="_blank" rel="noopener noreferrer" class="community-wa-btn"><div class="community-link-icon">💬</div><div><h3 class="community-link-title">GABUNG WHATSAPP</h3><p class="community-link-desc">Saluran &amp; Grup Resmi Komunitas ↗</p></div></a>
            <a href="https://www.instagram.com/nest.esports" target="_blank" rel="noopener noreferrer" class="community-ig-btn"><div class="community-link-icon">📸</div><div><h3 class="community-link-title">FOLLOW INSTAGRAM</h3><p class="community-link-desc">Update Info &amp; Dokumentasi Terbaru ↗</p></div></a>
        </div>
    </div></section>

    <section id="faq" class="faq-section"><h2 class="center-title">FAQ <span>REKRUTMEN</span></h2><div class="faq-container">
        <div class="faq-item"><button class="faq-question" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-answer-1">Apa syarat untuk bergabung NAMDEL ESPORT?<span class="faq-toggle">+</span></button><div class="faq-answer" id="faq-answer-1" aria-hidden="true"><p>Kamu harus merupakan siswa aktif SMAN 68 Jakarta dan bersedia mengikuti latihan rutin tim.</p></div></div>
        <div class="faq-item"><button class="faq-question" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-answer-2">Berapa kali latihan dalam seminggu?<span class="faq-toggle">+</span></button><div class="faq-answer" id="faq-answer-2" aria-hidden="true"><p>Latihan rutin dilakukan beberapa kali seminggu. Jadwal scrim dan latihan disesuaikan dengan kegiatan sekolah dan turnamen.</p></div></div>
        <div class="faq-item"><button class="faq-question" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-answer-3">Apakah ada biaya untuk bergabung?<span class="faq-toggle">+</span></button><div class="faq-answer" id="faq-answer-3" aria-hidden="true"><p>Tidak ada biaya pendaftaran untuk mengikuti seleksi NAMDEL ESPORT.</p></div></div>
        <div class="faq-item"><button class="faq-question" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-answer-4">Bagaimana proses seleksi pemain baru?<span class="faq-toggle">+</span></button><div class="faq-answer" id="faq-answer-4" aria-hidden="true"><p>Setelah mendaftar, calon pemain akan dihubungi untuk proses seleksi dan tryout bersama tim.</p></div></div>
        <div class="faq-item"><button class="faq-question" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-answer-5">Role apa saja yang bisa mendaftar?<span class="faq-toggle">+</span></button><div class="faq-answer" id="faq-answer-5" aria-hidden="true"><p>Semua role dan posisi kepelatihan dapat mendaftar melalui formulir rekrutmen.</p></div></div>
    </div></section>

    <section id="merchandise" class="merch-section merch-section-styled"><h2 class="center-title">KATALOG <span>MERCHANDISE</span></h2><div class="merch-grid merch-grid-styled">
        @foreach ([['logo' => 'logonest.png', 'name' => 'JERSEY TIM E-SPORT 2026', 'price' => 'Rp 150.000', 'message' => 'Jersey%20Tim%20E-Sport'], ['logo' => 'logo68.png', 'name' => 'LANYARD EKSKLUSIF FAMS', 'price' => 'Rp 35.000', 'message' => 'Lanyard%20Eksklusif'], ['logo' => 'logonest.png', 'name' => 'T-SHIRT SUPPORTER 68', 'price' => 'Rp 100.000', 'message' => 'T-Shirt%20Supporter']] as $item)
            <article class="merch-card merch-card-styled"><img loading="lazy" src="{{ asset('static/'.$item['logo']) }}" alt="{{ $item['name'] }}" class="merch-card-img"><div class="merch-card-body"><h3 class="merch-card-title">{{ $item['name'] }}</h3><p class="merch-card-price">{{ $item['price'] }}</p><a href="https://wa.me/{{ $whatsappNumber }}?text=Halo%20Admin,%20saya%20ingin%20memesan%20{{ $item['message'] }}" target="_blank" rel="noopener noreferrer" class="merch-card-wa-btn">Pesan via WhatsApp ↗</a></div></article>
        @endforeach
    </div></section>

    <section id="partnership" class="partner-section"><h2 class="center-title">MITRA <span>RESMI</span></h2><p class="partner-description">Terima kasih kepada sekolah dan mitra yang mendukung perjalanan NAMDEL ESPORT.</p><div class="partner-grid">
        @foreach (['logo68.png' => 'SMAN 68 Jakarta', 'logonest.png' => 'NEST Esport', 'realme.png' => 'Realme'] as $logo => $name)<div class="partner-logo"><img loading="lazy" src="{{ asset('static/'.$logo) }}" alt="{{ $name }}"></div>@endforeach
    </div><a class="partner-placeholder" href="https://wa.me/{{ $whatsappNumber }}?text=Halo%20Admin%2C%20saya%20tertarik%20menjadi%20sponsor%20NAMDEL%20ESPORT." target="_blank" rel="noopener noreferrer">Jadilah sponsor kami via WhatsApp ↗</a>
        <div class="partner-container"><h2>INQUIRY <span>SPONSOR</span></h2><p>Tertarik mendukung NAMDEL ESPORT?</p>
            <form action="{{ route('sponsorship') }}" method="POST" class="partner-form">@csrf
                <label>Perusahaan<input name="company" maxlength="150" required></label><label>Nama PIC<input name="contact_person" maxlength="100" required></label><label>Email<input type="email" name="email" maxlength="254" required></label>
                <label>Paket sponsor<select name="tier" required>@foreach ($sponsorTiers as $tier)<option value="{{ $tier }}">{{ ucfirst($tier) }}</option>@endforeach</select></label><button type="submit" class="save-btn">Kirim inquiry</button>
            </form>
        </div>
    </section>
    <footer id="kontak" class="footer-section footer-section-styled"><h2>HUBUNGI <span>KAMI</span></h2><p style="color:var(--text-gray);margin-bottom:25px">Ada pertanyaan atau tawaran kerja sama? Hubungi Humas kami.</p><a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener noreferrer" class="btn-wa">Chat Manajer ↗</a><p>NAMDEL ESPORT • SMAN 68 JAKARTA</p><a href="{{ route('login') }}">Portal Admin</a></footer>
    <button class="scroll-top-btn" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Kembali ke atas">&#9650;</button>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" integrity="sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ" crossorigin="anonymous"></script>
    <script src="{{ asset('static/script.js') }}" defer></script>
</body>
</html>
