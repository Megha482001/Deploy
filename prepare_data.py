import os
import fitz  # PyMuPDF

# Function to convert a PDF file to text
def convert_pdf_to_text(pdf_path):
    try:
        document = fitz.open(pdf_path)
        text = ""
        for page_num in range(len(document)):
            page = document.load_page(page_num)
            text += page.get_text()
        return text
    except Exception as e:
        print(f"Error processing {pdf_path}: {e}")
        return ""

# Folder containing PDF files
pdf_folder = r"C:\Users\prash\OneDrive\Desktop\pdfs"  # Replace with your folder path

# Output text file
output_txt_file = "corpus.txt"

# Collect text from all PDFs in the folder
combined_text = ""
for filename in os.listdir(pdf_folder):
    if filename.endswith(".pdf"):
        pdf_path = os.path.join(pdf_folder, filename)
        pdf_text = convert_pdf_to_text(pdf_path)
        combined_text += pdf_text + "\n\n"

# Write the combined text to the output file
with open(output_txt_file, 'w', encoding='utf-8') as f:
    f.write(combined_text)

print(f"All PDFs have been converted and combined into {output_txt_file}")
