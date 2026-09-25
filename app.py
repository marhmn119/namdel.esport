from flask import Flask, render_template, request, redirect, url_for, session, flash, make_response
from flask_wtf.csrf import CSRFProtect
from werkzeug.utils import secure_filename
import os
import sqlite3
from dotenv import load_dotenv
import uuid
import csv
import io

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', 'kunci_cadangan')
csrf = CSRFProtect(app)

ADMIN_USER = os.getenv('ADMIN_USER')
ADMIN_PASS = os.getenv('ADMIN_PASS')

# Pengaturan folder dan file upload
UPLOAD_FOLDER = 'static/uploads'
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 5 * 1024 * 1024  # Maksimal ukuran file 5MB
os.makedirs(UPLOAD_FOLDER, exist_ok=True) # Otomatis bikin folder kalau belum ada

ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png', 'gif', 'webp'}
ALLOWED_ROLES = ['jungler', 'roamer', 'mid laner', 'gold laner', 'exp laner', 'coach/manager']

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

# FUNGSI MEMBUAT DATABASE
def init_db():
    with sqlite3.connect('namdel.db') as conn:
        c = conn.cursor()
        c.execute('''CREATE TABLE IF NOT EXISTS pendaftar 
                     (id INTEGER PRIMARY KEY AUTOINCREMENT, 
                      nama TEXT, role TEXT, whatsapp TEXT, foto TEXT)''')
        conn.commit()

init_db()

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/register', methods=['POST'])
def register():
    if request.method == 'POST':
        nama = request.form.get('nama', '').strip()
        role = request.form.get('role', '').strip()
        whatsapp = request.form.get('whatsapp', '').strip()
        
        # Validasi input
        if not nama or len(nama) > 100:
            flash('Nama tidak boleh kosong dan maksimal 100 karakter.', 'error')
            return redirect(url_for('home'))
            
        if not validate_whatsapp(whatsapp):
            flash('Format WhatsApp tidak valid. Harus diawali 08 atau 628 (10-15 digit).', 'error')
            return redirect(url_for('home'))
            
        if not role or role.lower() not in ALLOWED_ROLES:
            flash('Role yang dipilih tidak valid.', 'error')
            return redirect(url_for('home'))
            
        # PROSES UPLOAD FOTO
        foto_file = request.files.get('foto_rank')
        if not foto_file or foto_file.filename == '':
            flash('Foto bukti rank wajib diunggah.', 'error')
            return redirect(url_for('home'))
            
        if not allowed_file(foto_file.filename):
            flash('Ekstensi file tidak diizinkan. Hanya boleh jpg, jpeg, png, gif, webp.', 'error')
            return redirect(url_for('home'))
            
        # Mengamankan nama file dan membuat unique filename
        ext = foto_file.filename.rsplit('.', 1)[1].lower()
        unique_filename = f"{uuid.uuid4().hex}.{ext}"
        foto_file.save(os.path.join(app.config['UPLOAD_FOLDER'], unique_filename))
        
        # Simpan ke Database
        with sqlite3.connect('namdel.db') as conn:
            c = conn.cursor()
            c.execute("INSERT INTO pendaftar (nama, role, whatsapp, foto) VALUES (?, ?, ?, ?)", 
                      (nama, role, whatsapp, unique_filename))
            conn.commit()
        
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
    
    return render_template('admin.html', 
                           pendaftar=data_pendaftar,
                           total_pendaftar=total_pendaftar,
                           stats_per_role=stats_per_role,
                           pendaftar_terbaru=pendaftar_terbaru)

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

if __name__ == '__main__':
    app.run(debug=True)