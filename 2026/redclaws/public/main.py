from flask import Flask, request, render_template, redirect
import os
import logging

logging.basicConfig(level=logging.DEBUG,
                    format='%(asctime)s- %(levelname)s -%(message)s')

app = Flask(__name__)
FOOD_DIR = "/app/food"
os.makedirs(FOOD_DIR, exist_ok=True)

@app.route('/')
def index():
    food_list = []
    try:
        for fname in sorted(os.listdir(FOOD_DIR)):
            full_path = os.path.join(FOOD_DIR, fname)
            if os.path.isfile(full_path):
                try:
                    with open(full_path, 'r') as f:
                        content = f.read().strip()

                    candidate_path = os.path.join(FOOD_DIR, content)
                    if os.path.isfile(candidate_path):
                        try:
                            with open(candidate_path, 'r') as real_file:
                                real_content = real_file.read().strip()
                                display = real_content
                                logging.debug(f"{fname} points to file {candidate_path}, displaying its contents.")
                        except Exception as e:
                            display = f"[Error reading pointed file: {e}]"
                    else:
                        display = content

                    food_list.append((fname, display))
                except Exception as e:
                    logging.error(f"Error reading {full_path}: {e}")
                    food_list.append((fname, f"[Error: {e}]"))
    except Exception as e:
        logging.error(f"Failed to list foods: {e}")
    return render_template('index.html', food_list=food_list)


@app.route('/add', methods=['POST'])
def add_food():
    food_name = request.form['name'].strip().replace(',', '')
    try:
        existing = [f for f in os.listdir(FOOD_DIR) if f.startswith("food")]
        indexes = [int(f[4:]) for f in existing if f[4:].isdigit()]
        next_index = max(indexes, default=0) + 1
        new_file = os.path.join(FOOD_DIR, f"food{next_index}")

        with open(new_file, 'w') as f:
            f.write(food_name)

        logging.debug(f"Added food: {new_file} = {food_name}")
    except Exception as e:
        logging.error(f"Failed to add food: {e}")
        return f"Error: {e}"
    return redirect('/')

@app.route('/delete', methods=['POST'])
def delete_food():
    filename = request.form.get('filename', '').strip()
    if not filename.startswith("food"):
        logging.warning(f"Blocked attempt to delete invalid file: {filename}")
        return "Invalid file name", 400

    file_path = os.path.join(FOOD_DIR, filename)
    try:
        if os.path.exists(file_path):
            os.remove(file_path)
            logging.debug(f"Deleted food file: {file_path}")
        else:
            logging.warning(f"Tried to delete nonexistent file: {file_path}")
    except Exception as e:
        logging.error(f"Failed to delete {file_path}: {e}")
        return f"Error: {e}", 500
    return redirect('/')
