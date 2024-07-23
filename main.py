import sys
import torch
from transformers import GPT2Tokenizer, GPT2LMHeadModel

# Load the fine-tuned model and tokenizer
model_path = r"C:\xampp\htdocs\Sky_trade\gpt2-finetuned"
tokenizer = GPT2Tokenizer.from_pretrained(model_path)
model = GPT2LMHeadModel.from_pretrained(model_path)

# Function to generate an answer
def generate_answer(question, model, tokenizer):
    # Prepare the input text
    input_text = f"Question: {question}\nAnswer:"
    inputs = tokenizer.encode(input_text, return_tensors="pt")
    
    # Create the attention mask
    attention_mask = torch.ones(inputs.shape, dtype=torch.long)
    
    # Generate the answer
    outputs = model.generate(
        inputs,
        attention_mask=attention_mask,
        max_length=50,
        num_return_sequences=1,
        no_repeat_ngram_size=2,
        top_k=50,
        top_p=0.95,
        temperature=0.7,
        pad_token_id=tokenizer.eos_token_id,  # Set pad_token_id to eos_token_id
        eos_token_id=tokenizer.eos_token_id
    )
    
    # Decode the generated answer
    answer = tokenizer.decode(outputs[0], skip_special_tokens=True)
    
    # Extract the answer part from the generated text
    answer = answer.split("Answer:")[1].strip()
    
    # Return the text until the first full stop
    return answer.split('.')[0] + '.'

# Get the user message from the command line argument
if len(sys.argv) > 1:
    question = sys.argv[1]
    answer = generate_answer(question, model, tokenizer)
    print(answer)
else:
    print("No question provided.")
