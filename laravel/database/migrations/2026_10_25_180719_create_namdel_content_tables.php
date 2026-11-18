<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $definitions = [
            'pendaftar' => static function (Blueprint $table): void {
                $table->id();
                $table->string('nama', 100);
                $table->string('role', 60);
                $table->string('whatsapp', 30);
                $table->string('foto')->nullable();
                $table->string('status', 20)->default('pending');
            },
            'matches' => static function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('competition');
                $table->date('date');
                $table->time('time')->nullable();
                $table->string('team_home')->nullable();
                $table->string('team_away')->nullable();
                $table->unsignedInteger('score_home')->default(0);
                $table->unsignedInteger('score_away')->default(0);
                $table->string('status', 20)->default('upcoming');
                $table->text('description')->nullable();
                $table->string('game', 10)->default('MLBB');
            },
            'gallery' => static function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('image');
                $table->text('description')->nullable();
                $table->dateTime('created_at')->nullable();
            },
            'tickers' => static function (Blueprint $table): void {
                $table->id();
                $table->text('content');
            },
            'scrim_stats' => static function (Blueprint $table): void {
                $table->id();
                $table->string('player_name');
                $table->string('team_name');
                $table->date('match_date');
                $table->unsignedInteger('points')->default(0);
                $table->unsignedInteger('kills')->default(0);
                $table->unsignedInteger('deaths')->default(0);
                $table->unsignedInteger('assists')->default(0);
                $table->boolean('is_mvp')->default(false);
            },
            'hero_match_stats' => static function (Blueprint $table): void {
                $table->id();
                $table->string('event_name');
                $table->string('hero_name');
                $table->string('result', 10);
                $table->dateTime('created_at');
                $table->string('game', 10)->default('MLBB');
            },
            'hero_ban_stats' => static function (Blueprint $table): void {
                $table->id();
                $table->string('event_name');
                $table->string('hero_name');
                $table->dateTime('created_at');
                $table->string('game', 10)->default('MLBB');
            },
            'tournament_recaps' => static function (Blueprint $table): void {
                $table->id();
                $table->string('game', 10);
                $table->string('event_name');
                $table->date('event_date');
                $table->text('summary')->default('');
                $table->text('team_stats')->default('');
                $table->string('bracket_image')->nullable();
                $table->string('result_image')->nullable();
                $table->dateTime('created_at');
            },
            'achievements' => static function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('event');
                $table->string('year', 4);
                $table->string('icon', 16)->default('🏆');
            },
            'match_votes' => static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('match_id');
                $table->string('voter_hash', 64);
                $table->string('winner');
                $table->unique(['match_id', 'voter_hash']);
            },
            'sponsorship_inquiries' => static function (Blueprint $table): void {
                $table->id();
                $table->string('company', 150);
                $table->string('contact_person', 100);
                $table->string('email', 254);
                $table->string('tier', 20);
                $table->dateTime('created_at');
            },
            'activity_logs' => static function (Blueprint $table): void {
                $table->id();
                $table->string('actor', 100);
                $table->string('action', 100);
                $table->string('target', 200)->default('');
                $table->dateTime('created_at');
            },
            'page_views' => static function (Blueprint $table): void {
                $table->id();
                $table->string('path', 500);
                $table->string('visitor_hash', 64);
                $table->string('user_agent', 500)->default('');
                $table->dateTime('visited_at');
            },
            'roster' => static function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('role');
                $table->string('role_id')->unique();
                $table->string('role_tag');
                $table->text('description');
                $table->string('photo');
                $table->string('stats');
                $table->text('signature_heroes');
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('game', 10)->default('MLBB');
            },
            'news' => static function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->longText('content');
                $table->string('image')->nullable();
                $table->dateTime('created_at')->nullable();
            },
        ];

        foreach ($definitions as $name => $define) {
            if (Schema::hasTable($name)) {
                continue;
            }

            Schema::create($name, $define);
        }

        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->text('value');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Imported legacy tables may predate this migration; never drop user data on rollback.
    }
};
