<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
 
    $input = json_decode(file_get_contents("php://input"), true);

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

    file_put_contents("user_message.txt", $userMessage);

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
            overflow: hidden; 
            background-image: url('bg.jpg');
            background-size: cover; 
            background-position: center;
        }

        .drone {
            position: absolute;
            width: 100px; 
            height: 100px;
            background-image: url('drone.png');
            background-size: contain;
            background-repeat: no-repeat;
        }

        .chat-container {
            width: 52vw; 
            height: 80vh; 
            border: 1px solid #ccc;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9); 
            z-index: 1; 
        }

        .chat-header {
            background: #ffffff;
            text-align: center;
            padding: 10px;
            border-top-left-radius: 20px; 
            border-top-right-radius: 20px; 
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
            border-bottom-left-radius: 20px; 
            border-bottom-right-radius: 20px;
        }

        .input-box input {
            flex: 1;
            padding: 10px;
            border: none;
            outline: none;
            height: 50px;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px; 
        }

        .input-box button {
            padding: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            width: 100px; 
            border-top-right-radius: 20px; 
            border-bottom-right-radius: 20px; 
        }

        .input-box button:hover {
            background: #0056b3;
        }

        .message {
            padding: 10px;
            border-radius: 15px; 
            max-width: 75%;
            word-wrap: break-word;
            display: inline-block;
        }

        .message.user {
            background: #007bff;
            color: white;
            align-self: flex-end;
        }

        .message.bot {
            background: #333; 
            color: #ffffff; 
            align-self: flex-start; 
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <img src="logo.jpeg" alt="Skytrade Logo" />
        </div>
        <div class="chat-box" id="chat-box">
            
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
                        animation.updatePlaybackRate(3); 

                        setTimeout(() => {
                            if (droneBehindChat === drone) {
                                moveDroneOutOfChat(drone, animation, duration);
                            }
                        }, 2000); 
                    } else if (droneBehindChat !== drone) {
                        moveDroneOutOfChat(drone, animation, duration);
                    }
                } else if (droneBehindChat === drone) {
                    droneBehindChat = null;
                    animation.updatePlaybackRate(1); 
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
            const duration = Math.random() * 4 + 22; 

            setupDrone(drone, startX, startY, endX, endY, duration);
        }

        async function sendMessage() {
            const userInput = document.getElementById("user-input").value;
            if (!userInput) return;

            const chatBox = document.getElementById("chat-box");

            const userMessage = document.createElement("div");
            userMessage.classList.add("message", "user");
            userMessage.innerText = userInput;
            chatBox.appendChild(userMessage);
            
            document.getElementById("user-input").value = "";

            try {
                const response = await fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ message: userInput }),
                });

                const result = await response.json();

                if (response.ok) {
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

            chatBox.scrollTop = chatBox.scrollHeight;
        }

        document.getElementById("user-input").addEventListener("keypress", function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); 
                sendMessage();
            }
        });
    </script>
</body>
</html>

