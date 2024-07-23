import os
import json
import torch
from transformers import GPT2Tokenizer, GPT2LMHeadModel, TextDataset, DataCollatorForLanguageModeling, Trainer, TrainingArguments

# Load the GPT-2 tokenizer
tokenizer = GPT2Tokenizer.from_pretrained("gpt2")

# Load and preprocess the corpus data
def preprocess_corpus(corpus_file):
    with open(corpus_file, 'r', encoding='utf-8') as f:
        data = f.read()
    return data

# Load and preprocess the corpus
corpus_file = r"C:\Users\prash\OneDrive\Desktop\Sky_trade\Corpus.txt"
preprocessed_data = preprocess_corpus(corpus_file)

# Save preprocessed data to a text file
with open('corpus.txt', 'w', encoding='utf-8') as f:
    f.write(preprocessed_data)

# Prepare the dataset for training
def load_dataset(file_path, tokenizer):
    return TextDataset(
        tokenizer=tokenizer,
        file_path=file_path,
        block_size=128
    )

dataset = load_dataset('corpus.txt', tokenizer)
data_collator = DataCollatorForLanguageModeling(
    tokenizer=tokenizer,
    mlm=False,
)

# Initialize the GPT-2 model
model = GPT2LMHeadModel.from_pretrained("gpt2")

# Training arguments
training_args = TrainingArguments(
    output_dir=r"C:\Users\prash\OneDrive\Desktop\Sky_trade\gpt2-finetuned",
    overwrite_output_dir=True,
    num_train_epochs=50,
    per_device_train_batch_size=1,
    save_steps=10_000,
    save_total_limit=2,
)

# Create Trainer instance
trainer = Trainer(
    model=model,
    args=training_args,
    data_collator=data_collator,
    train_dataset=dataset,
)

# Fine-tune the model
trainer.train()

# Save the fine-tuned model
model.save_pretrained(r"C:\Users\prash\OneDrive\Desktop\Sky_trade\gpt2-finetuned")
tokenizer.save_pretrained(r"C:\Users\prash\OneDrive\Desktop\Sky_trade\gpt2-finetuned")
print("Model and tokenizer saved.")
