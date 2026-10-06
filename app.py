from flask import Flask, render_template, request, redirect, url_for, session, flash, make_response
from flask_wtf.csrf import CSRFProtect
from werkzeug.utils import secure_filename
import os
import sqlite3
from dotenv import load_dotenv
import uuid
import csv
import io
from datetime import datetime
import requests

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', 'kunci_cadangan')
csrf = CSRFProtect(app)

ADMIN_USER = os.getenv('ADMIN_USER')
ADMIN_PASS = os.getenv('ADMIN_PASS')

UPLOAD_FOLDER = 'static/uploads'
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 5 * 1024 * 1024
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png', 'gif', 'webp'}
ALLOWED_ROLES = ['jungler', 'roamer', 'mid laner', 'gold laner', 'exp laner', 'coach/manager']

month_map = {
    'Jan': 'JAN', 'Feb': 'FEB', 'Mar': 'MAR', 'Apr': 'APR', 'May': 'MEI', 'Jun': 'JUN',
    'Jul': 'JUL', 'Aug': 'AGU', 'Sep': 'SEP', 'Oct': 'OKT', 'Nov': 'NOV', 'Dec': 'DES'
}


def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


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


def init_db():
    with sqlite3.connect('namdel.db') as conn:
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
        c.execute('''CREATE TABLE IF NOT EXISTS news
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      title TEXT,
                      content TEXT,
                      image TEXT,
                      created_at TEXT DEFAULT CURRENT_DATE)''')
        c.execute('''CREATE TABLE IF NOT EXISTS gallery
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      title TEXT,
                      image TEXT,
                      description TEXT,
                      created_at TEXT DEFAULT CURRENT_DATE)''')
        c.execute('''CREATE TABLE IF NOT EXISTS tickers
                     (id INTEGER PRIMARY KEY AUTOINCREMENT,
                      content TEXT)''')

        pendaftar_cols = [row[1] for row in c.execute('PRAGMA table_info(pendaftar)').fetchall()]
        if 'status' not in pendaftar_cols:
            c.execute('ALTER TABLE pendaftar ADD COLUMN status TEXT DEFAULT "pending"')

        count = c.execute('SELECT COUNT(*) FROM matches').fetchone()[0]
        if count == 0:
            default_matches = [
                ('RRQ MABAR', 'Turnamen Pelajar', '2026-09-26', '11:30:00', 'NEST', 'RRQ MABAR', 0, 0, 'upcoming', 'Laga pembuka musim turnamen pelajar.'),
                ('ESI JAKARTA', 'Turnamen Pelajar', '2026-10-05', '18:00:00', 'NEST', 'ESI JAKARTA', 0, 0, 'upcoming', 'Pertandingan penting untuk melangkah ke babak berikutnya.'),
                ('LIGA ESPORT NASIONAL', 'Liga Sekolah', '2026-10-20', '16:00:00', 'NEST', 'SMK BINTANG', 2, 1, 'finished', 'Hasil pertandingan terakhir dimenangkan NEST.')
            ]
            c.executemany(
                "INSERT INTO matches (title, competition, date, time, team_home, team_away, score_home, score_away, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                default_matches
            )

        if c.execute('SELECT COUNT(*) FROM news').fetchone()[0] == 0:
            c.execute(
                "INSERT INTO news (title, content, image) VALUES (?, ?, ?)",
                ('Latihan Intensif Persiapan Turnamen', 'Tim NEST kembali melaksanakan latihan intensif untuk memperbaiki strategi dan komunikasi antar pemain menjelang turnamen berikutnya.', 'logonest.png')
            )

        if c.execute('SELECT COUNT(*) FROM gallery').fetchone()[0] == 0:
            default_gallery = [
                ('Briefing Tim Sebelum Turnamen', 'dokumentasi.png', 'Persiapan strategi sebelum bertanding.'),
                ('Pertemuan Scrim', 'Pertemuan scrim.png', 'Diskusi taktik bersama tim.'),
                ('Penyerahan Arbook', 'artbook kepkse.png', 'Dokumentasi kegiatan pelajar.'),
                ('Foto Tim NEST', 'artbook.png', 'Komunitas e-sports sekolah yang solid.')
            ]
            c.executemany(
                "INSERT INTO gallery (title, image, description) VALUES (?, ?, ?)",
                default_gallery
            )
            
        if c.execute('SELECT COUNT(*) FROM tickers').fetchone()[0] == 0:
            default_tickers = [
                ('OPEN RECRUITMENT SEASON 2026 - DAFTAR SEKARANG!',),
                ('NEST JUARA RRQ MABAR 2024',),
                ('SCRIM RUTIN SETIAP SABTU & MINGGU',),
                ('FOLLOW INSTAGRAM @nest.esports UNTUK UPDATE TERBARU',),
                ('TURNAMEN ESI JAKARTA - OKTOBER 2026',)
            ]
            c.executemany("INSERT INTO tickers (content) VALUES (?)", default_tickers)

        conn.commit()


init_db()


@app.route('/')
def home():
    with sqlite3.connect('namdel.db') as conn:
        conn.row_factory = sqlite3.Row
        matches = conn.execute("SELECT * FROM matches ORDER BY date ASC, time ASC").fetchall()
        gallery = conn.execute("SELECT * FROM gallery ORDER BY id DESC").fetchall()
        news = conn.execute("SELECT * FROM news ORDER BY id DESC").fetchall()
        tickers = conn.execute("SELECT * FROM tickers ORDER BY id ASC").fetchall()
    return render_template('index.html', matches=matches, gallery=gallery, news=news, tickers=tickers)


@app.route('/register', methods=['POST'])
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

        if not allowed_file(foto_file.filename):
            flash('Ekstensi file tidak diizinkan. Hanya boleh jpg, jpeg, png, gif, webp.', 'error')
            return redirect(url_for('home'))

        ext = foto_file.filename.rsplit('.', 1)[1].lower()
        unique_filename = f"{uuid.uuid4().hex}.{ext}"
        foto_file.save(os.path.join(app.config['UPLOAD_FOLDER'], unique_filename))

        with sqlite3.connect('namdel.db') as conn:
            c = conn.cursor()
            c.execute(
                "INSERT INTO pendaftar (nama, role, whatsapp, foto, status) VALUES (?, ?, ?, ?, 'pending')",
                (nama, role, whatsapp, unique_filename)
            )
            conn.commit()

        # Send Webhook Notification (Discord/Telegram)
        webhook_url = os.getenv('WEBHOOK_URL')
        if webhook_url:
            try:
                payload = {
                    "content": f"🚨 **PENDAFTAR BARU NAMDEL ESPORT!** 🚨\n\n**Nama**: {nama}\n**Role**: {role}\n**WhatsApp**: {whatsapp}\n\n*Silakan cek dashboard admin untuk memverifikasi bukti rank.*"
                }
                requests.post(webhook_url, json=payload, timeout=5)
            except Exception as e:
                print("Webhook failed:", e)

        flash('Mantap! Pendaftaran berhasil.', 'success')
        return redirect(url_for('home'))


@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        user = request.form.get('username')
        password = request.form.get('password')

        if user == ADMIN_USER and password == ADMIN_PASS:
            session['logged_in'] = True
            return redirect(url_for('admin'))
        else:
            flash('Username atau Password salah!', 'error')
            return redirect(url_for('login'))

    return render_template('login.html')


@app.route('/admin')
def admin():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with sqlite3.connect('namdel.db') as conn:
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

        c.execute("SELECT * FROM news ORDER BY id DESC")
        news = c.fetchall()

        c.execute("SELECT * FROM gallery ORDER BY id DESC")
        gallery = c.fetchall()

        c.execute("SELECT * FROM tickers ORDER BY id ASC")
        tickers = c.fetchall()

    return render_template(
        'admin.html',
        pendaftar=data_pendaftar,
        total_pendaftar=total_pendaftar,
        stats_per_role=stats_per_role,
        pendaftar_terbaru=pendaftar_terbaru,
        matches=matches,
        news=news,
        gallery=gallery,
        tickers=tickers
    )


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

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute(
            "INSERT INTO matches (title, competition, date, time, team_home, team_away, score_home, score_away, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (title, competition, match_date, match_time, team_home, team_away, int(score_home or 0), int(score_away or 0), status, description)
        )
        conn.commit()

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

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute(
            "UPDATE matches SET title=?, competition=?, date=?, time=?, team_home=?, team_away=?, score_home=?, score_away=?, status=?, description=? WHERE id=?",
            (title, competition, match_date, match_time, team_home, team_away, int(score_home or 0), int(score_away or 0), status, description, id)
        )
        conn.commit()

    flash('Jadwal pertandingan berhasil diperbarui.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/match/delete/<int:id>', methods=['POST'])
def admin_match_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute('DELETE FROM matches WHERE id=?', (id,))
        conn.commit()

    flash('Pertandingan berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/status/<int:id>', methods=['POST'])
def admin_status_update(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    status = request.form.get('status', 'pending').strip().lower()
    if status not in ['pending', 'review', 'accepted', 'rejected']:
        status = 'pending'

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute('UPDATE pendaftar SET status=? WHERE id=?', (status, id))
        conn.commit()

    flash('Status pendaftar berhasil diperbarui.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/news/add', methods=['POST'])
def admin_news_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    title = request.form.get('title', '').strip()
    content = request.form.get('content', '').strip()
    image = request.form.get('image', '').strip()

    if not title or not content:
        flash('Judul dan isi berita wajib diisi.', 'error')
        return redirect(url_for('admin'))

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute(
            "INSERT INTO news (title, content, image) VALUES (?, ?, ?)",
            (title, content, image or 'logonest.png')
        )
        conn.commit()

    flash('Berita baru berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/news/delete/<int:id>', methods=['POST'])
def admin_news_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute('DELETE FROM news WHERE id=?', (id,))
        conn.commit()

    flash('Berita berhasil dihapus.', 'success')
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

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute(
            "INSERT INTO gallery (title, image, description) VALUES (?, ?, ?)",
            (title, image, description)
        )
        conn.commit()

    flash('Galeri berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/gallery/delete/<int:id>', methods=['POST'])
def admin_gallery_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute('DELETE FROM gallery WHERE id=?', (id,))
        conn.commit()

    flash('Item galeri berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/delete/<int:id>', methods=['POST'])
def admin_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with sqlite3.connect('namdel.db') as conn:
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
                    except Exception:
                        pass

            c.execute("DELETE FROM pendaftar WHERE id=?", (id,))
            conn.commit()

    flash('Data berhasil dihapus.', 'success')
    return redirect(url_for('admin'))


@app.route('/admin/export')
def admin_export():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute("SELECT id, nama, role, whatsapp, foto FROM pendaftar")
        rows = c.fetchall()

    si = io.StringIO()
    cw = csv.writer(si)
    cw.writerow(['ID', 'Nama', 'Role', 'WhatsApp', 'Foto'])
    cw.writerows(rows)

    output = make_response(si.getvalue())
    output.headers["Content-Disposition"] = "attachment; filename=rekrutmen_namdel.csv"
    output.headers["Content-type"] = "text/csv"
    return output


@app.route('/logout')
def logout():
    session.pop('logged_in', None)
    return redirect(url_for('home'))


@app.route('/admin/ticker/add', methods=['POST'])
def admin_ticker_add():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    content = request.form.get('content', '').strip()
    if content:
        with sqlite3.connect('namdel.db') as conn:
            c = conn.cursor()
            c.execute("INSERT INTO tickers (content) VALUES (?)", (content,))
            conn.commit()
        flash('Ticker berhasil ditambahkan.', 'success')
    return redirect(url_for('admin'))

@app.route('/admin/ticker/delete/<int:id>', methods=['POST'])
def admin_ticker_delete(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute('DELETE FROM tickers WHERE id=?', (id,))
        conn.commit()
    flash('Ticker berhasil dihapus.', 'success')
    return redirect(url_for('admin'))

if __name__ == '__main__':
    app.run(debug=True)