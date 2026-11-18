<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $address = (string) $request->ip();
        $visitorHash = hash_hmac('sha256', $address, (string) config('app.key'));
        DB::table('page_views')->insert([
            'path' => mb_substr($request->path() === '/' ? '/' : '/'.$request->path(), 0, 500),
            'visitor_hash' => $visitorHash,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'visited_at' => now()->toDateTimeString(),
        ]);

        $matches = DB::table('matches')
            ->select('matches.*')
            ->selectRaw('(SELECT COUNT(*) FROM match_votes WHERE match_id = matches.id AND winner = matches.team_home) AS votes_home')
            ->selectRaw('(SELECT COUNT(*) FROM match_votes WHERE match_id = matches.id AND winner = matches.team_away) AS votes_away')
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function (object $match): object {
                $total = $match->votes_home + $match->votes_away;
                $match->home_percent = $total ? (int) round($match->votes_home * 100 / $total) : 0;
                $match->away_percent = 100 - $match->home_percent;

                return $match;
            });

        $teamStatistics = [];
        foreach (['MLBB', 'HOK'] as $game) {
            $finished = DB::table('matches')->where('game', $game)->where('status', 'finished')->get();
            $teamNames = ['NEST', 'NAMDEL', 'NAMDEL ESPORT', 'SMAN 68', 'SMAN 68 JAKARTA'];
            $teamMatches = $finished->filter(function (object $match) use ($teamNames): bool {
                return in_array(mb_strtoupper(trim((string) $match->team_home)), $teamNames, true)
                    || in_array(mb_strtoupper(trim((string) $match->team_away)), $teamNames, true);
            });
            $wins = $teamMatches->filter(function (object $match) use ($teamNames): bool {
                $home = in_array(mb_strtoupper(trim((string) $match->team_home)), $teamNames, true);

                return $home
                    ? $match->score_home > $match->score_away
                    : $match->score_away > $match->score_home;
            })->count();
            $teamStatistics[$game] = [
                'matches' => $teamMatches->count(),
                'wins' => $wins,
                'losses' => $teamMatches->count() - $wins,
                'winrate' => $teamMatches->count() ? (int) round($wins * 100 / $teamMatches->count()) : 0,
            ];
        }

        $recaps = DB::table('tournament_recaps')->orderByDesc('event_date')->orderByDesc('id')->get();
        foreach ($recaps as $recap) {
            $picks = DB::table('hero_match_stats')
                ->select('hero_name')
                ->selectRaw('COUNT(*) AS picks')
                ->selectRaw("SUM(CASE WHEN result = 'win' THEN 1 ELSE 0 END) AS wins")
                ->where('game', $recap->game)->whereRaw('LOWER(event_name) = LOWER(?)', [$recap->event_name])
                ->groupBy('hero_name')->get()->keyBy(fn (object $row): string => mb_strtolower($row->hero_name));
            $bans = DB::table('hero_ban_stats')->select('hero_name')->selectRaw('COUNT(*) AS bans')
                ->where('game', $recap->game)->whereRaw('LOWER(event_name) = LOWER(?)', [$recap->event_name])
                ->groupBy('hero_name')->get()->keyBy(fn (object $row): string => mb_strtolower($row->hero_name));
            $heroNames = $picks->keys()->merge($bans->keys())->unique();
            $recap->hero_stats = $heroNames->map(function (string $key) use ($picks, $bans): object {
                $pick = $picks->get($key);
                $ban = $bans->get($key);

                return (object) [
                    'hero_name' => $pick->hero_name ?? $ban->hero_name,
                    'picks' => $pick->picks ?? 0,
                    'bans' => $ban->bans ?? 0,
                    'wins' => $pick->wins ?? 0,
                ];
            })->sortByDesc('picks')->take(8)->values();
        }

        $roster = DB::table('roster')->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (object $player): object {
                $heroes = json_decode($player->signature_heroes ?: '[]', true);
                $player->signature_heroes = is_array($heroes) ? $heroes : [];

                return $player;
            });

        return view('home', [
            'matches' => $matches,
            'roster' => $roster,
            'rosterByGame' => $roster->groupBy('game'),
            'teamStatistics' => $teamStatistics,
            'recaps' => $recaps,
            'achievements' => DB::table('achievements')->orderByDesc('year')->orderByDesc('id')->get(),
            'gallery' => DB::table('gallery')->orderByDesc('id')->get(),
            'tickers' => DB::table('tickers')->orderBy('id')->get(),
            'highlightVideoUrl' => DB::table('site_settings')->where('key', 'highlight_video_url')->value('value')
                ?? 'https://www.youtube.com/embed/IDOM7NIIKhA',
            'sponsorTiers' => ['bronze', 'silver', 'gold'],
            'whatsappNumber' => config('services.namdel.whatsapp_number'),
        ]);
    }

    public function vote(Request $request, int $match): RedirectResponse
    {
        $validated = $request->validate(['winner' => ['required', 'string', 'max:100']]);
        $scheduledMatch = DB::table('matches')->where('id', $match)->first();

        if (! $scheduledMatch) {
            abort(404);
        }

        $scheduledAt = Carbon::parse($scheduledMatch->date.' '.($scheduledMatch->time ?: '00:00:00'), 'Asia/Jakarta');
        if ($scheduledMatch->status !== 'upcoming' || $scheduledAt->isPast()
            || ! $scheduledMatch->team_home || ! $scheduledMatch->team_away
            || ! in_array($validated['winner'], [$scheduledMatch->team_home, $scheduledMatch->team_away], true)) {
            return back()->with('error', 'Prediksi hanya tersedia untuk pertandingan mendatang.');
        }

        $voterId = $request->session()->get('voter_id');
        if (! $voterId) {
            $voterId = bin2hex(random_bytes(32));
            $request->session()->put('voter_id', $voterId);
        }
        $voterHash = hash_hmac('sha256', $voterId, (string) config('app.key'));

        try {
            DB::table('match_votes')->insert([
                'match_id' => $match,
                'voter_hash' => $voterHash,
                'winner' => $validated['winner'],
            ]);
        } catch (QueryException $exception) {
            if (! str_contains(mb_strtolower($exception->getMessage()), 'unique')) {
                throw $exception;
            }

            return back()->with('error', 'Kamu sudah memberikan prediksi untuk pertandingan ini.');
        }

        return back()->with('success', 'Prediksimu berhasil dicatat. Terima kasih sudah ikut mendukung!');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:Jungler,Roamer,Mid Laner,Gold Laner,Exp Laner,Coach/Manager'],
            'whatsapp' => ['required', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'foto_rank' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $filename = $this->storePublicImage($request, 'foto_rank');
        DB::table('pendaftar')->insert([
            'nama' => trim($validated['nama']),
            'role' => $validated['role'],
            'whatsapp' => $validated['whatsapp'],
            'foto' => $filename,
            'status' => 'pending',
        ]);

        $this->sendNotification('Pendaftar baru NAMDEL ESPORT: '.$validated['nama'].' — '.$validated['role'].'.');

        return back()->with('success', 'Mantap! Pendaftaran berhasil.');
    }

    public function sponsorship(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254'],
            'tier' => ['required', 'in:bronze,silver,gold'],
        ]);
        DB::table('sponsorship_inquiries')->insert($validated + ['created_at' => now()->toDateTimeString()]);
        $this->sendNotification('Inquiry sponsor baru: '.$validated['company'].' — '.$validated['contact_person'].' ('.$validated['email'].'), tier '.ucfirst($validated['tier']).'.');

        return back()->with('success', 'Terima kasih! Tim kami akan segera menghubungi Anda.');
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $sort = $request->query('sort', 'points');
        if (! in_array($sort, ['points', 'kda', 'mvp'], true)) {
            return response()->json(['error' => 'Kategori leaderboard tidak valid.'], 400);
        }

        $weekStart = now('Asia/Jakarta')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $players = DB::table('scrim_stats')->select('player_name AS name', 'team_name AS team')
            ->selectRaw('SUM(points) AS points, SUM(kills) AS kills, SUM(deaths) AS deaths, SUM(assists) AS assists')
            ->selectRaw('SUM(CASE WHEN is_mvp = 1 AND match_date BETWEEN ? AND ? THEN 1 ELSE 0 END) AS weekly_mvp', [
                $weekStart->toDateString(), $weekEnd->toDateString(),
            ])
            ->selectRaw('COUNT(*) AS matches')->groupBy('player_name', 'team_name')->get()
            ->map(function (object $player): object {
                $player->kda = round(($player->kills + $player->assists) / max($player->deaths, 1), 2);

                return $player;
            });

        $sortKey = match ($sort) {
            'kda' => 'kda',
            'mvp' => 'weekly_mvp',
            default => 'points',
        };
        $players = $players->sortByDesc(fn (object $player): array => [
            $player->{$sortKey}, $player->points, $player->kda,
        ])->values();
        $players->each(fn (object $player, int $index) => $player->rank = $index + 1);

        return response()->json([
            'players' => $players->take(10),
            'weekly_mvp' => $players->where('weekly_mvp', '>', 0)->sortByDesc('weekly_mvp')->first(),
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'updated_at' => now('Asia/Jakarta')->format('Y-m-d\TH:i'),
        ]);
    }

    public function serviceWorker(): BinaryFileResponse
    {
        return response()->file(public_path('static/service-worker.js'), [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    private function storePublicImage(Request $request, string $field): string
    {
        $file = $request->file($field);
        $extension = $file->guessExtension() ?: 'jpg';
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $file->move(public_path('static/uploads'), $filename);

        return $filename;
    }

    private function sendNotification(string $message): void
    {
        $webhook = config('services.namdel.webhook_url');
        if ($webhook) {
            try {
                Http::timeout(5)->post($webhook, ['content' => $message])->throw();
            } catch (ConnectionException|RequestException $exception) {
                Log::warning('Discord webhook delivery failed.', ['exception' => $exception::class]);
            }
        }

        $token = config('services.namdel.telegram_bot_token');
        $chatId = config('services.namdel.telegram_chat_id');
        if ($token && $chatId) {
            try {
                Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $message,
                ])->throw();
            } catch (ConnectionException|RequestException $exception) {
                Log::warning('Telegram notification delivery failed.', ['exception' => $exception::class]);
            }
        }
    }
}
