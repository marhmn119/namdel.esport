<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NamdelMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_renders_and_records_an_aggregate_view(): void
    {
        DB::table('matches')->insert([
            'title' => 'Test HOK event',
            'competition' => 'Test League',
            'date' => now()->addDays(4)->toDateString(),
            'time' => '18:00:00',
            'team_home' => '',
            'team_away' => '',
            'score_home' => 0,
            'score_away' => 0,
            'status' => 'upcoming',
            'description' => '',
            'game' => 'HOK',
        ]);
        DB::table('roster')->insert([
            'name' => 'Aimar',
            'role' => 'Jungler',
            'role_id' => 'hok-aimar',
            'role_tag' => 'Jungler',
            'description' => 'HOK player',
            'photo' => 'aimar.png',
            'stats' => '50,50,50,50,50',
            'signature_heroes' => '[{"name":"Jing"}]',
            'sort_order' => 1,
            'game' => 'HOK',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('ROSTER');
        $response->assertSee('HOK');
        $response->assertSee('Tim pertandingan akan diumumkan.');
        $response->assertSee('id="matchDetailModal"', false);
        $response->assertSee('id="faq"', false);
        $response->assertSee('data-profile-id="hok-aimar"', false);
        $response->assertSee('id="profile-hok-aimar"', false);
        $response->assertHeader('X-Frame-Options', 'DENY');
        $this->get('https://namdel.test')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->assertDatabaseHas('page_views', ['path' => '/']);
    }

    public function test_admin_requires_a_login_and_valid_credentials(): void
    {
        Config::set('services.namdel.admin_user', 'test-admin');
        Config::set('services.namdel.admin_pass', 'test-password');

        $this->get('/admin')->assertRedirect(route('login'));
        $this->from('/login')->post('/login', [
            'username' => 'test-admin',
            'password' => 'incorrect',
        ])->assertRedirect('/login')->assertSessionHas('error');

        $this->post('/login', [
            'username' => 'test-admin',
            'password' => 'test-password',
        ])->assertRedirect(route('admin.index'));

        $this->get('/admin')->assertOk()->assertSee('Portal Admin');
        $this->post('/logout')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_upcoming_match_can_be_created_without_team_names_but_finished_match_cannot(): void
    {
        $this->withSession(['logged_in' => true, 'admin_user' => 'test-admin']);
        $match = [
            'title' => 'Tournament schedule',
            'competition' => 'Jakarta Esport',
            'game' => 'MLBB',
            'date' => now()->addDays(8)->toDateString(),
            'time' => '15:00',
            'team_home' => '',
            'team_away' => '',
            'score_home' => '0',
            'score_away' => '0',
            'status' => 'upcoming',
        ];

        $this->post(route('admin.match.add'), $match)->assertRedirect();
        $this->assertDatabaseHas('matches', [
            'title' => 'Tournament schedule',
            'team_home' => '',
            'team_away' => '',
            'status' => 'upcoming',
        ]);

        $finishedMatch = array_merge($match, ['status' => 'finished']);
        $this->from('/admin')->post(route('admin.match.add'), $finishedMatch)
            ->assertRedirect('/admin')
            ->assertSessionHasErrors(['team_home', 'team_away']);
    }

    public function test_registration_accepts_only_valid_images_and_records_an_applicant(): void
    {
        $this->post(route('register'), [
            'nama' => 'Test Player',
            'role' => 'Jungler',
            'whatsapp' => '081234567890',
            'foto_rank' => UploadedFile::fake()->createWithContent(
                'rank.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p5sAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect()->assertSessionHas('success');

        $applicant = DB::table('pendaftar')->where('nama', 'Test Player')->first();
        $this->assertNotNull($applicant);
        $this->assertFileExists(public_path('static/uploads/'.$applicant->foto));
        unlink(public_path('static/uploads/'.$applicant->foto));
    }

    public function test_admin_deletes_applicant_and_recap_uploads_without_touching_other_assets(): void
    {
        $this->withSession(['logged_in' => true, 'admin_user' => 'test-admin']);
        $applicantImage = bin2hex(random_bytes(12)).'.png';
        $recapImage = bin2hex(random_bytes(12)).'.png';
        $uploadDirectory = public_path('static/uploads');
        file_put_contents($uploadDirectory.'/'.$applicantImage, 'applicant image');
        file_put_contents($uploadDirectory.'/'.$recapImage, 'recap image');

        try {
            $applicantId = DB::table('pendaftar')->insertGetId([
                'nama' => 'Delete Test',
                'role' => 'Jungler',
                'whatsapp' => '081234567890',
                'foto' => $applicantImage,
                'status' => 'pending',
            ]);
            $recapId = DB::table('tournament_recaps')->insertGetId([
                'game' => 'MLBB',
                'event_name' => 'Delete Test Event',
                'event_date' => now()->toDateString(),
                'summary' => '',
                'team_stats' => '',
                'bracket_image' => $recapImage,
                'result_image' => null,
                'created_at' => now()->toDateTimeString(),
            ]);

            $this->delete(route('admin.applicants.delete', $applicantId))->assertRedirect();
            $this->post(route('admin.recap.delete', $recapId))->assertRedirect();

            $this->assertDatabaseMissing('pendaftar', ['id' => $applicantId]);
            $this->assertDatabaseMissing('tournament_recaps', ['id' => $recapId]);
            $this->assertFileDoesNotExist($uploadDirectory.'/'.$applicantImage);
            $this->assertFileDoesNotExist($uploadDirectory.'/'.$recapImage);
        } finally {
            foreach ([$applicantImage, $recapImage] as $filename) {
                $path = $uploadDirectory.'/'.$filename;
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_admin_activity_log_is_descriptive_and_paginated(): void
    {
        $this->withSession(['logged_in' => true, 'admin_user' => 'test-admin']);

        for ($index = 1; $index <= 18; $index++) {
            DB::table('activity_logs')->insert([
                'actor' => 'test-admin',
                'action' => 'add_match',
                'target' => 'Detail pertandingan '.$index,
                'created_at' => now()->subMinutes($index)->toDateTimeString(),
            ]);
        }

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Menambahkan jadwal pertandingan')
            ->assertSee('Detail pertandingan 18')
            ->assertDontSee('Detail pertandingan 13</td>')
            ->assertSee('Menampilkan 1–5 dari 18 aktivitas');

        $this->get('/admin?activity_page=2')
            ->assertOk()
            ->assertSee('Detail pertandingan 13')
            ->assertSee('Menampilkan 6–10 dari 18 aktivitas');
    }
}
