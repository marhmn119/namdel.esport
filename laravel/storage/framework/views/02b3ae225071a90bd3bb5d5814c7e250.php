<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff6600">
    <title>NAMDEL ESPORT - SMAN 68</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('static/logonest.png')); ?>">
    <link rel="manifest" href="<?php echo e(asset('static/manifest.json')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('static/style.css')); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <meta name="description" content="Website resmi NAMDEL ESPORT, tim esports SMAN 68 Jakarta. Jadwal, roster MLBB dan HOK, prestasi, dan rekrutmen.">
    <meta property="og:title" content="NAMDEL ESPORT - SMAN 68">
    <meta property="og:image" content="<?php echo e(asset('static/logonest.png')); ?>">
</head>
<body>
    <?php if(session('success') || session('error')): ?>
        <div class="flash-container" role="status">
            <?php $__currentLoopData = ['success', 'error']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(session($type)): ?>
                    <div class="flash flash-<?php echo e($type); ?>"><?php echo e(session($type)); ?></div>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <nav>
        <div class="logo-container">
            <img class="nav-logo nav-logo-school" src="<?php echo e(asset('static/logo68.png')); ?>" alt="SMAN 68" width="70" height="70">
            <img class="nav-logo nav-logo-team" src="<?php echo e(asset('static/logonest.png')); ?>" alt="NEST" width="70" height="70">
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
        <div class="mega-menu-column"><a href="#faq">FAQ</a><a href="#merchandise">Merchandise</a><a href="#partnership">Sponsor</a><a href="#kontak">Kontak</a><a href="<?php echo e(route('login')); ?>">Admin</a></div>
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

    <?php if($tickers->isNotEmpty()): ?>
        <div class="ticker-section" aria-label="Pengumuman">
            <?php $__currentLoopData = $tickers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticker): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span><?php echo e($ticker->content); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

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
                <?php $__currentLoopData = ['MLBB', 'HOK']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="match-game-column">
                        <div class="match-header"><span><?php echo e($game); ?> • PERTANDINGAN TERBARU</span><span>WIB / UTC+7</span></div>
                        <div class="match-game-panel" id="match-panel-<?php echo e(strtolower($game)); ?>" role="region" aria-label="Jadwal <?php echo e($game); ?>">
                            <?php $__empty_1 = true; $__currentLoopData = $matches->where('game', $game); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <article class="match-card" role="group" tabindex="0"
                                    data-id="<?php echo e($match->id); ?>" data-title="<?php echo e($match->title); ?>" data-competition="<?php echo e($match->competition); ?>"
                                    data-home="<?php echo e($match->team_home); ?>" data-away="<?php echo e($match->team_away); ?>"
                                    data-description="<?php echo e($match->description); ?>" data-status="<?php echo e($match->status); ?>"
                                    data-score="<?php echo e($match->score_home); ?> - <?php echo e($match->score_away); ?>"
                                    data-home-percent="<?php echo e($match->home_percent); ?>" data-away-percent="<?php echo e($match->away_percent); ?>"
                                    data-targetdate="<?php echo e($match->date); ?>T<?php echo e(substr($match->time ?: '00:00:00', 0, 8)); ?>+07:00">
                                    <div class="match-date"><?php echo e(\Illuminate\Support\Carbon::parse($match->date)->locale('id')->translatedFormat('d M Y')); ?></div>
                                    <div class="match-info">
                                        <p class="league"><?php echo e($match->competition); ?></p><h3><?php echo e($match->title); ?></h3>
                                        <?php if($match->team_home || $match->team_away): ?>
                                            <p><?php echo e($match->team_home ?: 'Tim belum ditentukan'); ?> <span>VS</span> <?php echo e($match->team_away ?: 'Tim belum ditentukan'); ?></p>
                                        <?php else: ?>
                                            <p>Tim pertandingan akan diumumkan.</p>
                                        <?php endif; ?>
                                        <?php if($match->status === 'finished'): ?>
                                            <p class="match-score"><?php echo e($match->team_home); ?> <?php echo e($match->score_home); ?> - <?php echo e($match->score_away); ?> <?php echo e($match->team_away); ?></p>
                                        <?php else: ?>
                                            <p class="match-timer-text">⏱️ <span class="countdown-clock">Menghitung waktu...</span></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="match-time"><?php echo e(substr($match->time ?: '', 0, 5)); ?> WIB</div>
                                    <?php if($match->status === 'upcoming' && $match->team_home && $match->team_away): ?>
                                        <form action="<?php echo e(route('vote', $match->id)); ?>" method="POST" class="match-vote-form">
                                            <?php echo csrf_field(); ?>
                                            <span>Prediksi pemenang:</span>
                                            <button type="submit" name="winner" value="<?php echo e($match->team_home); ?>"><?php echo e($match->team_home); ?></button>
                                            <button type="submit" name="winner" value="<?php echo e($match->team_away); ?>"><?php echo e($match->team_away); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="schedule-empty-state"><div class="empty-state-icon">📅</div><h3>Belum Ada Jadwal <?php echo e($game); ?></h3><p>Jadwal pertandingan <?php echo e($game); ?> akan segera diumumkan.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                <form id="predictionForm" method="POST"><?php echo csrf_field(); ?>
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
                <iframe src="<?php echo e($highlightVideoUrl); ?>" title="Rekaman pertandingan esports" frameborder="0" allowfullscreen loading="lazy"></iframe>
            </div></div></div>
        </div>
    </section>

    <section id="statistik" class="stats-section">
        <h2 class="center-title" style="text-align:center;margin-bottom:50px;color:white">STATISTIK TIM</h2>
        <div class="game-team-stats-grid">
            <?php $__currentLoopData = ['MLBB', 'HOK']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($stats = $teamStatistics[$game]); ?>
                <article class="game-team-stats-card"><p class="game-stats-eyebrow">STATISTIK TIM</p><h3><?php echo e($game); ?></h3>
                    <div class="team-stat-numbers">
                        <div><strong><?php echo e($stats['matches']); ?></strong><span>Match</span></div>
                        <div><strong><?php echo e($stats['wins']); ?></strong><span>Menang</span></div>
                        <div><strong><?php echo e($stats['losses']); ?></strong><span>Kalah</span></div>
                        <div><strong><?php echo e($stats['winrate']); ?>%</strong><span>Win rate</span></div>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    <section id="recap-turnamen" class="tournament-recaps-section">
        <div class="recap-section-heading"><p class="section-subtitle">TOURNAMENT ARCHIVE</p><h2 class="center-title">RECAP <span>TURNAMEN</span></h2><p>Bracket, hasil pertandingan, dan performa tim di setiap turnamen.</p></div>
        <?php $__currentLoopData = ['MLBB', 'HOK']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="recap-game-group"><h3><?php echo e($game); ?> <span>RECAP</span></h3><div class="recap-card-grid">
                <?php $__empty_1 = true; $__currentLoopData = $recaps->where('game', $game); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="recap-card"><div class="recap-card-heading"><div><span><?php echo e(\Illuminate\Support\Carbon::parse($recap->event_date)->locale('id')->translatedFormat('d M Y')); ?></span><h4><?php echo e($recap->event_name); ?></h4></div><strong><?php echo e($game); ?></strong></div>
                        <?php if($recap->summary): ?><p class="recap-summary"><?php echo e($recap->summary); ?></p><?php endif; ?>
                        <div class="recap-images">
                            <?php if($recap->bracket_image): ?><a href="<?php echo e(asset('static/uploads/'.$recap->bracket_image)); ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?php echo e(asset('static/uploads/'.$recap->bracket_image)); ?>" alt="Bracket <?php echo e($recap->event_name); ?>"><span>Bracket</span></a><?php endif; ?>
                            <?php if($recap->result_image): ?><a href="<?php echo e(asset('static/uploads/'.$recap->result_image)); ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?php echo e(asset('static/uploads/'.$recap->result_image)); ?>" alt="Hasil <?php echo e($recap->event_name); ?>"><span>Hasil Pertandingan</span></a><?php endif; ?>
                        </div>
                        <?php if($recap->team_stats): ?><div class="recap-stat-note"><strong>Statistik Tim</strong><p><?php echo e($recap->team_stats); ?></p></div><?php endif; ?>
                        <?php if($recap->hero_stats->isNotEmpty()): ?><div class="recap-hero-stats"><strong>Hero turnamen</strong><ul><?php $__currentLoopData = $recap->hero_stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hero): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($hero->hero_name); ?> <span>Pick <?php echo e($hero->picks); ?> · Ban <?php echo e($hero->bans); ?> · Win <?php echo e($hero->wins); ?></span></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="recap-empty-state">Recap turnamen <?php echo e($game); ?> belum tersedia.</p>
                <?php endif; ?>
            </div></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>

    <section id="roster" class="roster-section roster-section-styled"><div class="roster-container">
        <?php $__currentLoopData = ['MLBB', 'HOK']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="team-roster-group" style="margin-bottom:50px"><div class="roster-heading"><div><p class="roster-eyebrow">NEST • TIM UTAMA</p><h2 class="roster-title">ROSTER <span><?php echo e($game); ?></span></h2></div></div>
                <div class="roster-grid" id="roster-lineup-<?php echo e(strtolower($game)); ?>">
                    <?php $__empty_1 = true; $__currentLoopData = $rosterByGame->get($game, collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $player): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="card clickable-card roster-card" role="button" tabindex="0" data-profile-id="<?php echo e($player->role_id); ?>" aria-label="Lihat profil <?php echo e($player->name); ?>, <?php echo e($player->role); ?>"><img loading="lazy" src="<?php echo e(asset('static/'.$player->photo)); ?>" alt="<?php echo e($player->name); ?>, <?php echo e($player->role); ?>" width="375" height="666" class="roster-card-img"><div class="roster-card-info"><h3 class="roster-card-name"><?php echo e($player->name); ?></h3><p class="roster-card-role"><?php echo e($player->role); ?></p></div></article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="recap-empty-state">Roster <?php echo e($game); ?> akan segera diumumkan.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div></section>

    <div id="playerProfileModal" class="modal player-profile-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Profil pemain" onclick="if(event.target === this) closePlayerModal()">
        <div class="modal-content player-profile-modal-content">
            <button type="button" onclick="closePlayerModal()" class="close-btn" aria-label="Tutup profil pemain">&times;</button>
            <?php $__currentLoopData = $roster; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $player): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="profile-slide-item" id="profile-<?php echo e($player->role_id); ?>" style="display:none">
                    <div class="player-profile-summary">
                        <div class="player-profile-copy">
                            <h2 class="player-name"><?php echo e($player->name); ?></h2>
                            <div class="player-role-tag"><?php echo e($player->role_tag); ?></div>
                            <p class="player-description"><?php echo e($player->description); ?></p>
                            <?php if($player->signature_heroes): ?>
                                <div class="player-signature" style="margin-top:20px">
                                    <strong style="color:#ffcc00;display:block;margin-bottom:10px">Signature Heroes:</strong>
                                    <div class="hero-showcase">
                                        <?php $__currentLoopData = $player->signature_heroes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hero): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="hero-item">
                                                <?php if(!empty($hero['img'])): ?><img src="<?php echo e(asset('static/'.$hero['img'])); ?>" alt="<?php echo e($hero['name'] ?? 'Hero'); ?>" class="hero-img"><?php endif; ?>
                                                <div class="hero-stats"><span class="hero-name"><?php echo e($hero['name'] ?? ''); ?></span><?php if(!empty($hero['wr'])): ?><span class="hero-wr"><?php echo e($hero['wr']); ?></span><?php endif; ?></div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <img loading="lazy" class="player-portrait" src="<?php echo e(asset('static/'.$player->photo)); ?>" alt="<?php echo e($player->name); ?>" width="375" height="666">
                    </div>
                    <div class="player-stat-panel"><h3>Statistik Gaya Main</h3><div class="player-chart-wrap"><canvas id="radar-<?php echo e($player->role_id); ?>" data-stats="<?php echo e($player->stats); ?>"></canvas></div></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <section id="galeri" class="gallery-section gallery-section-styled"><h2 class="center-title">DOKUMENTASI <span>KEGIATAN</span></h2><div class="gallery-container gallery-container-styled"><div class="gallery-track gallery-track-styled">
        <?php $__currentLoopData = $gallery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><article class="gallery-item gallery-item-styled" role="button" tabindex="0" data-image="<?php echo e(asset('static/'.$item->image)); ?>" data-caption="<?php echo e($item->title); ?>" aria-label="Buka gambar <?php echo e($item->title); ?>"><img loading="lazy" src="<?php echo e(asset('static/'.$item->image)); ?>" alt="<?php echo e($item->title); ?>" class="gallery-item-img"><p class="gallery-item-caption"><?php echo e($item->title); ?></p></article><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
        <?php $__empty_1 = true; $__currentLoopData = $achievements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $achievement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><article class="achievement-card"><span><?php echo e($achievement->icon); ?></span><h3><?php echo e($achievement->title); ?></h3><p><?php echo e($achievement->event); ?> • <?php echo e($achievement->year); ?></p></article><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p>Prestasi akan segera diumumkan.</p><?php endif; ?>
    </div></section>

    <section id="join" class="join-section dark-section"><div class="join-container"><p class="section-subtitle">OPEN RECRUITMENT</p><h2>GABUNG <span>NAMDEL ESPORT</span></h2><p>Tunjukkan kemampuan dan jadilah bagian dari tim esports SMAN 68 Jakarta.</p>
        <form action="<?php echo e(route('register')); ?>" method="POST" enctype="multipart/form-data" class="join-form">
            <?php echo csrf_field(); ?>
            <label for="name">Nama lengkap</label><input id="name" name="nama" maxlength="100" value="<?php echo e(old('nama')); ?>" required>
            <label for="role">Role</label><select id="role" name="role" required><option value="">Pilih role</option><?php $__currentLoopData = ['Jungler','Roamer','Mid Laner','Gold Laner','Exp Laner','Coach/Manager']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($role); ?>" <?php if(old('role') === $role): echo 'selected'; endif; ?>><?php echo e($role); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
            <label for="contact">WhatsApp</label><input id="contact" name="whatsapp" placeholder="08xx atau 628xx" value="<?php echo e(old('whatsapp')); ?>" required>
            <label for="foto">Bukti rank (JPG/PNG/WEBP, maks. 5 MB)</label><input id="foto" type="file" name="foto_rank" accept=".jpg,.jpeg,.png,.webp" required>
            <?php if($errors->any()): ?><ul class="form-errors"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul><?php endif; ?>
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
        <?php $__currentLoopData = [['logo' => 'logonest.png', 'name' => 'JERSEY TIM E-SPORT 2026', 'price' => 'Rp 150.000', 'message' => 'Jersey%20Tim%20E-Sport'], ['logo' => 'logo68.png', 'name' => 'LANYARD EKSKLUSIF FAMS', 'price' => 'Rp 35.000', 'message' => 'Lanyard%20Eksklusif'], ['logo' => 'logonest.png', 'name' => 'T-SHIRT SUPPORTER 68', 'price' => 'Rp 100.000', 'message' => 'T-Shirt%20Supporter']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="merch-card merch-card-styled"><img loading="lazy" src="<?php echo e(asset('static/'.$item['logo'])); ?>" alt="<?php echo e($item['name']); ?>" class="merch-card-img"><div class="merch-card-body"><h3 class="merch-card-title"><?php echo e($item['name']); ?></h3><p class="merch-card-price"><?php echo e($item['price']); ?></p><a href="https://wa.me/<?php echo e($whatsappNumber); ?>?text=Halo%20Admin,%20saya%20ingin%20memesan%20<?php echo e($item['message']); ?>" target="_blank" rel="noopener noreferrer" class="merch-card-wa-btn">Pesan via WhatsApp ↗</a></div></article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div></section>

    <section id="partnership" class="partner-section"><h2 class="center-title">MITRA <span>RESMI</span></h2><p class="partner-description">Terima kasih kepada sekolah dan mitra yang mendukung perjalanan NAMDEL ESPORT.</p><div class="partner-grid">
        <?php $__currentLoopData = ['logo68.png' => 'SMAN 68 Jakarta', 'logonest.png' => 'NEST Esport', 'realme.png' => 'Realme']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logo => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="partner-logo"><img loading="lazy" src="<?php echo e(asset('static/'.$logo)); ?>" alt="<?php echo e($name); ?>"></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div><a class="partner-placeholder" href="https://wa.me/<?php echo e($whatsappNumber); ?>?text=Halo%20Admin%2C%20saya%20tertarik%20menjadi%20sponsor%20NAMDEL%20ESPORT." target="_blank" rel="noopener noreferrer">Jadilah sponsor kami via WhatsApp ↗</a>
        <div class="partner-container"><h2>INQUIRY <span>SPONSOR</span></h2><p>Tertarik mendukung NAMDEL ESPORT?</p>
            <form action="<?php echo e(route('sponsorship')); ?>" method="POST" class="partner-form"><?php echo csrf_field(); ?>
                <label>Perusahaan<input name="company" maxlength="150" required></label><label>Nama PIC<input name="contact_person" maxlength="100" required></label><label>Email<input type="email" name="email" maxlength="254" required></label>
                <label>Paket sponsor<select name="tier" required><?php $__currentLoopData = $sponsorTiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($tier); ?>"><?php echo e(ucfirst($tier)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label><button type="submit" class="save-btn">Kirim inquiry</button>
            </form>
        </div>
    </section>
    <footer id="kontak" class="footer-section footer-section-styled"><h2>HUBUNGI <span>KAMI</span></h2><p style="color:var(--text-gray);margin-bottom:25px">Ada pertanyaan atau tawaran kerja sama? Hubungi Humas kami.</p><a href="https://wa.me/<?php echo e($whatsappNumber); ?>" target="_blank" rel="noopener noreferrer" class="btn-wa">Chat Manajer ↗</a><p>NAMDEL ESPORT • SMAN 68 JAKARTA</p><a href="<?php echo e(route('login')); ?>">Portal Admin</a></footer>
    <button class="scroll-top-btn" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Kembali ke atas">&#9650;</button>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" integrity="sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ" crossorigin="anonymous"></script>
    <script src="<?php echo e(asset('static/script.js')); ?>" defer></script>
</body>
</html>
<?php /**PATH C:\Users\aimar\OneDrive\Dokumen\webb esport mlbb sma68\laravel\resources\views\home.blade.php ENDPATH**/ ?>