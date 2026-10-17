from flask import Flask, jsonify, render_template, request, redirect, url_for, session, flash, make_response, abort, send_from_directory
from flask_wtf.csrf import CSRFProtect
import os
import sqlite3
import hashlib
import hmac
import logging
from logging.handlers import RotatingFileHandler
from contextlib import contextmanager
from dotenv import load_dotenv
import uuid
import json
import csv
import io
from datetime import datetime, timedelta, timezone
import requests
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from flask_babel import Babel
from PIL import Image, UnidentifiedImageError

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY')
if not app.secret_key:
    raise RuntimeError('SECRET_KEY wajib diatur melalui environment atau file .env.')
app.config.update(
    SESSION_COOKIE_HTTPONLY=True,
    SESSION_COOKIE_SECURE=os.getenv('SESSION_COOKIE_SECURE', '').lower() == 'true',
    SESSION_COOKIE_SAMESITE='Lax',
)
csrf = CSRFProtect(app)
limiter = Limiter(
    get_remote_address,
    app=app,
    default_limits=[],
    storage_uri=os.getenv('RATELIMIT_STORAGE_URI', 'memory://'),
)

def get_locale():
    return 'id'


babel = Babel(app, locale_selector=get_locale)

ADMIN_USER = os.getenv('ADMIN_USER')
ADMIN_PASS = os.getenv('ADMIN_PASS')

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.abspath(os.getenv('DATABASE_PATH', os.path.join(BASE_DIR, 'namdel.db')))
UPLOAD_FOLDER = os.path.join(BASE_DIR, 'static', 'uploads')
LOG_FOLDER = os.path.join(BASE_DIR, 'logs')
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 5 * 1024 * 1024
os.makedirs(os.path.dirname(DB_PATH), exist_ok=True)
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(LOG_FOLDER, exist_ok=True)

file_handler = RotatingFileHandler(os.path.join(LOG_FOLDER, 'app.log'), maxBytes=1_000_000, backupCount=3, encoding='utf-8')
file_handler.setLevel(logging.INFO)
file_handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(message)s'))
app.logger.addHandler(file_handler)
app.logger.setLevel(logging.INFO)

ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png', 'webp'}
ALLOWED_ROLES = ['jungler', 'roamer', 'mid laner', 'gold laner', 'exp laner', 'coach/manager']
WHATSAPP_NUMBER = os.getenv('WHATSAPP_NUMBER', '6285777103329')
MLBB_HERO_NAMES = (
    'Aamon', 'Akai', 'Aldous', 'Alice', 'Alpha', 'Alucard', 'Angela', 'Argus', 'Arlott', 'Atlas', 'Aulus', 'Aurora',
    'Badang', 'Balmond', 'Bane', 'Barats', 'Baxia', 'Beatrix', 'Belerick', 'Benedetta', 'Brody', 'Bruno',
    "Chang'e", 'Carmilla', 'Cecilion', 'Chip', 'Chou', 'Cici', 'Claude', 'Clint', 'Cyclops', 'Collie',
    'Diggie', 'Dyrroth',
    'Edith', 'Esmeralda', 'Estes', 'Eudora',
    'Fanny', 'Faramis', 'Floryn', 'Franco', 'Fredrinn', 'Freya',
    'Gatotkaca', 'Gloo', 'Gord', 'Granger', 'Grock', 'Guinevere', 'Gusion',
    'Hanabi', 'Hanzo', 'Harith', 'Harley', 'Hayabusa', 'Helcurt', 'Hilda', 'Hylos', 'Hirara',
    'Irithel', 'Ixia',
    'Jawhead', 'Johnson', 'Joy', 'Julian',
    'Kadita', 'Kagura', 'Kaja', 'Kalea', 'Karina', 'Karrie', 'Khaleed', 'Khufra', 'Kimmy',
    'Lancelot', 'Lapu-Lapu', 'Layla', 'Leomord', 'Lesley', 'Ling', 'Lolita', 'Lukas', 'Lunox', 'Lylia', 'Luo Yi',
    'Masha', 'Marcel', 'Martis', 'Mathilda', 'Melissa', 'Minotaur', 'Minsitthar', 'Miya', 'Moskov',
    'Nana', 'Natalia', 'Natan', 'Nolan', 'Novaria',
    'Obsidia', 'Odette',
    'Paquito', 'Pharsa', 'Phoveus', 'Popol & Kupa',
    'Rafaela', 'Roger', 'Ruby',
    'Saber', 'Selena', 'Silvanna', 'Sora', 'Sun', 'Suyou',
    'Terizla', 'Thamuz', 'Tigreal',
    'Uranus',
    'Vale', 'Valentina', 'Valir', 'Vexana',
    'Wanwan',
    'X.Borg', 'Xavier',
    'Yi Sun-shin', 'Yin', 'Yu Zhong', 'Yve',
    'Zetian', 'Zhask', 'Zhuxin', 'Zilong',
)

month_map = {
    'Jan': 'JAN', 'Feb': 'FEB', 'Mar': 'MAR', 'Apr': 'APR', 'May': 'MEI', 'Jun': 'JUN',
    'Jul': 'JUL', 'Aug': 'AGU', 'Sep': 'SEP', 'Oct': 'OKT', 'Nov': 'NOV', 'Dec': 'DES'
}


def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


@contextmanager
def get_db_connection():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute('PRAGMA foreign_keys = ON')
    try:
        with conn:
            yield conn
    finally:
        conn.close()


def log_activity(action, target=''):
    with get_db_connection() as conn:
        conn.execute(
            'INSERT INTO activity_logs (actor, action, target, created_at) VALUES (?, ?, ?, ?)',
            (session.get('admin_user', 'admin'), action, str(target)[:200], datetime.now().isoformat(timespec='seconds'))
        )


def send_webhook(message):
    discord_url = os.getenv('WEBHOOK_URL')
    if discord_url:
        try:
            response = requests.post(discord_url, json={'content': message}, timeout=5)
            response.raise_for_status()
        except requests.RequestException:
            app.logger.exception('Discord webhook delivery failed.')

    telegram_token = os.getenv('TELEGRAM_BOT_TOKEN')
    telegram_chat_id = os.getenv('TELEGRAM_CHAT_ID')
    if telegram_token and telegram_chat_id:
        try:
            response = requests.post(
                f'https://api.telegram.org/bot{telegram_token}/sendMessage',
                json={'chat_id': telegram_chat_id, 'text': message},
                timeout=5,
            )
            response.raise_for_status()
        except requests.RequestException:
            app.logger.exception('Telegram webhook delivery failed.')


def safe_image_upload(upload):
    if not upload or not upload.filename or not allowed_file(upload.filename):
        raise ValueError('Ekstensi file tidak diizinkan. Gunakan JPG, JPEG, PNG, GIF, atau WEBP.')
    try:
        upload.stream.seek(0)
        with Image.open(upload.stream) as image:
            image.verify()
        upload.stream.seek(0)
        with Image.open(upload.stream) as image:
            image = image.convert('RGB')
            image.thumbnail((1200, 1200), Image.Resampling.LANCZOS)
            filename = f'{uuid.uuid4().hex}.webp'
            image.save(os.path.join(app.config['UPLOAD_FOLDER'], filename), 'WEBP', quality=85, method=6)
            return filename
    except (UnidentifiedImageError, OSError, Image.DecompressionBombError) as error:
        raise ValueError('File yang diunggah bukan gambar yang valid.') from error


def protect_csv_cell(value):
    text = '' if value is None else str(value)
    if text.lstrip().startswith(('=', '+', '-', '@')):
        return "'" + text
    return text


@app.before_request
def record_page_view():
    if request.method != 'GET' or request.endpoint in {'static', 'service_worker', 'leaderboard_api'}:
        return
    if request.path.startswith('/admin') or request.path.startswith('/api/'):
        return
    address = request.remote_addr or ''
    visitor_hash = hashlib.sha256(f'{app.secret_key}:{address}'.encode()).hexdigest()
    with get_db_connection() as conn:
        conn.execute(
            'INSERT INTO page_views (path, visitor_hash, user_agent, visited_at) VALUES (?, ?, ?, ?)',
            (request.path[:500], visitor_hash, request.user_agent.string[:500], datetime.now().isoformat(timespec='seconds'))
        )


@app.context_processor
def language_context():
    translations = {
        'id': {
            'menu': 'Menu', 'schedule': 'Jadwal', 'stats': 'Statistik', 'achievements': 'Prestasi',
            'team': 'Tim', 'partnership': 'Kemitraan', 'join': 'Daftar Pemain',
            'vote': 'Pilih pemenang',
            'schedule_title': 'SAATNYA BERLAGA.', 'stats_title': 'STATISTIK TIM',
            'achievement_title': 'PRESTASI KAMI', 'roster_title': 'ROSTER TIM UTAMA',
            'join_title': 'REKRUTMEN PEMAIN', 'partner_title': 'MITRA RESMI',
            'sponsor_prompt': 'Tertarik menjadi sponsor?', 'submit_inquiry': 'Kirim inquiry',
        },
    }
    def static_url(filename, external=False):
        path = os.path.join(app.static_folder, filename)
        try:
            version = os.stat(path).st_mtime_ns
        except OSError:
            app.logger.exception('Static asset is missing: %s', filename)
            return url_for('static', filename=filename, _external=external)
        return url_for('static', filename=filename, v=version, _external=external)

    return {
        't': translations['id'],
        'static_url': static_url,
        'whatsapp_number': WHATSAPP_NUMBER,
    }


@app.route('/service-worker.js')
def service_worker():
    response = make_response(send_from_directory(app.static_folder, 'service-worker.js'))
    response.headers['Service-Worker-Allowed'] = '/'
    response.headers['Cache-Control'] = 'no-cache'
    return response


def validate_whatsapp(wa):
    if not wa:
        return False
    if not wa.isdigit():
        return False
    if not (wa.startswith('08') or wa.startswith('628')):
        return False
    if not (10 <= len(wa) <= 15):
        return False
    return True


@app.template_filter('format_date_id')
def format_date_id(value):
    if not value:
        return ''
    try:
        dt = datetime.strptime(str(value), '%Y-%m-%d')
        month = month_map.get(dt.strftime('%b'), dt.strftime('%b').upper())
        return f"{dt.day:02d} {month}"
    except ValueError:
        return str(value)


@app.template_filter('format_time_id')
def format_time_id(value):
    if not value:
        return ''
    try:
        dt = datetime.strptime(str(value), '%H:%M:%S')
        return dt.strftime('%H.%M')
    except ValueError:
        try:
            dt = datetime.strptime(str(value), '%H:%M')
            return dt.strftime('%H.%M')
        except ValueError:
            return str(value)


def normalize_match_schedule(match_date, match_time):
    date_value = datetime.strptime(match_date, '%Y-%m-%d').date()
    if len(match_time) == 5:
        time_value = datetime.strptime(match_time, '%H:%M').time()
    else:
        time_value = datetime.strptime(match_time, '%H:%M:%S').time()
    return date_value.isoformat(), time_value.strftime('%H:%M:%S')


def init_db():
    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute('''CREATE TABLE IF NOT EXISTS pendaftar
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      nama TEXT, role TEXT, whatsapp TEXT, foto TEXT, status TEXT DEFAULT 'pending')''')
        c.execute('''CREATE TABLE IF NOT EXISTS matches
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      title TEXT,
                      competition TEXT,
                      date TEXT,
                      time TEXT,
                      team_home TEXT,
                      team_away TEXT,
                      score_home INTEGER DEFAULT 0,
                      score_away INTEGER DEFAULT 0,
                      status TEXT DEFAULT 'upcoming',
                      description TEXT)''')
        c.execute('''CREATE TABLE IF NOT EXISTS gallery
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      title TEXT,
                      image TEXT,
                      description TEXT,
                      created_at TEXT DEFAULT CURRENT_DATE)''')
        c.execute('''CREATE TABLE IF NOT EXISTS tickers
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      content TEXT)''')
        c.execute('''CREATE TABLE IF NOT EXISTS scrim_stats
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      player_name TEXT NOT NULL,
                      team_name TEXT NOT NULL,
                      match_date TEXT NOT NULL,
                      points INTEGER NOT NULL DEFAULT 0,
                      kills INTEGER NOT NULL DEFAULT 0,
                      deaths INTEGER NOT NULL DEFAULT 0,
                      assists INTEGER NOT NULL DEFAULT 0,
                      is_mvp INTEGER NOT NULL DEFAULT 0)''')
        c.execute('''CREATE TABLE IF NOT EXISTS hero_match_stats
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      event_name TEXT NOT NULL,
                      hero_name TEXT NOT NULL,
                      result TEXT NOT NULL CHECK(result IN ('win', 'loss')),
                      created_at TEXT NOT NULL)''')
        c.execute('''CREATE TABLE IF NOT EXISTS hero_ban_stats
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      event_name TEXT NOT NULL,
                      hero_name TEXT NOT NULL,
                      created_at TEXT NOT NULL)''')
        c.execute('''CREATE TABLE IF NOT EXISTS achievements
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      title TEXT NOT NULL,
                      event TEXT NOT NULL,
                      year TEXT NOT NULL,
                      icon TEXT NOT NULL DEFAULT '🏆')''')
        c.execute('''CREATE TABLE IF NOT EXISTS match_votes
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      match_id INTEGER NOT NULL REFERENCES matches(id) ON DELETE CASCADE,
                      voter_hash TEXT NOT NULL,
                      winner TEXT NOT NULL,
                      UNIQUE(match_id, voter_hash))''')
        c.execute('''CREATE TABLE IF NOT EXISTS sponsorship_inquiries
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      company TEXT NOT NULL,
                      contact_person TEXT NOT NULL,
                      email TEXT NOT NULL,
                      tier TEXT NOT NULL,
                      created_at TEXT NOT NULL)''')
        c.execute('''CREATE TABLE IF NOT EXISTS activity_logs
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      actor TEXT NOT NULL,
                      action TEXT NOT NULL,
                      target TEXT NOT NULL DEFAULT '',
                      created_at TEXT NOT NULL)''')
        c.execute('''CREATE TABLE IF NOT EXISTS page_views
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      path TEXT NOT NULL,
                      visitor_hash TEXT NOT NULL,
                      user_agent TEXT NOT NULL DEFAULT '',
                      visited_at TEXT NOT NULL)''')
        c.execute('CREATE INDEX IF NOT EXISTS idx_page_views_visited_at ON page_views(visited_at)')
        c.execute('''CREATE TABLE IF NOT EXISTS site_settings
                     (key TEXT PRIMARY KEY, value TEXT NOT NULL)''')
        c.execute("INSERT OR IGNORE INTO site_settings (key, value) VALUES ('highlight_video_url', 'https://www.youtube.com/embed/IDOM7NIIKhA')")

        c.execute('''CREATE TABLE IF NOT EXISTS roster
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      name TEXT NOT NULL,
                      role TEXT NOT NULL,
                      role_id TEXT UNIQUE NOT NULL,
                      role_tag TEXT NOT NULL,
                      description TEXT NOT NULL,
                      photo TEXT NOT NULL,
                      stats TEXT NOT NULL,
                      signature_heroes TEXT NOT NULL,
                      sort_order INTEGER DEFAULT 0)''')

        if c.execute('SELECT COUNT(*) FROM roster').fetchone()[0] == 0:
            default_roster = [
                (
                    'Banu', 'Jungler', 'jungler', 'Aggressive Jungler',
                    'Mesin pembunuh utama NAMDEL ESPORT yang siap mengeksekusi lawan tanpa ragu.',
                    'banu.png', '95,80,95,85,60',
                    json.dumps([
                        {'name': 'Hirara', 'wr': '78% WR', 'img': 'hero-hirara.jpg'},
                        {'name': 'Aulus', 'wr': '65% WR', 'img': 'hero-aulus.jpg'},
                        {'name': 'Paquito', 'wr': '70% WR', 'img': 'hero-paquito.jpg'}
                    ]),
                    1
                ),
                (
                    'Arkan', 'Exp Laner', 'explaner', 'Frontline Initiator',
                    'Fighter tangguh yang bertugas menahan badan dan mengacak-acak formasi musuh.',
                    'arkan.png', '75,95,80,75,85',
                    json.dumps([
                        {'name': 'Benedetta', 'wr': '72% WR', 'img': 'hero-benedetta.jpg'},
                        {'name': 'Aulus', 'wr': '68% WR', 'img': 'hero-aulus.jpg'}
                    ]),
                    2
                ),
                (
                    'Ridwan', 'Mid Laner', 'midlaner', 'Burst & Control',
                    'Mage andalan tim yang siap memberikan burst damage area terbesar saat team fight.',
                    'ridwan.png', '85,95,75,85,70',
                    json.dumps([
                        {'name': 'Lunox', 'wr': '74% WR', 'img': 'hero-lunox.jpg'},
                        {'name': 'Eudora', 'wr': '69% WR', 'img': 'hero-eudora.jpg'}
                    ]),
                    3
                ),
                (
                    'Rasya', 'Roamer', 'roamer', 'Protective Roam',
                    'Sang inisiator sekaligus pelindung tim di garis terdepan. Mata dan telinga bagi rekan setim.',
                    'rasya.png', '50,95,95,70,60',
                    json.dumps([
                        {'name': 'Marcel', 'wr': '75% WR', 'img': 'hero-marcel.jpg'},
                        {'name': 'Grock', 'wr': '71% WR', 'img': 'hero-grock.jpg'},
                        {'name': 'Minotaur', 'wr': '67% WR', 'img': 'hero-minotaur.jpg'}
                    ]),
                    4
                ),
                (
                    'Aimar', 'Gold Laner', 'goldlaner', 'Late Game Insurance',
                    'Marksman tim yang menjadi jaminan kemenangan mutlak di fase late game.',
                    'aimar.png', '95,85,80,90,90',
                    json.dumps([
                        {'name': 'Harith', 'wr': '76% WR', 'img': 'hero-harith.jpg'},
                        {'name': 'Moskov', 'wr': '73% WR', 'img': 'hero-moskov.jpg'},
                        {'name': 'Obsidia', 'wr': '80% WR', 'img': 'hero-obsidia.jpg'}
                    ]),
                    5
                ),
            ]
            c.executemany(
                '''INSERT INTO roster
                   (name, role, role_id, role_tag, description, photo, stats, signature_heroes, sort_order)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)''',
                default_roster
            )

        for old_name, new_name in (
            ('Pertemuan scrim.png', 'pertemuan-scrim.png'),
            ('artbook kepkse.png', 'artbook-kepkse.png'),
        ):
            old_path = os.path.join(app.static_folder, old_name)
            new_path = os.path.join(app.static_folder, new_name)
            if os.path.isfile(old_path) and not os.path.exists(new_path):
                os.replace(old_path, new_path)
            c.execute('UPDATE gallery SET image=? WHERE image=?', (new_name, old_name))

        pendaftar_cols = [row[1] for row in c.execute('PRAGMA table_info(pendaftar)').fetchall()]
        if 'status' not in pendaftar_cols:
            c.execute('ALTER TABLE pendaftar ADD COLUMN status TEXT DEFAULT "pending"')

        today = datetime.now(timezone(timedelta(hours=7))).date()
        count = c.execute('SELECT COUNT(*) FROM matches').fetchone()[0]
        if count == 0:
            default_matches = [
                ('RRQ MABAR', 'Turnamen Pelajar', (today + timedelta(days=7)).isoformat(), '11:30:00', 'NEST', 'RRQ MABAR', 0, 0, 'upcoming', 'Laga pembuka musim turnamen pelajar.'),
                ('ESI JAKARTA', 'Turnamen Pelajar', (today + timedelta(days=14)).isoformat(), '18:00:00', 'NEST', 'ESI JAKARTA', 0, 0, 'upcoming', 'Pertandingan penting untuk melangkah ke babak berikutnya.'),
                ('LIGA ESPORT NASIONAL', 'Liga Sekolah', (today - timedelta(days=3)).isoformat(), '16:00:00', 'NEST', 'SMK BINTANG', 2, 1, 'finished', 'Hasil pertandingan terakhir dimenangkan NEST.')
            ]
            c.executemany(
                "INSERT INTO matches (title, competition, date, time, team_home, team_away, score_home, score_away, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                default_matches
            )
        else:
            for title, seeded_date, seeded_time, days_from_today, description in (
                ('RRQ MABAR', '2026-09-26', '11:30:00', 7, 'Laga pembuka musim turnamen pelajar.'),
                ('ESI JAKARTA', '2026-10-05', '18:00:00', 14, 'Pertandingan penting untuk melangkah ke babak berikutnya.'),
            ):
                if today >= datetime.strptime(seeded_date, '%Y-%m-%d').date():
                    c.execute(
                        '''UPDATE matches SET date=?
                           WHERE title=? AND date=? AND status='upcoming'
                             AND time=? AND team_home='NEST' AND team_away=?
                             AND score_home=0 AND score_away=0 AND description=?''',
                        ((today + timedelta(days=days_from_today)).isoformat(), title, seeded_date,
                         seeded_time, title, description)
                    )

        if c.execute('SELECT COUNT(*) FROM gallery').fetchone()[0] == 0:
            default_gallery = [
                ('Briefing Tim Sebelum Turnamen', 'dokumentasi.png', 'Persiapan strategi sebelum bertanding.'),
                ('Pertemuan Scrim', 'pertemuan-scrim.png', 'Diskusi taktik bersama tim.'),
                ('Penyerahan Artbook', 'artbook-kepkse.png', 'Dokumentasi kegiatan pelajar.'),
                ('Foto Tim NEST', 'artbook.png', 'Komunitas e-sports sekolah yang solid.')
            ]
            c.executemany(
                "INSERT INTO gallery (title, image, description) VALUES (?, ?, ?)",
                default_gallery
            )
            
        if c.execute('SELECT COUNT(*) FROM tickers').fetchone()[0] == 0:
            default_tickers = [
                ('OPEN RECRUITMENT SEASON 2026 - DAFTAR SEKARANG!',),
                ('IKUTI PERJALANAN NAMDEL ESPORT DI SETIAP KOMPETISI!',),
                ('SCRIM RUTIN SETIAP SABTU & MINGGU',),
                ('FOLLOW INSTAGRAM @nest.esports UNTUK UPDATE TERBARU',),
                ('TURNAMEN ESI JAKARTA - OKTOBER 2026',)
            ]
            c.executemany("INSERT INTO tickers (content) VALUES (?)", default_tickers)

        conn.commit()


init_db()


@app.route('/')
def home():
    with get_db_connection() as conn:
        matches = conn.execute(
            '''SELECT m.*,
                      (SELECT COUNT(*) FROM match_votes v WHERE v.match_id=m.id AND v.winner=m.team_home) AS votes_home,
                      (SELECT COUNT(*) FROM match_votes v WHERE v.match_id=m.id AND v.winner=m.team_away) AS votes_away
               FROM matches m ORDER BY date ASC, time ASC'''
        ).fetchall()
        matches = [dict(match) for match in matches]
        for match in matches:
            total_votes = match['votes_home'] + match['votes_away']
            match['home_percent'] = round(match['votes_home'] * 100 / total_votes) if total_votes else 0
            match['away_percent'] = 100 - match['home_percent'] if total_votes else 0
        gallery = conn.execute("SELECT * FROM gallery ORDER BY id DESC").fetchall()
        tickers = conn.execute("SELECT * FROM tickers ORDER BY id ASC").fetchall()
        achievements = conn.execute("SELECT * FROM achievements ORDER BY year DESC, id DESC").fetchall()
        video_row = conn.execute("SELECT value FROM site_settings WHERE key='highlight_video_url'").fetchone()
        highlight_video_url = video_row['value'] if video_row else 'https://www.youtube.com/embed/IDOM7NIIKhA'
        roster_rows = conn.execute("SELECT * FROM roster ORDER BY sort_order ASC, id ASC").fetchall()
        roster = []
        for r in roster_rows:
            item = dict(r)
            try:
                item['signature_heroes'] = json.loads(item.get('signature_heroes') or '[]')
            except (ValueError, TypeError):
                item['signature_heroes'] = []
            roster.append(item)
        hero_stat_events = conn.execute(
            '''SELECT MIN(event_name) AS event_name FROM (
                   SELECT event_name FROM hero_match_stats
                   UNION ALL
                   SELECT event_name FROM hero_ban_stats
               )
               GROUP BY lower(event_name)
               ORDER BY event_name COLLATE NOCASE'''
        ).fetchall()
        selected_event = request.args.get('hero_event', 'all').strip().casefold()
        if selected_event.startswith('event:'):
            selected_event = selected_event.removeprefix('event:')
        event_by_key = {event['event_name'].casefold(): event['event_name'] for event in hero_stat_events}
        selected_event_name = event_by_key.get(selected_event) if selected_event != 'all' else None
        selected_event_key = f'event:{selected_event_name.casefold()}' if selected_event_name else 'all'
        hero_pick_rows = conn.execute(
            '''SELECT lower(event_name) AS event_key,
                       MIN(event_name) AS event_name,
                       lower(hero_name) AS hero_key,
                       MIN(hero_name) AS hero_name,
                       COUNT(*) AS picks,
                       SUM(CASE WHEN result='win' THEN 1 ELSE 0 END) AS wins
                FROM hero_match_stats
                GROUP BY lower(event_name), lower(hero_name)'''
        ).fetchall()
        hero_ban_rows = conn.execute(
            '''SELECT lower(event_name) AS event_key,
                       MIN(event_name) AS event_name,
                       lower(hero_name) AS hero_key,
                       MIN(hero_name) AS hero_name,
                       COUNT(*) AS bans
                FROM hero_ban_stats
                GROUP BY lower(event_name), lower(hero_name)'''
        ).fetchall()
        finished_matches = conn.execute(
            '''SELECT team_home, team_away, score_home, score_away
               FROM matches WHERE status='finished' '''
        ).fetchall()

    hero_names = sorted(MLBB_HERO_NAMES, key=str.casefold)
    event_labels = {event['event_name'].casefold(): event['event_name'] for event in hero_stat_events}
    hero_data_by_event = {
        event_key: {
            hero_name.casefold(): {'hero_name': hero_name, 'picks': 0, 'bans': 0, 'wins': 0}
            for hero_name in hero_names
        }
        for event_key in event_labels
    }
    hero_data_all = {
        hero_name.casefold(): {'hero_name': hero_name, 'picks': 0, 'bans': 0, 'wins': 0}
        for hero_name in hero_names
    }
    for row in hero_pick_rows:
        event_key = row['event_key']
        hero_key = row['hero_key']
        event_heroes = hero_data_by_event.setdefault(event_key, {})
        hero_entry = event_heroes.setdefault(hero_key, {
            'hero_name': row['hero_name'], 'picks': 0, 'bans': 0, 'wins': 0
        })
        hero_entry['picks'] = row['picks']
        hero_entry['wins'] = row['wins']
        all_hero = hero_data_all.setdefault(hero_key, {
            'hero_name': row['hero_name'], 'picks': 0, 'bans': 0, 'wins': 0
        })
        all_hero['picks'] += row['picks']
        all_hero['wins'] += row['wins']

    for row in hero_ban_rows:
        event_key = row['event_key']
        hero_key = row['hero_key']
        event_heroes = hero_data_by_event.setdefault(event_key, {})
        hero_entry = event_heroes.setdefault(hero_key, {
            'hero_name': row['hero_name'], 'picks': 0, 'bans': 0, 'wins': 0
        })
        hero_entry['bans'] = row['bans']
        all_hero = hero_data_all.setdefault(hero_key, {
            'hero_name': row['hero_name'], 'picks': 0, 'bans': 0, 'wins': 0
        })
        all_hero['bans'] += row['bans']

    def with_hero_winrates(hero_data):
        result = []
        for hero_entry in hero_data.values():
            hero_entry['winrate'] = round(hero_entry['wins'] * 100 / hero_entry['picks'], 1) if hero_entry['picks'] else 0
            result.append(hero_entry)
        return sorted(result, key=lambda hero: hero['hero_name'].casefold())

    hero_statistics_by_event = {
        'all': {'label': 'Semua turnamen', 'heroes': with_hero_winrates(hero_data_all)}
    }
    for event_key, event_heroes in hero_data_by_event.items():
        key = f'event:{event_key}'
        hero_statistics_by_event[key] = {
            'label': event_labels.get(event_key, event_key),
            'heroes': with_hero_winrates(event_heroes),
        }

    selected_stats = hero_statistics_by_event.get(selected_event_key, hero_statistics_by_event['all'])
    hero_statistics = selected_stats['heroes']
    hero_top_pick = sorted(
        (hero for hero in hero_statistics if hero['picks'] > 0),
        key=lambda hero: (-hero['picks'], hero['hero_name'].casefold()),
    )[:5]
    hero_top_ban = sorted(
        (hero for hero in hero_statistics if hero['bans'] > 0),
        key=lambda hero: (-hero['bans'], hero['hero_name'].casefold()),
    )[:5]
    hero_top_winrate = sorted(
        (hero for hero in hero_statistics if hero['picks'] >= 5),
        key=lambda hero: (-hero['winrate'], -hero['picks'], hero['hero_name'].casefold()),
    )[:5]

    team_names = {'NEST', 'NAMDEL', 'NAMDEL ESPORT', 'SMAN 68', 'SMAN 68 JAKARTA'}
    team_matches = 0
    wins = 0
    for match in finished_matches:
        home_team = (match['team_home'] or '').strip().upper()
        away_team = (match['team_away'] or '').strip().upper()
        score_home = int(match['score_home'] or 0)
        score_away = int(match['score_away'] or 0)
        if home_team in team_names:
            team_matches += 1
        elif away_team in team_names:
            team_matches += 1
        else:
            continue

        if home_team in team_names and score_home > score_away:
            wins += 1
        elif away_team in team_names and score_away > score_home:
            wins += 1
    winrate = round(wins * 100 / team_matches) if team_matches else 0
    site_url = url_for('home', _external=True)
    site_image_url = url_for('static', filename='logo68.png', _external=True)
    organization_schema = {
        '@context': 'https://schema.org',
        '@type': 'SportsOrganization',
        'name': 'NAMDEL ESPORT',
        'sport': 'Esports',
        'url': site_url,
        'logo': site_image_url,
    }

    return render_template(
        'index.html',
        matches=matches,
        gallery=gallery,
        tickers=tickers,
        achievements=achievements,
        roster=roster,
        highlight_video_url=highlight_video_url,
        total_matches=team_matches,
        winrate=winrate,
        achievement_count=len(achievements),
        hero_names=hero_names,
        hero_statistics=hero_statistics,
        hero_top_pick=hero_top_pick,
        hero_top_ban=hero_top_ban,
        hero_top_winrate=hero_top_winrate,
        hero_stat_events=hero_stat_events,
        hero_statistics_by_event=hero_statistics_by_event,
        selected_hero_event=selected_event_key,
        site_url=site_url,
        site_image_url=site_image_url,
        organization_schema=organization_schema,
    )


@app.route('/vote/<int:match_id>', methods=['POST'])
@limiter.limit('10 per minute')
def cast_prediction(match_id):
    winner = request.form.get('winner', '').strip()
    voter_id = session.setdefault('voter_id', uuid.uuid4().hex)
    voter_hash = hashlib.sha256(f'{app.secret_key}:{voter_id}'.encode()).hexdigest()
    with get_db_connection() as conn:
        match = conn.execute(
            'SELECT team_home, team_away, status, date, time FROM matches WHERE id=?',
            (match_id,)
        ).fetchone()
        if not match:
            abort(404)
        try:
            scheduled_at = datetime.fromisoformat(f"{match['date']}T{match['time'] or '00:00:00'}+07:00")
        except ValueError:
            flash('Tanggal pertandingan belum memiliki format yang valid.', 'error')
            return redirect(url_for('home') + '#jadwal')
        if (match['status'] != 'upcoming' or scheduled_at <= datetime.now(timezone(timedelta(hours=7)))
                or winner not in (match['team_home'], match['team_away'])):
            flash('Prediksi hanya tersedia untuk pertandingan mendatang.', 'error')
            return redirect(url_for('home') + '#jadwal')
        try:
            conn.execute(
                'INSERT INTO match_votes (match_id, voter_hash, winner) VALUES (?, ?, ?)',
                (match_id, voter_hash, winner)
            )
        except sqlite3.IntegrityError:
            flash('Kamu sudah memberikan prediksi untuk pertandingan ini.', 'error')
            return redirect(url_for('home') + '#jadwal')
    flash('Prediksimu berhasil dicatat. Terima kasih sudah ikut mendukung!', 'success')
    return redirect(url_for('home') + '#jadwal')


@app.route('/sponsorship', methods=['POST'])
@limiter.limit('3 per hour')
def sponsorship_inquiry():
    company = request.form.get('company', '').strip()
    contact = request.form.get('contact_person', '').strip()
    email = request.form.get('email', '').strip()
    tier = request.form.get('tier', '').strip().lower()
    if (not company or len(company) > 150 or not contact or len(contact) > 100
            or len(email) > 254 or '@' not in email or tier not in {'bronze', 'silver', 'gold'}):
        flash('Mohon periksa kembali data inquiry sponsor.', 'error')
        return redirect(url_for('home') + '#partnership')
    with get_db_connection() as conn:
        conn.execute(
            'INSERT INTO sponsorship_inquiries (company, contact_person, email, tier, created_at) VALUES (?, ?, ?, ?, ?)',
            (company, contact, email, tier, datetime.now().isoformat(timespec='seconds'))
        )
    send_webhook(f'Inquiry sponsor baru: {company} — {contact} ({email}), tier {tier.title()}.')
    flash('Terima kasih! Tim kami akan segera menghubungi Anda.', 'success')
    return redirect(url_for('home') + '#partnership')


@app.errorhandler(404)
def page_not_found(error):
    return render_template('404.html'), 404


@app.errorhandler(500)
def internal_server_error(error):
    app.logger.error('Unhandled server error.', exc_info=(type(error), error, error.__traceback__))
    return render_template('500.html'), 500


@app.errorhandler(413)
def request_entity_too_large(error):
    flash('Ukuran file terlalu besar! Maksimal 5 MB.', 'error')
    return redirect(url_for('home') + '#join')


@app.route('/api/leaderboard')
def leaderboard_api():
    sort_by = request.args.get('sort', 'points')
    if sort_by not in {'points', 'kda', 'mvp'}:
        return jsonify({'error': 'Kategori leaderboard tidak valid.'}), 400

    now = datetime.now(timezone(timedelta(hours=7)))
    today = now.date()
    week_start = today - timedelta(days=today.weekday())
    week_end = week_start + timedelta(days=6)
    with get_db_connection() as conn:
        rows = conn.execute(
            '''SELECT player_name, team_name,
                      SUM(points) AS points,
                      SUM(kills) AS kills,
                      SUM(deaths) AS deaths,
                      SUM(assists) AS assists,
                      SUM(CASE WHEN is_mvp = 1 AND match_date BETWEEN ? AND ? THEN 1 ELSE 0 END) AS weekly_mvp,
                      COUNT(*) AS matches
               FROM scrim_stats
               GROUP BY player_name, team_name''',
            (week_start.isoformat(), week_end.isoformat())
        ).fetchall()

    players = [
        {
            'name': row[0],
            'team': row[1],
            'points': row[2],
            'kills': row[3],
            'deaths': row[4],
            'assists': row[5],
            'weekly_mvp': row[6],
            'matches': row[7],
            'kda': round((row[3] + row[5]) / max(row[4], 1), 2),
        }
        for row in rows
    ]
    if sort_by == 'kda':
        players.sort(key=lambda player: (player['kda'], player['points']), reverse=True)
    elif sort_by == 'mvp':
        players.sort(key=lambda player: (player['weekly_mvp'], player['points'], player['kda']), reverse=True)
    else:
        players.sort(key=lambda player: (player['points'], player['kda']), reverse=True)

    for rank, player in enumerate(players, start=1):
        player['rank'] = rank
    weekly_mvp = max(
        (player for player in players if player['weekly_mvp'] > 0),
        key=lambda player: (player['weekly_mvp'], player['points'], player['kda']),
        default=None
    )

    return jsonify({
        'players': players[:10],
        'weekly_mvp': weekly_mvp,
        'week_start': week_start.isoformat(),
        'week_end': week_end.isoformat(),
        'updated_at': now.isoformat(timespec='minutes'),
    })


@app.route('/register', methods=['POST'])
@limiter.limit('5 per minute')
def register():
    if request.method == 'POST':
        nama = request.form.get('nama', '').strip()
        role = request.form.get('role', '').strip()
        whatsapp = request.form.get('whatsapp', '').strip()

        if not nama or len(nama) > 100:
            flash('Nama tidak boleh kosong dan maksimal 100 karakter.', 'error')
            return redirect(url_for('home'))

        if not validate_whatsapp(whatsapp):
            flash('Format WhatsApp tidak valid. Harus diawali 08 atau 628 (10-15 digit).', 'error')
            return redirect(url_for('home'))

        if not role or role.lower() not in ALLOWED_ROLES:
            flash('Role yang dipilih tidak valid.', 'error')
            return redirect(url_for('home'))

        foto_file = request.files.get('foto_rank')
        if not foto_file or foto_file.filename == '':
            flash('Foto bukti rank wajib diunggah.', 'error')
            return redirect(url_for('home'))

        try:
            unique_filename = safe_image_upload(foto_file)
        except ValueError as error:
            flash(str(error), 'error')
            return redirect(url_for('home'))

        with get_db_connection() as conn:
            c = conn.cursor()
            c.execute(
                "INSERT INTO pendaftar (nama, role, whatsapp, foto, status) VALUES (?, ?, ?, ?, 'pending')",
                (nama, role, whatsapp, unique_filename)
            )
            conn.commit()

        send_webhook(f'Pendaftar baru NAMDEL ESPORT: {nama} — {role}. Cek dashboard admin untuk detail.')

        flash('Mantap! Pendaftaran berhasil.', 'success')
        return redirect(url_for('home'))


@app.route('/login', methods=['GET', 'POST'])
@limiter.limit('5 per minute', methods=['POST'])
def login():
    if request.method == 'POST':
        user = request.form.get('username')
        password = request.form.get('password')

        if (ADMIN_USER and ADMIN_PASS and hmac.compare_digest(user or '', ADMIN_USER)
                and hmac.compare_digest(password or '', ADMIN_PASS)):
            session.clear()
            session['logged_in'] = True
            session['admin_user'] = user
            log_activity('login', 'admin')
            return redirect(url_for('admin'))
        else:
            flash('Username atau Password salah!', 'error')
            return redirect(url_for('login'))

    return render_template('login.html')


@app.route('/admin')
def admin():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute("SELECT * FROM pendaftar")
        data_pendaftar = c.fetchall()

        c.execute("SELECT COUNT(*) FROM pendaftar")
        total_pendaftar = c.fetchone()[0]

        c.execute("SELECT role, COUNT(*) FROM pendaftar GROUP BY role")
        stats_per_role = dict(c.fetchall())

        c.execute("SELECT * FROM pendaftar ORDER BY id DESC LIMIT 5")
        pendaftar_terbaru = c.fetchall()

        c.execute("SELECT * FROM matches ORDER BY date DESC, time DESC")
        matches = c.fetchall()

        c.execute("SELECT * FROM gallery ORDER BY id DESC")
        gallery = c.fetchall()

        c.execute("SELECT * FROM tickers ORDER BY id ASC")
        tickers = c.fetchall()

        c.execute("SELECT * FROM scrim_stats ORDER BY match_date DESC, id DESC LIMIT 100")
        scrim_stats = c.fetchall()
        hero_usage_entries = c.execute(
            '''SELECT * FROM (
                   SELECT id, event_name, hero_name, 'pick' AS entry_type, result, created_at FROM hero_match_stats
                   UNION ALL
                   SELECT id, event_name, hero_name, 'ban' AS entry_type, NULL AS result, created_at FROM hero_ban_stats
               )
               ORDER BY created_at DESC, id DESC LIMIT 200'''
        ).fetchall()
        hero_usage_events = c.execute(
            '''SELECT MIN(event_name) AS event_name FROM (
                   SELECT event_name FROM hero_match_stats
                   UNION ALL
                   SELECT event_name FROM hero_ban_stats
               )
               GROUP BY lower(event_name)
               ORDER BY event_name COLLATE NOCASE'''
        ).fetchall()
        achievements = c.execute('SELECT * FROM achievements ORDER BY year DESC, id DESC').fetchall()
        activity_logs = c.execute('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 100').fetchall()
        sponsors = c.execute('SELECT * FROM sponsorship_inquiries ORDER BY id DESC LIMIT 100').fetchall()
        total_views = c.execute('SELECT COUNT(*) FROM page_views').fetchone()[0]
        unique_visitors = c.execute('SELECT COUNT(DISTINCT visitor_hash) FROM page_views').fetchone()[0]
        popular_pages = c.execute(
            'SELECT path, COUNT(*) AS views FROM page_views GROUP BY path ORDER BY views DESC LIMIT 10'
        ).fetchall()
        site_settings = {row['key']: row['value'] for row in c.execute('SELECT key, value FROM site_settings').fetchall()}
        roster = c.execute('SELECT * FROM roster ORDER BY sort_order ASC, id ASC').fetchall()

    return render_template(
        'admin.html',
        pendaftar=data_pendaftar,
        total_pendaftar=total_pendaftar,
        stats_per_role=stats_per_role,
        pendaftar_terbaru=pendaftar_terbaru,
        matches=matches,
        gallery=gallery,
        tickers=tickers,
        scrim_stats=scrim_stats,
        hero_usage_entries=hero_usage_entries,
        hero_usage_events=hero_usage_events,
        hero_names=sorted(MLBB_HERO_NAMES, key=str.casefold),
        achievements=achievements,
        activity_logs=activity_logs,
        sponsors=sponsors,
        total_views=total_views,
        unique_visitors=unique_visitors,
        popular_pages=popular_pages,
        site_settings=site_settings,
        roster=roster,
    )


@app.route('/admin/scrim/add', methods=['POST'])
def admin_scrim_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    player_name = request.form.get('player_name', '').strip()
    team_name = request.form.get('team_name', '').strip()
    match_date = request.form.get('match_date', '').strip()
    try:
        datetime.strptime(match_date, '%Y-%m-%d')
        points = int(request.form.get('points', '0'))
        kills = int(request.form.get('kills', '0'))
        deaths = int(request.form.get('deaths', '0'))
        assists = int(request.form.get('assists', '0'))
    except ValueError:
        flash('Tanggal dan statistik pertandingan harus diisi dengan format yang valid.', 'error')
        return redirect(url_for('admin'))

    if not player_name or len(player_name) > 100 or not team_name or len(team_name) > 100:
        flash('Nama pemain dan tim wajib diisi (maksimal 100 karakter).', 'error')
        return redirect(url_for('admin'))

    if any(value < 0 or value > 9999 for value in (points, kills, deaths, assists)):
        flash('Statistik harus berupa angka antara 0 dan 9999.', 'error')
        return redirect(url_for('admin'))

    with get_db_connection() as conn:
        conn.execute(
            '''INSERT INTO scrim_stats
               (player_name, team_name, match_date, points, kills, deaths, assists, is_mvp)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)''',
            (player_name, team_name, match_date, points, kills, deaths, assists,
             1 if request.form.get('is_mvp') == 'on' else 0)
        )
    log_activity('add_scrim_stat', player_name)

    flash('Statistik scrim berhasil ditambahkan ke leaderboard.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/hero-stats/add', methods=['POST'])
def admin_hero_stats_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    event_name = request.form.get('event_name', '').strip()
    submitted_hero = request.form.get('hero_name', '').strip()
    hero_by_key = {hero.casefold(): hero for hero in MLBB_HERO_NAMES}
    hero_name = hero_by_key.get(submitted_hero.casefold(), '')
    entry_type = request.form.get('entry_type', '').strip().lower()
    result = request.form.get('result', '').strip().lower()
    if not event_name or len(event_name) > 120 or not hero_name:
        flash('Nama turnamen dan hero resmi wajib diisi (maksimal 120 karakter untuk nama turnamen).', 'error')
        return redirect(url_for('admin'))
    if entry_type not in {'pick', 'ban'}:
        flash('Pilih apakah hero ini di-pick atau di-ban.', 'error')
        return redirect(url_for('admin'))
    if entry_type == 'pick' and result not in {'win', 'loss'}:
        flash('Pilih hasil pertandingan menang atau kalah.', 'error')
        return redirect(url_for('admin'))

    with get_db_connection() as conn:
        if entry_type == 'pick':
            conn.execute(
                '''INSERT INTO hero_match_stats (event_name, hero_name, result, created_at)
                   VALUES (?, ?, ?, ?)''',
                (event_name, hero_name, result, datetime.now().isoformat(timespec='seconds'))
            )
        else:
            conn.execute(
                '''INSERT INTO hero_ban_stats (event_name, hero_name, created_at)
                   VALUES (?, ?, ?)''',
                (event_name, hero_name, datetime.now().isoformat(timespec='seconds'))
            )
    activity_detail = f'{event_name}: {hero_name} ({entry_type}'
    if entry_type == 'pick':
        activity_detail += f', {result}'
    log_activity('add_hero_match_stat', f'{activity_detail})')
    flash('Catatan hero berhasil disimpan.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/hero-stats/delete/<entry_type>/<int:id>', methods=['POST'])
def admin_hero_stats_delete(entry_type, id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    if entry_type not in {'pick', 'ban'}:
        abort(404)

    with get_db_connection() as conn:
        table_name = 'hero_match_stats' if entry_type == 'pick' else 'hero_ban_stats'
        cursor = conn.execute(f'DELETE FROM {table_name} WHERE id=?', (id,))

    if cursor.rowcount:
        log_activity('delete_hero_match_stat', id)
        flash('Catatan penggunaan hero berhasil dihapus.', 'success')
    else:
        flash('Catatan penggunaan hero tidak ditemukan.', 'error')
    return redirect(url_for('admin'))


@app.route('/admin/scrim/delete/<int:id>', methods=['POST'])
def admin_scrim_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with get_db_connection() as conn:
        cursor = conn.execute('DELETE FROM scrim_stats WHERE id=?', (id,))

    if cursor.rowcount:
        log_activity('delete_scrim_stat', id)
        flash('Data statistik scrim berhasil dihapus.', 'success')
    else:
        flash('Data statistik scrim tidak ditemukan.', 'error')
    return redirect(url_for('admin'))


@app.route('/admin/match/add', methods=['POST'])
def admin_match_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    title = request.form.get('title', '').strip()
    competition = request.form.get('competition', '').strip()
    match_date = request.form.get('date', '').strip()
    match_time = request.form.get('time', '').strip() or '00:00:00'
    team_home = request.form.get('team_home', '').strip()
    team_away = request.form.get('team_away', '').strip()
    score_home = request.form.get('score_home', '0').strip()
    score_away = request.form.get('score_away', '0').strip()
    status = request.form.get('status', 'upcoming').strip().lower()
    description = request.form.get('description', '').strip()

    if not title or not competition or not match_date or not team_home or not team_away:
        flash('Judul, kompetisi, tanggal, tim rumah, dan tim tandang wajib diisi.', 'error')
        return redirect(url_for('admin'))
    try:
        match_date, match_time = normalize_match_schedule(match_date, match_time)
        score_home, score_away = int(score_home or 0), int(score_away or 0)
        if score_home < 0 or score_away < 0 or status not in {'upcoming', 'finished'}:
            raise ValueError
    except ValueError:
        flash('Tanggal, jam, skor, atau status pertandingan tidak valid.', 'error')
        return redirect(url_for('admin'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute(
            "INSERT INTO matches (title, competition, date, time, team_home, team_away, score_home, score_away, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (title, competition, match_date, match_time, team_home, team_away, score_home, score_away, status, description)
        )
    log_activity('add_match', title)

    flash('Jadwal pertandingan berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/match/edit/<int:id>', methods=['POST'])
def admin_match_edit(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    title = request.form.get('title', '').strip()
    competition = request.form.get('competition', '').strip()
    match_date = request.form.get('date', '').strip()
    match_time = request.form.get('time', '').strip() or '00:00:00'
    team_home = request.form.get('team_home', '').strip()
    team_away = request.form.get('team_away', '').strip()
    score_home = request.form.get('score_home', '0').strip()
    score_away = request.form.get('score_away', '0').strip()
    status = request.form.get('status', 'upcoming').strip().lower()
    description = request.form.get('description', '').strip()

    if not title or not competition or not match_date or not team_home or not team_away:
        flash('Data pertandingan tidak lengkap untuk diupdate.', 'error')
        return redirect(url_for('admin'))
    try:
        match_date, match_time = normalize_match_schedule(match_date, match_time)
        score_home, score_away = int(score_home or 0), int(score_away or 0)
        if score_home < 0 or score_away < 0 or status not in {'upcoming', 'finished'}:
            raise ValueError
    except ValueError:
        flash('Tanggal, jam, skor, atau status pertandingan tidak valid.', 'error')
        return redirect(url_for('admin'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute(
            "UPDATE matches SET title=?, competition=?, date=?, time=?, team_home=?, team_away=?, score_home=?, score_away=?, status=?, description=? WHERE id=?",
            (title, competition, match_date, match_time, team_home, team_away, score_home, score_away, status, description, id)
        )
    log_activity('edit_match', f'{id}: {title}')

    flash('Jadwal pertandingan berhasil diperbarui.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/match/delete/<int:id>', methods=['POST'])
def admin_match_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute('DELETE FROM matches WHERE id=?', (id,))

    log_activity('delete_match', id)
    flash('Pertandingan berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/status/<int:id>', methods=['POST'])
def admin_status_update(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    status = request.form.get('status', 'pending').strip().lower()
    if status not in ['pending', 'review', 'accepted', 'rejected']:
        status = 'pending'

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute('UPDATE pendaftar SET status=? WHERE id=?', (status, id))
    log_activity('update_applicant_status', f'{id}: {status}')

    flash('Status pendaftar berhasil diperbarui.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/gallery/add', methods=['POST'])
def admin_gallery_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    title = request.form.get('title', '').strip()
    description = request.form.get('description', '').strip()
    image = request.form.get('image', '').strip()

    if not title or not image:
        flash('Judul dan nama file gambar wajib diisi.', 'error')
        return redirect(url_for('admin'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute(
            "INSERT INTO gallery (title, image, description) VALUES (?, ?, ?)",
            (title, image, description)
        )
    log_activity('add_gallery_item', title)

    flash('Galeri berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/gallery/delete/<int:id>', methods=['POST'])
def admin_gallery_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute('DELETE FROM gallery WHERE id=?', (id,))
    log_activity('delete_gallery_item', id)

    flash('Item galeri berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/delete/<int:id>', methods=['POST'])
def admin_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute("SELECT foto FROM pendaftar WHERE id=?", (id,))
        row = c.fetchone()
        if row:
            foto = row[0]
            if foto:
                filepath = os.path.join(app.config['UPLOAD_FOLDER'], foto)
                if os.path.exists(filepath):
                    try:
                        os.remove(filepath)
                    except OSError:
                        app.logger.exception('Unable to remove applicant upload %s.', foto)

            c.execute("DELETE FROM pendaftar WHERE id=?", (id,))
    if row:
        log_activity('delete_applicant', id)

    flash('Data berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/export')
def admin_export():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute("SELECT id, nama, role, whatsapp, foto FROM pendaftar")
        rows = c.fetchall()

    si = io.StringIO()
    cw = csv.writer(si)
    cw.writerow(['ID', 'Nama', 'Role', 'WhatsApp', 'Foto'])
    cw.writerows([[protect_csv_cell(value) for value in row] for row in rows])

    output = make_response(si.getvalue())
    output.headers["Content-Disposition"] = "attachment; filename=rekrutmen_namdel.csv"
    output.headers["Content-type"] = "text/csv"
    return output


@app.route('/logout')
def logout():
    if session.get('logged_in'):
        log_activity('logout', 'admin')
    session.pop('logged_in', None)
    session.pop('admin_user', None)
    return redirect(url_for('home'))


@app.route('/admin/ticker/add', methods=['POST'])
def admin_ticker_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    content = request.form.get('content', '').strip()
    if content:
        with get_db_connection() as conn:
            c = conn.cursor()
            c.execute("INSERT INTO tickers (content) VALUES (?)", (content,))
        log_activity('add_ticker', content)
        flash('Ticker berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))

@app.route('/admin/ticker/delete/<int:id>', methods=['POST'])
def admin_ticker_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    with get_db_connection() as conn:
        c = conn.cursor()
        c.execute('DELETE FROM tickers WHERE id=?', (id,))
    log_activity('delete_ticker', id)
    flash('Ticker berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/achievement/add', methods=['POST'])
def admin_achievement_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    title = request.form.get('title', '').strip()
    event = request.form.get('event', '').strip()
    year = request.form.get('year', '').strip()
    icon = request.form.get('icon', '🏆').strip() or '🏆'
    if not title or len(title) > 150 or not event or len(event) > 150 or not year.isdigit() or len(year) != 4:
        flash('Judul, acara, dan tahun prestasi harus diisi dengan benar.', 'error')
        return redirect(url_for('admin'))
    with get_db_connection() as conn:
        conn.execute(
            'INSERT INTO achievements (title, event, year, icon) VALUES (?, ?, ?, ?)',
            (title, event, year, icon[:8])
        )
    log_activity('add_achievement', title)
    flash('Prestasi berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/achievement/delete/<int:id>', methods=['POST'])
def admin_achievement_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    with get_db_connection() as conn:
        cursor = conn.execute('DELETE FROM achievements WHERE id=?', (id,))
    if cursor.rowcount:
        log_activity('delete_achievement', id)
        flash('Prestasi berhasil dihapus.', 'success')
    else:
        flash('Prestasi tidak ditemukan.', 'error')
    return redirect(url_for('admin'))


@app.route('/admin/settings/update', methods=['POST'])
def admin_settings_update():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    key = request.form.get('key', '').strip()
    value = request.form.get('value', '').strip()
    allowed_keys = {'highlight_video_url'}
    if not key or key not in allowed_keys or not value or len(value) > 500:
        flash('Pengaturan tidak valid.', 'error')
        return redirect(url_for('admin'))
    with get_db_connection() as conn:
        conn.execute('INSERT OR REPLACE INTO site_settings (key, value) VALUES (?, ?)', (key, value))
    log_activity('update_setting', f'{key}: {value[:100]}')
    flash('Pengaturan situs berhasil diperbarui.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/roster/edit/<int:id>', methods=['POST'])
def admin_roster_edit(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    name = request.form.get('name', '').strip()
    role = request.form.get('role', '').strip()
    role_tag = request.form.get('role_tag', '').strip()
    description = request.form.get('description', '').strip()
    photo = request.form.get('photo', '').strip()
    if not name or not role:
        flash('Nama dan role pemain wajib diisi.', 'error')
        return redirect(url_for('admin'))
    with get_db_connection() as conn:
        conn.execute(
            '''UPDATE roster SET name=?, role=?, role_tag=?, description=?, photo=? WHERE id=?''',
            (name, role, role_tag, description, photo, id)
        )
    log_activity('edit_roster', f'{id}: {name}')
    flash('Data roster pemain berhasil diperbarui.', 'success')
    return redirect(url_for('admin'))


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=int(os.getenv('PORT', '5000')), debug=False)