import os

# CHANGE THIS PATH to your BeriBakes project folder
PROJECT_PATH = r"C:\xampp\htdocs\beribakes"

OUTPUT_FILE = "beribakes_structure.txt"


def get_structure(start_path):
    structure = []

    for root, dirs, files in os.walk(start_path):
        level = root.replace(start_path, "").count(os.sep)
        indent = "│   " * level
        folder = os.path.basename(root)

        structure.append(f"{indent}├── {folder}/")

        sub_indent = "│   " * (level + 1)

        for file in files:
            structure.append(f"{sub_indent}├── {file}")

    return structure


def save_structure():
    structure = get_structure(PROJECT_PATH)

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        f.write("\n".join(structure))

    print(f"\n✅ Folder structure saved to {OUTPUT_FILE}")


if __name__ == "__main__":
    save_structure()