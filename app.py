from flask import Flask, render_template, request, redirect, url_for, flash

app = Flask(__name__)
app.secret_key = "namdel_rahasia"

# Data jadwal pertandingan
schedules = [
    {"date": "25 Sep 2026", "match": "NAMDEL vs SMAN 1", "game": "Mobile Legends", "time": "15:00 WIB"},
    {"date": "28 Sep 2026", "match": "NAMDEL vs SMK 2", "game": "Mobile Legends", "time": "16:30 WIB"},
]

@app.route('/')
def home():
    return render_template('index.html', schedules=schedules)

@app.route('/register', methods=['POST'])
def register():
    # Mengambil data dari form web
    nama = request.form.get('nama')
    role = request.form.get('role')
    whatsapp = request.form.get('whatsapp') # <-- pastikan ini 'whatsapp' sesuai HTML
    
    print(f"=== PENDAFTAR BARU NAMDEL ESPORT ===")
    print(f"Nama: {nama}")
    print(f"Role: {role}")
    print(f"No WA: {whatsapp}")
    print(f"==================================")
    
    return redirect(url_for('home'))
if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0')