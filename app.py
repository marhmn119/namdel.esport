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
    if request.method == 'POST':
        name = request.form['name']
        game_role = request.form['role']
        contact = request.form['contact']
        
        # Cetak data pendaftar di terminal
        print(f"Pendaftar Baru: {name} | Role: {game_role} | Kontak: {contact}")
        
        flash("Pendaftaran berhasil dikirim! Tunggu info selanjutnya.")
        return redirect(url_for('home'))

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0')