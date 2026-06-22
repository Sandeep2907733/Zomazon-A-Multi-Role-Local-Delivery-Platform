<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingredients'])) {

    header('Content-Type: application/json');

    $ingredients = trim($_POST['ingredients']);
    $apiKey = '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';
    $url = "https://api.groq.com/openai/v1/chat/completions";

    $data = json_encode([
        'model' => 'llama-3.1-8b-instant',
        'messages' => [
            ['role' => 'user', 'content' => "Suggest a detailed recipe using these ingredients: $ingredients"]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $recipe = $result['choices'][0]['message']['content']
              ?? ($result['error']['message'] ?? 'Sorry, could not get a recipe. Try again!');

    echo json_encode(['reply' => $recipe]);
    exit;

}
?>
<!DOCTYPE html>
<html>
<head>

<title>AI Recipe Suggestor</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>

body{
margin:0;
font-family:'Poppins';
background:#f5f7fb;
display:flex;
flex-direction:column;
height:100vh;
}

.header{
display:flex;
align-items:center;
padding:15px;
background:white;
box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.back-btn{
cursor:pointer;
margin-right:10px;
}

.chat-container{
flex:1;
padding:20px;
overflow-y:auto;
}

.message{
max-width:70%;
padding:12px;
margin-bottom:10px;
border-radius:12px;
}

.user{
background:#22c55e;
color:white;
margin-left:auto;
}

.bot{
background:white;
box-shadow:0 3px 10px rgba(0,0,0,0.08);
white-space:pre-wrap;
}

.input-area{
display:flex;
padding:10px;
background:white;
box-shadow:0 -2px 10px rgba(0,0,0,0.08);
align-items:center;
}

.input-area input{
flex:1;
padding:10px;
border-radius:20px;
border:1px solid #ddd;
outline:none;
font-family:'Poppins';
}

.input-area button{
margin-left:10px;
padding:10px;
border:none;
background:#22c55e;
color:white;
border-radius:50%;
cursor:pointer;
display:flex;
align-items:center;
justify-content:center;
}

</style>

</head>

<body>

<div class="header">
    <span class="material-icons back-btn" onclick="goBack()">arrow_back</span>
    <h3>AI Recipe Assistant</h3>
</div>

<div class="chat-container" id="chatBox">
    <div class="message bot">
        Hi 👋 Tell me your ingredients and I'll suggest a recipe!
    </div>
</div>

<div class="input-area">
    <input type="text" id="userInput" placeholder="Enter ingredients..." onkeydown="if(event.key==='Enter') sendMessage()">
    <button onclick="sendMessage()">
        <span class="material-icons">send</span>
    </button>
</div>

<script>

function goBack(){
    window.history.back();
}

function sendMessage(){

    let input = document.getElementById("userInput").value.trim();
    if(input === "") return;

    let chatBox = document.getElementById("chatBox");

    let userMsg = document.createElement("div");
    userMsg.className = "message user";
    userMsg.innerText = input;
    chatBox.appendChild(userMsg);

    document.getElementById("userInput").value = "";

    let botMsg = document.createElement("div");
    botMsg.className = "message bot";
    botMsg.innerText = "Thinking...";
    chatBox.appendChild(botMsg);

    chatBox.scrollTop = chatBox.scrollHeight;

    let formData = new FormData();
    formData.append("ingredients", input);

    fetch("recipe.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        botMsg.innerText = data.reply;
        chatBox.scrollTop = chatBox.scrollHeight;
    })
    .catch(() => {
        botMsg.innerText = "Something went wrong. Please try again.";
        chatBox.scrollTop = chatBox.scrollHeight;
    });

}

</script>

</body>
</html>