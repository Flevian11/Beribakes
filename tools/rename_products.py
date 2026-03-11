import os
import random

# CHANGE THIS PATH IF NEEDED
folder = r"C:\xampp\htdocs\beribakes\uploads\products"

# filenames expected by the database
db_images = [
    "chocolate-cake.jpg",
    "cupcake.jpg",
    "fresh-bread.jpg",
    "croissant.jpg",
    "donut.jpg",
    "muffin.jpg",
    "strawberry-cake.jpg",
    "banana-bread.jpg"
]

# get all images in folder
files = [f for f in os.listdir(folder) if f.lower().endswith((".jpg",".jpeg",".png"))]

# shuffle files randomly
random.shuffle(files)

# take first 8 images
selected = files[:len(db_images)]

print("Selected images:")
for f in selected:
    print(" -", f)

# rename them to db names
for old, new in zip(selected, db_images):
    old_path = os.path.join(folder, old)
    new_path = os.path.join(folder, new)

    os.rename(old_path, new_path)

    print(f"Renamed {old} -> {new}")

print("\nDone. Images now match the database.")