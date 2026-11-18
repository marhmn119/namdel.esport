<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Admin — NAMDEL ESPORT</title>
    <link rel="stylesheet" href="<?php echo e(asset('static/style.css')); ?>">
    <style>
        body.admin-page{background:#0a0a0a;color:#fff;font-family:Poppins,sans-serif;margin:0}
        .admin-nav{position:sticky;top:0;z-index:10;background:#161616;padding:14px 4%;display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #ff6600}
        .admin-nav h1{font-size:1.3rem;margin:0}.admin-nav span{color:#ff6600}
        .admin-nav a,.admin-button{display:inline-block;background:#ff6600;color:white;text-decoration:none;padding:9px 14px;border:0;border-radius:6px;font-weight:700;cursor:pointer}
        .admin-nav form{display:inline}.admin-nav .logout{background:#7f1d1d}
        .admin-main{max-width:1400px;margin:24px auto;padding:0 18px}
        .admin-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
        .admin-stats article,.admin-section{background:#161b22;border:1px solid #30363d;border-radius:10px;padding:20px;margin-bottom:18px}
        .admin-stats strong{display:block;color:#ffcc00;font-size:1.5rem}.admin-stats span{color:#aaa}
        .admin-section h2{margin:0 0 14px;font-size:1.2rem}.admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
        .admin-grid label{display:flex;flex-direction:column;gap:5px;color:#aaa;font-size:.86rem}
        .admin-grid input,.admin-grid select,.admin-grid textarea{box-sizing:border-box;width:100%;padding:10px;border-radius:5px;border:1px solid #30363d;background:#0d1117;color:#fff;font:inherit}
        .admin-grid textarea{min-height:72px}.admin-grid .wide{grid-column:1/-1}
        .admin-section button{background:#22c55e;color:#fff;border:0;border-radius:5px;padding:9px 14px;font-weight:700;cursor:pointer}
        .admin-table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:620px}th,td{padding:10px;border-bottom:1px solid #30363d;text-align:left;vertical-align:top}th{color:#aaa}
        .admin-table-wrap form{display:inline-block}.admin-table-wrap .danger{background:#7f1d1d}
        .admin-note{color:#aaa}.admin-flash{padding:12px 18px;margin:12px 0;border-radius:7px}.admin-flash.success{background:#14532d}.admin-flash.error{background:#7f1d1d}
        .admin-inline{display:flex;flex-wrap:wrap;gap:7px;align-items:center}.admin-inline input,.admin-inline select{background:#0d1117;color:white;border:1px solid #30363d;padding:7px;border-radius:4px}
        .admin-details{margin-top:8px}.admin-details summary{cursor:pointer;color:#ffcc00}
        .activity-log-table{table-layout:fixed;min-width:700px}.activity-log-table th:nth-child(1){width:160px}.activity-log-table th:nth-child(2){width:120px}.activity-log-table th:nth-child(3){width:250px}
        .activity-log-table td{overflow-wrap:anywhere}.activity-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px;color:#aaa}
        .activity-pagination nav{display:flex;gap:6px;align-items:center}.activity-pagination nav a,.activity-pagination nav span{display:inline-block;padding:6px 10px;border:1px solid #30363d;border-radius:5px;color:#fff;text-decoration:none;background:#0d1117}
        .activity-pagination nav span[aria-current="page"]{background:#ff6600;border-color:#ff6600}.activity-pagination nav span[aria-disabled="true"]{color:#666}
        @media(max-width:600px){.admin-nav{padding:12px;align-items:flex-start;gap:10px;flex-direction:column}.admin-section{padding:14px}.admin-grid{grid-template-columns:1fr}.admin-grid .wide{grid-column:auto}}
    </style>
</head>
<body class="admin-page">
    <nav class="admin-nav"><h1>PORTAL <span>MANAJER</span></h1><div><a href="<?php echo e(route('home')); ?>">Lihat Website</a>
        <form action="<?php echo e(route('logout')); ?>" method="POST"><?php echo csrf_field(); ?><button class="logout" type="submit">Logout</button></form>
    </div></nav>
    <main class="admin-main">
        <?php $__currentLoopData = ['success','error']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(session($type)): ?><div class="admin-flash <?php echo e($type); ?>" role="status"><?php echo e(session($type)); ?></div><?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php if($errors->any()): ?><div class="admin-flash error"><ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>

        <div class="admin-stats">
            <article><span>Total pendaftar</span><strong><?php echo e($totalApplicants); ?></strong></article>
            <article><span>Kunjungan</span><strong><?php echo e($totalViews); ?></strong></article>
            <article><span>Pengunjung unik</span><strong><?php echo e($uniqueVisitors); ?></strong></article>
            <article><span>Pendaftar terbaru</span><strong><?php echo e($latestApplicant ?: '-'); ?></strong></article>
        </div>

        <section class="admin-section" id="matches"><h2>⚔️ Jadwal &amp; Hasil Pertandingan</h2>
            <form action="<?php echo e(route('admin.match.add')); ?>" method="POST" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Judul<input name="title" maxlength="150" required></label>
                <label>Kompetisi<input name="competition" maxlength="150" required></label>
                <label>Game<select name="game" required><option>MLBB</option><option>HOK</option></select></label>
                <label>Tanggal<input type="date" name="date" required></label>
                <label>Jam<input type="time" name="time" value="18:00" required></label>
                <label>Tim Home (boleh kosong saat Upcoming)<input name="team_home" placeholder="Belum ditentukan"></label>
                <label>Tim Away (boleh kosong saat Upcoming)<input name="team_away" placeholder="Belum ditentukan"></label>
                <label>Skor Home<input type="number" name="score_home" min="0" value="0"></label>
                <label>Skor Away<input type="number" name="score_away" min="0" value="0"></label>
                <label>Status<select name="status" required><option value="upcoming">Upcoming</option><option value="finished">Finished</option></select></label>
                <label class="wide">Deskripsi<textarea name="description" maxlength="2000"></textarea></label>
                <div class="wide"><button>Tambah Pertandingan</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Game</th><th>Jadwal</th><th>Match</th><th>Status / Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $matches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr><td><?php echo e($match->game); ?></td><td><?php echo e($match->title); ?><br><?php echo e($match->competition); ?><br><?php echo e($match->date); ?> <?php echo e(substr($match->time ?? '',0,5)); ?></td>
                        <td><?php echo e($match->team_home ?: 'Belum ditentukan'); ?> VS <?php echo e($match->team_away ?: 'Belum ditentukan'); ?><br><?php echo e($match->score_home); ?> - <?php echo e($match->score_away); ?></td>
                        <td><?php echo e(ucfirst($match->status)); ?><br><details class="admin-details"><summary>Edit</summary>
                            <form action="<?php echo e(route('admin.match.edit',$match->id)); ?>" method="POST" class="admin-grid"><?php echo csrf_field(); ?>
                                <label>Judul<input name="title" value="<?php echo e($match->title); ?>" required></label><label>Kompetisi<input name="competition" value="<?php echo e($match->competition); ?>" required></label>
                                <label>Game<select name="game"><option <?php if($match->game==='MLBB'): echo 'selected'; endif; ?>>MLBB</option><option <?php if($match->game==='HOK'): echo 'selected'; endif; ?>>HOK</option></select></label>
                                <label>Tanggal<input type="date" name="date" value="<?php echo e($match->date); ?>" required></label><label>Jam<input type="time" name="time" value="<?php echo e(substr($match->time ?? '',0,5)); ?>" required></label>
                                <label>Home<input name="team_home" value="<?php echo e($match->team_home); ?>"></label><label>Away<input name="team_away" value="<?php echo e($match->team_away); ?>"></label>
                                <label>Skor Home<input type="number" name="score_home" min="0" value="<?php echo e($match->score_home); ?>"></label><label>Skor Away<input type="number" name="score_away" min="0" value="<?php echo e($match->score_away); ?>"></label>
                                <label>Status<select name="status"><option value="upcoming" <?php if($match->status==='upcoming'): echo 'selected'; endif; ?>>Upcoming</option><option value="finished" <?php if($match->status==='finished'): echo 'selected'; endif; ?>>Finished</option></select></label>
                                <label class="wide">Deskripsi<textarea name="description"><?php echo e($match->description); ?></textarea></label><div class="wide"><button>Simpan</button></div>
                            </form>
                        </details>
                        <form action="<?php echo e(route('admin.match.delete',$match->id)); ?>" method="POST" onsubmit="return confirm('Hapus pertandingan ini?')"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4">Belum ada pertandingan.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="roster"><h2>👥 Roster MLBB &amp; HOK</h2>
            <form action="<?php echo e(route('admin.roster.add')); ?>" method="POST" enctype="multipart/form-data" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Game<select name="game"><option>MLBB</option><option>HOK</option></select></label><label>Nama<input name="name" maxlength="100" required></label>
                <label>Role<input name="role" maxlength="60" required></label><label>Tagline<input name="role_tag" maxlength="100"></label>
                <label>Foto<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" required></label>
                <label>Hero andalan (pisahkan koma)<input name="signature_heroes" maxlength="300"></label>
                <label class="wide">Deskripsi<textarea name="description" maxlength="1000"></textarea></label><div class="wide"><button>Tambah ke Roster</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Game</th><th>Nama</th><th>Role</th><th>Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $roster; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $player): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($player->game); ?></td><td><?php echo e($player->name); ?></td><td><?php echo e($player->role); ?></td><td>
                    <details class="admin-details"><summary>Edit</summary><form action="<?php echo e(route('admin.roster.edit',$player->id)); ?>" method="POST" class="admin-inline"><?php echo csrf_field(); ?>
                        <select name="game"><option <?php if($player->game==='MLBB'): echo 'selected'; endif; ?>>MLBB</option><option <?php if($player->game==='HOK'): echo 'selected'; endif; ?>>HOK</option></select>
                        <input name="name" value="<?php echo e($player->name); ?>" required><input name="role" value="<?php echo e($player->role); ?>" required>
                        <input name="role_tag" value="<?php echo e($player->role_tag); ?>"><input name="description" value="<?php echo e($player->description); ?>">
                        <input name="signature_heroes" value="<?php echo e($player->signature_heroes); ?>" placeholder="Hero andalan, pisahkan koma">
                        <input name="photo" value="<?php echo e($player->photo); ?>" required><button>Simpan</button>
                    </form></details>
                    <form action="<?php echo e(route('admin.roster.delete',$player->id)); ?>" method="POST" onsubmit="return confirm('Hapus pemain dari roster?')"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form>
                </td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4">Roster kosong.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="hero-stats"><h2>🎮 Statistik Pick / Ban Hero</h2>
            <form action="<?php echo e(route('admin.hero.add')); ?>" method="POST" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Game<select name="game"><option>MLBB</option><option>HOK</option></select></label><label>Event<input name="event_name" maxlength="120" required></label>
                <label>Hero<input name="hero_name" maxlength="80" required></label><label>Jenis<select name="entry_type"><option value="pick">Pick</option><option value="ban">Ban</option></select></label>
                <label>Hasil Pick<select name="result"><option value="win">Menang</option><option value="loss">Kalah</option></select></label><div class="wide"><button>Simpan Catatan Hero</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Game</th><th>Event</th><th>Hero</th><th>Jenis/Hasil</th><th>Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $heroEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($entry->game); ?></td><td><?php echo e($entry->event_name); ?></td><td><?php echo e($entry->hero_name); ?></td><td><?php echo e(ucfirst($entry->entry_type)); ?> <?php echo e($entry->result ?: ''); ?></td>
                    <td><form action="<?php echo e(route('admin.hero.delete',[$entry->entry_type,$entry->id])); ?>" method="POST"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">Belum ada catatan hero.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="recaps"><h2>🏆 Recap Turnamen</h2>
            <form action="<?php echo e(route('admin.recap.add')); ?>" method="POST" enctype="multipart/form-data" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Game<select name="game"><option>MLBB</option><option>HOK</option></select></label><label>Nama turnamen<input name="event_name" maxlength="120" required></label>
                <label>Tanggal<input type="date" name="event_date" required></label><label>Gambar bracket<input type="file" name="bracket_image" accept=".jpg,.jpeg,.png,.webp"></label>
                <label>Gambar hasil<input type="file" name="result_image" accept=".jpg,.jpeg,.png,.webp"></label>
                <label class="wide">Ringkasan<textarea name="summary" maxlength="2000"></textarea></label><label class="wide">Statistik tim<textarea name="team_stats" maxlength="1000"></textarea></label>
                <div class="wide"><button>Simpan Recap</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Game</th><th>Turnamen</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $recaps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($recap->game); ?></td><td><?php echo e($recap->event_name); ?></td><td><?php echo e($recap->event_date); ?></td><td><form action="<?php echo e(route('admin.recap.delete',$recap->id)); ?>" method="POST" onsubmit="return confirm('Hapus recap?')"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4">Belum ada recap.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="scrim"><h2>🏅 Statistik Scrim Internal</h2>
            <form action="<?php echo e(route('admin.scrim.add')); ?>" method="POST" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Pemain<input name="player_name" maxlength="100" required></label><label>Tim/Kelas<input name="team_name" maxlength="100" required></label>
                <label>Tanggal<input type="date" name="match_date" required></label><label>Poin<input type="number" name="points" min="0" value="0" required></label>
                <label>Kill<input type="number" name="kills" min="0" value="0" required></label><label>Death<input type="number" name="deaths" min="0" value="0" required></label>
                <label>Assist<input type="number" name="assists" min="0" value="0" required></label><label>MVP<select name="is_mvp"><option value="0">Tidak</option><option value="1">Ya</option></select></label>
                <div class="wide"><button>Simpan Statistik</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Pemain</th><th>Tim</th><th>Tanggal</th><th>Poin</th><th>K/D/A</th><th>Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $scrimStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($stat->player_name); ?></td><td><?php echo e($stat->team_name); ?></td><td><?php echo e($stat->match_date); ?></td><td><?php echo e($stat->points); ?></td><td><?php echo e($stat->kills); ?>/<?php echo e($stat->deaths); ?>/<?php echo e($stat->assists); ?></td><td><form action="<?php echo e(route('admin.scrim.delete',$stat->id)); ?>" method="POST"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="6">Belum ada statistik scrim.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="applicants"><h2>📋 Pendaftar Pemain</h2><p><a class="admin-button" href="<?php echo e(route('admin.export')); ?>">Export CSV</a></p>
            <div class="admin-table-wrap"><table><thead><tr><th>Nama</th><th>Role</th><th>WhatsApp</th><th>Bukti</th><th>Status/Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $applicants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $applicant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($applicant->nama); ?></td><td><?php echo e($applicant->role); ?></td><td><a href="https://wa.me/<?php echo e($applicant->whatsapp); ?>" rel="noopener"><?php echo e($applicant->whatsapp); ?></a></td>
                    <td><?php if($applicant->foto): ?><a href="<?php echo e(asset('static/uploads/'.basename($applicant->foto))); ?>" target="_blank" rel="noopener">Lihat foto</a><?php else: ?>—<?php endif; ?></td>
                    <td><form action="<?php echo e(route('admin.status',$applicant->id)); ?>" method="POST" class="admin-inline"><?php echo csrf_field(); ?><select name="status"><?php $__currentLoopData = ['pending','review','accepted','rejected']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($status); ?>" <?php if($applicant->status===$status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><button>Simpan</button></form>
                        <form action="<?php echo e(route('admin.applicants.delete',$applicant->id)); ?>" method="POST" onsubmit="return confirm('Hapus pendaftar?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="danger">Hapus</button></form></td>
                </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">Belum ada pendaftar.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="achievements"><h2>🏆 Prestasi</h2>
            <form action="<?php echo e(route('admin.achievement.add')); ?>" method="POST" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Prestasi<input name="title" maxlength="150" required></label><label>Event<input name="event" maxlength="150" required></label>
                <label>Tahun<input name="year" inputmode="numeric" pattern="[0-9]{4}" required></label><label>Ikon<input name="icon" maxlength="16" value="🏆"></label><div class="wide"><button>Tambah Prestasi</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Prestasi</th><th>Event</th><th>Tahun</th><th>Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $achievements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($item->title); ?></td><td><?php echo e($item->event); ?></td><td><?php echo e($item->year); ?></td><td><form action="<?php echo e(route('admin.achievement.delete',$item->id)); ?>" method="POST"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4">Belum ada prestasi.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="gallery"><h2>📷 Galeri</h2>
            <form action="<?php echo e(route('admin.gallery.add')); ?>" method="POST" class="admin-grid"><?php echo csrf_field(); ?>
                <label>Judul<input name="title" maxlength="150" required></label><label>Nama file gambar di static<input name="image" placeholder="dokumentasi.png" required></label>
                <label class="wide">Deskripsi<textarea name="description" maxlength="500"></textarea></label><div class="wide"><button>Tambah Galeri</button></div>
            </form>
            <div class="admin-table-wrap"><table><thead><tr><th>Judul</th><th>File</th><th>Aksi</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $gallery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($item->title); ?></td><td><?php echo e($item->image); ?></td><td><form action="<?php echo e(route('admin.gallery.delete',$item->id)); ?>" method="POST"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="3">Galeri kosong.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="admin-section" id="tickers"><h2>📢 Pengumuman berjalan</h2>
            <form action="<?php echo e(route('admin.ticker.add')); ?>" method="POST" class="admin-inline"><?php echo csrf_field(); ?><input name="content" maxlength="500" required placeholder="Teks pengumuman"><button>Tambah</button></form>
            <div class="admin-table-wrap"><table><tbody><?php $__empty_1 = true; $__currentLoopData = $tickers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticker): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($ticker->content); ?></td><td><form action="<?php echo e(route('admin.ticker.delete',$ticker->id)); ?>" method="POST"><?php echo csrf_field(); ?><button class="danger">Hapus</button></form></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td>Belum ada pengumuman.</td></tr><?php endif; ?></tbody></table></div>
        </section>

        <section class="admin-section" id="settings"><h2>📺 Pengaturan Situs</h2>
            <form action="<?php echo e(route('admin.settings.update')); ?>" method="POST" class="admin-inline"><?php echo csrf_field(); ?>
                <input type="hidden" name="key" value="highlight_video_url"><label>URL embed video<input type="url" name="value" value="<?php echo e($siteSettings['highlight_video_url'] ?? ''); ?>" required></label><button>Simpan</button>
            </form>
        </section>

        <section class="admin-section"><h2>🤝 Inquiry Sponsor</h2><div class="admin-table-wrap"><table><thead><tr><th>Perusahaan</th><th>PIC</th><th>Email</th><th>Paket</th><th>Waktu</th></tr></thead><tbody>
            <?php $__empty_1 = true; $__currentLoopData = $sponsors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sponsor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($sponsor->company); ?></td><td><?php echo e($sponsor->contact_person); ?></td><td><?php echo e($sponsor->email); ?></td><td><?php echo e(ucfirst($sponsor->tier)); ?></td><td><?php echo e($sponsor->created_at); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">Belum ada inquiry sponsor.</td></tr><?php endif; ?>
        </tbody></table></div></section>

        <section class="admin-section"><h2>📈 Halaman Populer</h2><div class="admin-table-wrap"><table><thead><tr><th>Halaman</th><th>Kunjungan</th></tr></thead><tbody><?php $__currentLoopData = $popularPages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($page->path); ?></td><td><?php echo e($page->views); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div></section>
        <section class="admin-section" id="activity-logs">
            <h2>🕵️ Log Aktivitas Admin</h2>
            <p class="admin-note">5 aktivitas terbaru ditampilkan; gunakan halaman berikutnya untuk melihat riwayat lama.</p>
            <div class="admin-table-wrap"><table class="activity-log-table"><thead><tr><th>Waktu (WIB)</th><th>Admin</th><th>Aktivitas</th><th>Rincian</th></tr></thead><tbody>
                <?php $__empty_1 = true; $__currentLoopData = $activityLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e(\Illuminate\Support\Carbon::parse($entry->created_at)->timezone('Asia/Jakarta')->format('d M Y, H:i')); ?></td>
                        <td><?php echo e($entry->actor); ?></td>
                        <td><?php echo e($entry->action_label); ?></td>
                        <td><?php echo e($entry->target ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4">Belum ada aktivitas admin.</td></tr>
                <?php endif; ?>
            </tbody></table></div>
            <div class="activity-pagination">
                <span>Menampilkan <?php echo e($activityLogs->firstItem() ?? 0); ?>–<?php echo e($activityLogs->lastItem() ?? 0); ?> dari <?php echo e($activityLogs->total()); ?> aktivitas</span>
                <?php echo e($activityLogs->links()); ?>

            </div>
        </section>
    </main>
</body>
</html>
<?php /**PATH C:\Users\aimar\OneDrive\Dokumen\webb esport mlbb sma68\laravel\resources\views\admin.blade.php ENDPATH**/ ?>