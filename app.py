from flask import Flask, render_template, request, redirect, url_for, session
from flask_wtf.csrf import CSRFProtect
from werkzeug.utils import secure_filename
import os
import sqlite3
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', 'kunci_cadangan')
csrf = CSRFProtect(app)

ADMIN_USER = os.getenv('ADMIN_USER')
ADMIN_PASS = os.getenv('ADMIN_PASS')

# Pengaturan folder untuk menyimpan foto upload
UPLOAD_FOLDER = 'static/uploads'
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
os.makedirs(UPLOAD_FOLDER, exist_ok=True) # Otomatis bikin folder kalau belum ada

# FUNGSI MEMBUAT DATABASE (Sekarang ada tambahan kolom 'foto')
def init_db():
    conn = sqlite3.connect('namdel.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS pendaftar 
                 (id INTEGER PRIMARY KEY AUTOINCREMENT, 
                  nama TEXT, role TEXT, whatsapp TEXT, foto TEXT)''')
    conn.commit()
    conn.close()

init_db()

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/register', methods=['POST'])
def register():
    if request.method == 'POST':
        nama = request.form.get('nama')
        role = request.form.get('role')
        whatsapp = request.form.get('whatsapp')
        
        # PROSES UPLOAD FOTO
        foto_file = request.files.get('foto_rank')
        filename = ""
        if foto_file:
            # Mengamankan nama file dan menyimpannya ke folder static/uploads
            filename = secure_filename(foto_file.filename)
            foto_file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
        
        # Simpan ke Database
        conn = sqlite3.connect('namdel.db')
        c = conn.cursor()
        c.execute("INSERT INTO pendaftar (nama, role, whatsapp, foto) VALUES (?, ?, ?, ?)", (nama, role, whatsapp, filename))
        conn.commit()
        conn.close()
        
        return redirect('/#join')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        user = request.form.get('username')
        password = request.form.get('password')
        
        if user == ADMIN_USER and password == ADMIN_PASS:
            session['logged_in'] = True
            return redirect(url_for('admin'))
        else:
            return "<script>alert('Gagal! Username atau Password salah.'); window.location.href='/login';</script>"
            
    return render_template('login.html')

@app.route('/admin')
def admin():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    
    conn = sqlite3.connect('namdel.db')
    c = conn.cursor()
    c.execute("SELECT * FROM pendaftar")
    data_pendaftar = c.fetchall()
    conn.close()
    
    return render_template('admin.html', pendaftar=data_pendaftar)

@app.route('/logout')
def logout():
    session.pop('logged_in', None)
    return redirect(url_for('home'))

if __name__ == '__main__':
    app.run(debug=True)
