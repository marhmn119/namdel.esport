<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function index(): View
    {
        $roster = DB::table('roster')->orderBy('game')->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (object $player): object {
                $heroes = json_decode($player->signature_heroes ?: '[]', true);
                $player->signature_heroes = collect(is_array($heroes) ? $heroes : [])
                    ->map(fn ($hero): string => is_array($hero) ? (string) ($hero['name'] ?? '') : (string) $hero)
                    ->filter()->implode(', ');

                return $player;
            });

        $activityLogs = DB::table('activity_logs')->orderByDesc('id')->paginate(5, ['*'], 'activity_page')
            ->through(function (object $entry): object {
                $entry->action_label = match ($entry->action) {
                    'login' => 'Login admin',
                    'logout' => 'Logout admin',
                    'add_match' => 'Menambahkan jadwal pertandingan',
                    'edit_match' => 'Mengubah jadwal pertandingan',
                    'delete_match' => 'Menghapus pertandingan',
                    'add_roster' => 'Menambahkan pemain roster',
                    'edit_roster' => 'Mengubah data pemain roster',
                    'delete_roster' => 'Menghapus pemain roster',
                    'add_hero_match_stat' => 'Menambahkan statistik hero',
                    'delete_hero_match_stat' => 'Menghapus statistik hero',
                    'add_tournament_recap' => 'Menambahkan recap turnamen',
                    'delete_tournament_recap' => 'Menghapus recap turnamen',
                    'add_scrim_stat' => 'Menambahkan statistik scrim',
                    'delete_scrim_stat' => 'Menghapus statistik scrim',
                    'add_achievement' => 'Menambahkan prestasi',
                    'delete_achievement' => 'Menghapus prestasi',
                    'add_gallery' => 'Menambahkan foto galeri',
                    'delete_gallery' => 'Menghapus foto galeri',
                    'add_ticker' => 'Menambahkan pengumuman',
                    'delete_ticker' => 'Menghapus pengumuman',
                    'update_setting' => 'Mengubah pengaturan situs',
                    'update_applicant_status' => 'Mengubah status pendaftar',
                    'delete_applicant' => 'Menghapus pendaftar',
                    default => str($entry->action)->replace('_', ' ')->title()->toString(),
                };

                if (in_array($entry->action, ['login', 'logout'], true)) {
                    $entry->target = '';
                }

                return $entry;
            });

        $heroEntries = DB::table('hero_match_stats')->select('id', 'game', 'event_name', 'hero_name', 'result', 'created_at')
            ->selectRaw("'pick' AS entry_type")->unionAll(
                DB::table('hero_ban_stats')->select('id', 'game', 'event_name', 'hero_name')
                    ->selectRaw('NULL AS result, created_at')->selectRaw("'ban' AS entry_type")
            )->orderByDesc('created_at')->limit(200)->get();

        return view('admin', [
            'applicants' => DB::table('pendaftar')->orderByDesc('id')->get(),
            'matches' => DB::table('matches')->orderByDesc('date')->orderByDesc('time')->get(),
            'roster' => $roster,
            'heroEntries' => $heroEntries,
            'recaps' => DB::table('tournament_recaps')->orderByDesc('event_date')->orderByDesc('id')->get(),
            'scrimStats' => DB::table('scrim_stats')->orderByDesc('match_date')->orderByDesc('id')->limit(100)->get(),
            'achievements' => DB::table('achievements')->orderByDesc('year')->orderByDesc('id')->get(),
            'gallery' => DB::table('gallery')->orderByDesc('id')->get(),
            'tickers' => DB::table('tickers')->orderBy('id')->get(),
            'sponsors' => DB::table('sponsorship_inquiries')->orderByDesc('id')->limit(100)->get(),
            'activityLogs' => $activityLogs,
            'totalViews' => DB::table('page_views')->count(),
            'uniqueVisitors' => DB::table('page_views')->distinct('visitor_hash')->count('visitor_hash'),
            'popularPages' => DB::table('page_views')->select('path')->selectRaw('COUNT(*) AS views')
                ->groupBy('path')->orderByDesc('views')->limit(10)->get(),
            'siteSettings' => DB::table('site_settings')->pluck('value', 'key'),
            'totalApplicants' => DB::table('pendaftar')->count(),
            'latestApplicant' => DB::table('pendaftar')->orderByDesc('id')->value('nama'),
            'heroUsageEvents' => DB::table('hero_match_stats')->select('event_name')
                ->union(DB::table('hero_ban_stats')->select('event_name'))->orderBy('event_name')->pluck('event_name')->unique()->values(),
        ]);
    }

    public function addMatch(Request $request): RedirectResponse
    {
        $data = $this->validateMatch($request);
        DB::table('matches')->insert($data);
        $this->log('add_match', $data['title']);

        return back()->with('success', 'Jadwal pertandingan berhasil ditambahkan.');
    }

    public function editMatch(Request $request, int $id): RedirectResponse
    {
        $data = $this->validateMatch($request);
        DB::table('matches')->where('id', $id)->update($data);
        $this->log('edit_match', $id.': '.$data['title']);

        return back()->with('success', 'Jadwal pertandingan berhasil diperbarui.');
    }

    public function deleteMatch(int $id): RedirectResponse
    {
        $deleted = DB::table('matches')->where('id', $id)->delete();
        if ($deleted) {
            DB::table('match_votes')->where('match_id', $id)->delete();
            $this->log('delete_match', $id);
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Pertandingan berhasil dihapus.' : 'Pertandingan tidak ditemukan.');
    }

    public function addRoster(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'game' => ['required', 'in:MLBB,HOK'],
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'string', 'max:60'],
            'role_tag' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'signature_heroes' => ['nullable', 'string', 'max:300'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $filename = $this->storeImage($request, 'photo');
        $name = trim($data['name']);
        DB::table('roster')->insert([
            'game' => $data['game'],
            'name' => $name,
            'role' => trim($data['role']),
            'role_id' => strtolower($data['game'].'-'.preg_replace('/[^a-z0-9]+/i', '-', $name).'-'.bin2hex(random_bytes(4))),
            'role_tag' => trim($data['role_tag'] ?? '') ?: $data['role'],
            'description' => trim($data['description'] ?? ''),
            'photo' => 'uploads/'.$filename,
            'stats' => '50,50,50,50,50',
            'signature_heroes' => json_encode(collect(explode(',', $data['signature_heroes'] ?? ''))
                ->map(fn (string $hero): string => trim($hero))->filter()->map(fn (string $hero): array => [
                    'name' => $hero, 'wr' => '', 'img' => '',
                ])->values(), JSON_THROW_ON_ERROR),
            'sort_order' => ((int) DB::table('roster')->where('game', $data['game'])->max('sort_order')) + 1,
        ]);
        $this->log('add_roster', $data['game'].': '.$name);

        return back()->with('success', 'Pemain berhasil ditambahkan ke roster.');
    }

    public function editRoster(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'game' => ['required', 'in:MLBB,HOK'],
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'string', 'max:60'],
            'role_tag' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'signature_heroes' => ['nullable', 'string', 'max:300'],
            'photo' => ['required', 'string', 'max:255', 'regex:/^(?:uploads\\/)?[A-Za-z0-9._-]+$/'],
        ]);
        DB::table('roster')->where('id', $id)->update([
            'game' => $data['game'], 'name' => trim($data['name']), 'role' => trim($data['role']),
            'role_tag' => trim($data['role_tag'] ?? '') ?: trim($data['role']),
            'description' => trim($data['description'] ?? ''),
            'photo' => $data['photo'],
            'signature_heroes' => json_encode(collect(explode(',', $data['signature_heroes'] ?? ''))
                ->map(fn (string $hero): string => trim($hero))->filter()->map(fn (string $hero): array => [
                    'name' => $hero, 'wr' => '', 'img' => '',
                ])->values(), JSON_THROW_ON_ERROR),
        ]);
        $this->log('edit_roster', $id.': '.$data['name']);

        return back()->with('success', 'Data roster berhasil diperbarui.');
    }

    public function deleteRoster(int $id): RedirectResponse
    {
        $player = DB::table('roster')->where('id', $id)->first();
        if (! $player) {
            return back()->with('error', 'Pemain roster tidak ditemukan.');
        }
        DB::table('roster')->where('id', $id)->delete();
        $this->deleteUpload($player->photo);
        $this->log('delete_roster', $id.': '.$player->game.' - '.$player->name);

        return back()->with('success', 'Pemain berhasil dihapus dari roster.');
    }

    public function addHeroStat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'game' => ['required', 'in:MLBB,HOK'],
            'event_name' => ['required', 'string', 'max:120'],
            'hero_name' => ['required', 'string', 'max:80'],
            'entry_type' => ['required', 'in:pick,ban'],
            'result' => ['required_if:entry_type,pick', 'nullable', 'in:win,loss'],
        ]);
        if ($data['entry_type'] === 'pick') {
            DB::table('hero_match_stats')->insert([
                'game' => $data['game'], 'event_name' => trim($data['event_name']),
                'hero_name' => trim($data['hero_name']), 'result' => $data['result'],
                'created_at' => now()->toDateTimeString(),
            ]);
        } else {
            DB::table('hero_ban_stats')->insert([
                'game' => $data['game'], 'event_name' => trim($data['event_name']),
                'hero_name' => trim($data['hero_name']), 'created_at' => now()->toDateTimeString(),
            ]);
        }
        $this->log('add_hero_match_stat', $data['event_name'].': '.$data['hero_name'].' ('.$data['entry_type'].')');

        return back()->with('success', 'Catatan hero berhasil disimpan.');
    }

    public function deleteHeroStat(string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['pick', 'ban'], true), 404);
        $table = $type === 'pick' ? 'hero_match_stats' : 'hero_ban_stats';
        $deleted = DB::table($table)->where('id', $id)->delete();
        if ($deleted) {
            $this->log('delete_hero_match_stat', $id);
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Catatan hero berhasil dihapus.' : 'Catatan hero tidak ditemukan.');
    }

    public function addRecap(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'game' => ['required', 'in:MLBB,HOK'],
            'event_name' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'team_stats' => ['nullable', 'string', 'max:1000'],
            'bracket_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'result_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if (! $request->hasFile('bracket_image') && ! $request->hasFile('result_image')) {
            return back()->withErrors(['bracket_image' => 'Unggah gambar bracket dan/atau hasil pertandingan.'])->withInput();
        }
        DB::table('tournament_recaps')->insert([
            'game' => $data['game'], 'event_name' => trim($data['event_name']),
            'event_date' => $data['event_date'], 'summary' => trim($data['summary'] ?? ''),
            'team_stats' => trim($data['team_stats'] ?? ''),
            'bracket_image' => $request->hasFile('bracket_image') ? $this->storeImage($request, 'bracket_image') : null,
            'result_image' => $request->hasFile('result_image') ? $this->storeImage($request, 'result_image') : null,
            'created_at' => now()->toDateTimeString(),
        ]);
        $this->log('add_tournament_recap', $data['game'].': '.$data['event_name']);

        return back()->with('success', 'Recap turnamen berhasil ditambahkan.');
    }

    public function deleteRecap(int $id): RedirectResponse
    {
        $recap = DB::table('tournament_recaps')->where('id', $id)->first();
        if (! $recap) {
            return back()->with('error', 'Recap turnamen tidak ditemukan.');
        }
        DB::table('tournament_recaps')->where('id', $id)->delete();
        $this->deleteUpload($recap->bracket_image, true);
        $this->deleteUpload($recap->result_image, true);
        $this->log('delete_tournament_recap', $id.': '.$recap->event_name);

        return back()->with('success', 'Recap turnamen berhasil dihapus.');
    }

    public function addScrim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'player_name' => ['required', 'string', 'max:100'],
            'team_name' => ['required', 'string', 'max:100'],
            'match_date' => ['required', 'date'],
            'points' => ['required', 'integer', 'min:0', 'max:9999'],
            'kills' => ['required', 'integer', 'min:0', 'max:9999'],
            'deaths' => ['required', 'integer', 'min:0', 'max:9999'],
            'assists' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_mvp' => ['nullable', 'boolean'],
        ]);
        DB::table('scrim_stats')->insert($data + ['is_mvp' => $request->boolean('is_mvp')]);
        $this->log('add_scrim_stat', $data['player_name']);

        return back()->with('success', 'Statistik scrim berhasil disimpan.');
    }

    public function deleteScrim(int $id): RedirectResponse
    {
        $deleted = DB::table('scrim_stats')->where('id', $id)->delete();
        if ($deleted) {
            $this->log('delete_scrim_stat', $id);
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Data statistik scrim berhasil dihapus.' : 'Data statistik scrim tidak ditemukan.');
    }

    public function addAchievement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'], 'event' => ['required', 'string', 'max:150'],
            'year' => ['required', 'digits:4'], 'icon' => ['nullable', 'string', 'max:16'],
        ]);
        DB::table('achievements')->insert($data + ['icon' => $data['icon'] ?? '🏆']);
        $this->log('add_achievement', $data['title']);

        return back()->with('success', 'Prestasi berhasil ditambahkan.');
    }

    public function deleteAchievement(int $id): RedirectResponse
    {
        $deleted = DB::table('achievements')->where('id', $id)->delete();
        if ($deleted) {
            $this->log('delete_achievement', $id);
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Prestasi berhasil dihapus.' : 'Prestasi tidak ditemukan.');
    }

    public function addGallery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'image' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        DB::table('gallery')->insert($data + ['created_at' => now()->toDateTimeString()]);
        $this->log('add_gallery', $data['title']);

        return back()->with('success', 'Galeri berhasil ditambahkan.');
    }

    public function deleteGallery(int $id): RedirectResponse
    {
        $deleted = DB::table('gallery')->where('id', $id)->delete();
        if ($deleted) {
            $this->log('delete_gallery', $id);
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Galeri berhasil dihapus.' : 'Galeri tidak ditemukan.');
    }

    public function addTicker(Request $request): RedirectResponse
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:500']]);
        DB::table('tickers')->insert(['content' => trim($data['content'])]);
        $this->log('add_ticker', $data['content']);

        return back()->with('success', 'Pengumuman berhasil ditambahkan.');
    }

    public function deleteTicker(int $id): RedirectResponse
    {
        $deleted = DB::table('tickers')->where('id', $id)->delete();
        if ($deleted) {
            $this->log('delete_ticker', $id);
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Pengumuman berhasil dihapus.' : 'Pengumuman tidak ditemukan.');
    }

    public function updateSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'in:highlight_video_url'],
            'value' => ['required', 'url', 'max:500'],
        ]);
        DB::table('site_settings')->updateOrInsert(['key' => $data['key']], ['value' => $data['value']]);
        $this->log('update_setting', $data['key']);

        return back()->with('success', 'Pengaturan situs berhasil diperbarui.');
    }

    public function status(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,review,accepted,rejected']]);
        DB::table('pendaftar')->where('id', $id)->update(['status' => $data['status']]);
        $this->log('update_applicant_status', $id.': '.$data['status']);

        return back()->with('success', 'Status pendaftar berhasil diperbarui.');
    }

    public function deleteApplicant(int $id): RedirectResponse
    {
        $applicant = DB::table('pendaftar')->where('id', $id)->first();
        if (! $applicant) {
            return back()->with('error', 'Pendaftar tidak ditemukan.');
        }
        DB::table('pendaftar')->where('id', $id)->delete();
        $this->deleteUpload('uploads/'.basename((string) $applicant->foto));
        $this->log('delete_applicant', $id.': '.$applicant->nama);

        return back()->with('success', 'Pendaftar berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        $records = DB::table('pendaftar')->orderBy('id')->get();

        return response()->streamDownload(function () use ($records): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Nama', 'Role', 'WhatsApp', 'Foto', 'Status']);
            foreach ($records as $record) {
                fputcsv($output, array_map($this->protectCsvCell(...), [
                    $record->id, $record->nama, $record->role, $record->whatsapp, $record->foto, $record->status,
                ]));
            }
            fclose($output);
        }, 'pendaftar_namdel.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateMatch(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'competition' => ['required', 'string', 'max:150'],
            'game' => ['required', 'in:MLBB,HOK'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'team_home' => ['nullable', 'string', 'max:100', 'required_if:status,finished'],
            'team_away' => ['nullable', 'string', 'max:100', 'required_if:status,finished'],
            'score_home' => ['nullable', 'integer', 'min:0'],
            'score_away' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:upcoming,finished'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'title' => trim($data['title']), 'competition' => trim($data['competition']),
            'game' => $data['game'], 'date' => $data['date'], 'time' => ($data['time'] ?? '00:00').':00',
            'team_home' => trim($data['team_home'] ?? ''), 'team_away' => trim($data['team_away'] ?? ''),
            'score_home' => (int) ($data['score_home'] ?? 0), 'score_away' => (int) ($data['score_away'] ?? 0),
            'status' => $data['status'], 'description' => trim($data['description'] ?? ''),
        ];
    }

    private function storeImage(Request $request, string $field): string
    {
        $file = $request->file($field);
        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $file->move(public_path('static/uploads'), $filename);

        return $filename;
    }

    private function deleteUpload(?string $path, bool $allowBareFilename = false): void
    {
        $normalized = str_replace('\\', '/', trim((string) $path));
        if (! str_starts_with($normalized, 'uploads/') && ! $allowBareFilename) {
            return;
        }
        $filename = basename($normalized);
        if (preg_match('/^[A-Za-z0-9._-]+$/', $filename) === 1 && is_file(public_path('static/uploads/'.$filename))) {
            if (! unlink(public_path('static/uploads/'.$filename))) {
                Log::warning('Could not delete uploaded image.', ['filename' => $filename]);
            }
        }
    }

    private function log(string $action, string|int $target = ''): void
    {
        DB::table('activity_logs')->insert([
            'actor' => (string) session('admin_user', 'admin'),
            'action' => $action,
            'target' => mb_substr((string) $target, 0, 200),
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    private function protectCsvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^\s*[=+\-@]/', $text) === 1 ? "'".$text : $text;
    }
}
