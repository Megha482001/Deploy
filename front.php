<?php
// Handling the backend processing for the chatbot
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
 
    // Read the input JSON data
    $input = json_decode(file_get_contents("php://input"), true);

    // Check if the input data is properly decoded and the 'message' key exists
    if (is_null($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input: JSON data not decoded']);
        exit;
    }

    if (!isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input: message key missing']);
        exit;
    }

    $userMessage = $input['message'];

    // Save the user message to a temporary file
    file_put_contents("user_message.txt", $userMessage);

    // Call the Python script and capture the output
    $command = escapeshellcmd('python chatbot_model.py');
    $output = shell_exec($command);

    if ($output === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate response']);
        exit;
    }

    echo json_encode(['response' => trim($output)]);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skytrade Bot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden; /* Prevent overflow */
            background-image: url('bg.jpg'); /* Background image */
            background-size: cover; /* Cover the entire viewport */
            background-position: center; /* Center the image */
        }

        .drone {
            position: absolute;
            width: 100px; /* Size of the drone */
            height: 100px;
            background-image: url('drone.png'); /* Your drone image */
            background-size: contain;
            background-repeat: no-repeat;
        }

        .chat-container {
            width: 67vw; /* 67% of viewport width */
            height: 80vh; /* Fixed height */
            border: 1px solid #ccc;
            border-radius: 20px; /* More curvature */
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent white background */
            z-index: 1; /* Ensure chat is above the drones */
        }

        .chat-header {
            background: #ffffff;
            text-align: center;
            padding: 10px;
            border-top-left-radius: 20px; /* Curved corners */
            border-top-right-radius: 20px; /* Curved corners */
        }

        .chat-header img {
            max-width: 100%;
            height: auto;
        }

        .chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: hsl(182, 100%, 95%);
            display: flex;
            flex-direction: column;
            gap: 10px; /* Space between messages */
        }

        .input-box {
            display: flex;
            border-top: 1px solid #ccc;
            border-bottom-left-radius: 20px; /* Curved corners */
            border-bottom-right-radius: 20px; /* Curved corners */
        }

        .input-box input {
            flex: 1;
            padding: 10px;
            border: none;
            outline: none;
            height: 50px;
            border-top-left-radius: 20px; /* Curved corners */
            border-bottom-left-radius: 20px; /* Curved corners */
        }

        .input-box button {
            padding: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            width: 100px; /* Adjusted width */
            border-top-right-radius: 20px; /* Curved corners */
            border-bottom-right-radius: 20px; /* Curved corners */
        }

        .input-box button:hover {
            background: #0056b3;
        }

        .message {
            padding: 10px;
            border-radius: 15px; /* Curved message corners */
            max-width: 75%;
            word-wrap: break-word;
            display: inline-block; /* Allow wrapping */
        }

        .message.user {
            background: #007bff;
            color: white;
            align-self: flex-end; /* Align user messages to the right */
        }

        .message.bot {
            background: #333; /* Darker background for better contrast */
            color: #ffffff; /* Bot message color */
            align-self: flex-start; /* Align bot messages to the left */
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <img src="logo.jpeg" alt="Skytrade Logo" />
        </div>
        <div class="chat-box" id="chat-box">
            <!-- Messages will be appended here -->
        </div>
        <div class="input-box">
            <input type="text" id="user-input" placeholder="Type a message..." />
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script>
        const drones = [];
        const chatContainer = document.querySelector('.chat-container');
        let droneBehindChat = null;

        function setupDrone(drone, startX, startY, endX, endY, duration) {
            drone.style.left = `${startX}px`;
            drone.style.top = `${startY}px`;

            drone.style.setProperty('--start-x', `${startX}px`);
            drone.style.setProperty('--start-y', `${startY}px`);
            drone.style.setProperty('--end-x', `${endX}px`);
            drone.style.setProperty('--end-y', `${endY}px`);

            drone.style.animation = `fly ${duration}s linear infinite`;

            const animation = drone.animate([
                { transform: 'translate(0, 0)' },
                { transform: `translate(${endX - startX}px, ${endY - startY}px)` }
            ], {
                duration: duration * 1000,
                iterations: Infinity,
                easing: 'linear'
            });

            monitorDrone(drone, animation, duration);
        }

        function monitorDrone(drone, animation, duration) {
            setInterval(() => {
                const droneRect = drone.getBoundingClientRect();
                const chatRect = chatContainer.getBoundingClientRect();
                const isBehindChat = (
                    droneRect.left < chatRect.right &&
                    droneRect.right > chatRect.left &&
                    droneRect.top < chatRect.bottom &&
                    droneRect.bottom > chatRect.top
                );

                if (isBehindChat) {
                    if (droneBehindChat === null) {
                        droneBehindChat = drone;
                        animation.updatePlaybackRate(3); // Speed up by 3 times

                        setTimeout(() => {
                            if (droneBehindChat === drone) {
                                moveDroneOutOfChat(drone, animation, duration);
                            }
                        }, 2000); // 2 seconds
                    } else if (droneBehindChat !== drone) {
                        moveDroneOutOfChat(drone, animation, duration);
                    }
                } else if (droneBehindChat === drone) {
                    droneBehindChat = null;
                    animation.updatePlaybackRate(1); // Normal speed
                }
            }, 100);
        }

        function moveDroneOutOfChat(drone, animation, duration) {
            const newStartX = Math.random() * (window.innerWidth - 100);
            const newStartY = Math.random() * (window.innerHeight - 100);
            const newEndX = Math.random() * (window.innerWidth - 100);
            const newEndY = Math.random() * (window.innerHeight - 100);

            drone.style.left = `${newStartX}px`;
            drone.style.top = `${newStartY}px`;

            drone.style.setProperty('--start-x', `${newStartX}px`);
            drone.style.setProperty('--start-y', `${newStartY}px`);
            drone.style.setProperty('--end-x', `${newEndX}px`);
            drone.style.setProperty('--end-y', `${newEndY}px`);

            animation.cancel();
            drone.style.animation = `fly ${duration}s linear infinite`;

            setupDrone(drone, newStartX, newStartY, newEndX, newEndY, duration);
            droneBehindChat = null;
        }

        // Initialize 3 drones
        for (let i = 0; i < 3; i++) {
            const drone = document.createElement('div');
            drone.classList.add('drone');
            document.body.appendChild(drone);
            drones.push(drone);

            const startX = Math.random() * (window.innerWidth - 100);
            const startY = Math.random() * (window.innerHeight - 100);
            const endX = Math.random() * (window.innerWidth - 100);
            const endY = Math.random() * (window.innerHeight - 100);
            const duration = Math.random() * 4 + 22; // Random duration between 40s and 80s

            setupDrone(drone, startX, startY, endX, endY, duration);
        }

        async function sendMessage() {
            const userInput = document.getElementById("user-input").value;
            if (!userInput) return;

            const chatBox = document.getElementById("chat-box");

            // Display user message
            const userMessage = document.createElement("div");
            userMessage.classList.add("message", "user");
            userMessage.innerText = userInput;
            chatBox.appendChild(userMessage);
            
            document.getElementById("user-input").value = "";

            try {
                // Send user message to backend
                const response = await fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ message: userInput }),
                });

                const result = await response.json();

                if (response.ok) {
                    // Display bot response
                    const botMessage = document.createElement("div");
                    botMessage.classList.add("message", "bot");
                    botMessage.innerText = result.response;
                    chatBox.appendChild(botMessage);
                } else {
                    console.error('Error:', result.error);
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            }

            // Scroll to the bottom of the chat
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Add event listener for 'Enter' key press
        document.getElementById("user-input").addEventListener("keypress", function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent form submission
                sendMessage();
            }
        });
    </script>
</body>
</html>

