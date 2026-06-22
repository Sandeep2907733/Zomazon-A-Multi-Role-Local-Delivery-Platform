<?php
session_start();

// ✅ LOGIN GUARD — show message then redirect if not logged in
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access Denied</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<style>
    body {
        font-family: Poppins, sans-serif;
        background: #f5f7fb;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .box {
        background: white;
        padding: 40px 30px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        text-align: center;
        max-width: 320px;
    }
    .icon { font-size: 48px; margin-bottom: 10px; }
    h3 { margin: 0 0 8px; color: #111; }
    p { color: #666; font-size: 14px; margin: 0 0 20px; }
    .bar-wrap {
        background: #e5e7eb;
        border-radius: 99px;
        height: 6px;
        overflow: hidden;
    }
    .bar {
        height: 100%;
        width: 0%;
        background: #22c55e;
        border-radius: 99px;
        animation: fill 3s linear forwards;
    }
    .note { font-size: 12px; color: #aaa; margin-top: 12px; }
    @keyframes fill { to { width: 100%; } }
</style>
</head>
<body>
<div class="box">
    <div class="icon">🔒</div>
    <h3>Login Required</h3>
    <p>You need to be logged in to access Help & Support.</p>
    <div class="bar-wrap"><div class="bar"></div></div>
    <div class="note">Redirecting to login in 3 seconds...</div>
</div>
<script>
    setTimeout(() => { window.location.href = "registration/Login.php"; }, 3000);
</script>
</body>
</html>
<?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Help & Support</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
body{
font-family:Poppins;
background:#f5f7fb;
margin:0;
display:flex;
flex-direction:column;
height:100vh;
}

.header{
display:flex;
align-items:center;
padding:15px;
background:#22c55e;
color:white;
}

.back{cursor:pointer;margin-right:10px;}

.chat{
flex:1;
padding:15px;
overflow-y:auto;
display:flex;
flex-direction:column;
}

.msg{
padding:10px 15px;
margin:8px 0;
border-radius:15px;
max-width:75%;
font-size:14px;
}

.bot{
background:white;
box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.user{
background:#22c55e;
color:white;
margin-left:auto;
}

.options{
margin-top:10px;
}

.option-btn{
display:inline-block;
padding:8px 12px;
background:#e5e7eb;
border-radius:20px;
margin:5px;
cursor:pointer;
font-size:13px;
}

.option-btn:hover{
background:#22c55e;
color:white;
}

.input-box{
display:flex;
padding:10px;
background:white;
border-top:1px solid #ddd;
}

input{
flex:1;
padding:10px;
border-radius:10px;
border:1px solid #ddd;
}

button{
margin-left:10px;
padding:10px 15px;
background:#22c55e;
color:white;
border:none;
border-radius:10px;
cursor:pointer;
}

.timestamp{
font-size:10px;
opacity:0.6;
margin-top:4px;
}

</style>
</head>

<body>

<div class="header">
<span class="material-icons back" onclick="goBack()">arrow_back</span>
<h3>Help & Support</h3>
</div>

<div class="chat" id="chat"></div>

<div class="input-box">
<input id="input" placeholder="Type your issue...">
<button onclick="send()">Send</button>
</div>

<script>
const chat = document.getElementById("chat");

function goBack(){
    window.history.back();
}

function getTime(){
    let d = new Date();
    return d.getHours() + ":" + (d.getMinutes()<10?"0":"") + d.getMinutes();
}

function addMessage(text, type){
    let div = document.createElement("div");
    div.className = "msg " + type;
    div.innerHTML = text + `<div class="timestamp">${getTime()}</div>`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

function showOptions(){
    addMessage(`
    <div class="options">
        <span class="option-btn" onclick="handleOption('order')">📦 My Orders</span>
        <span class="option-btn" onclick="handleOption('delivery')">🚚 Delivery</span>
        <span class="option-btn" onclick="handleOption('payment')">💳 Payment</span>
        <span class="option-btn" onclick="handleOption('contact')">📞 Contact</span>
    </div>
    `, "bot");
}

function handleOption(type){
    addMessage(type, "user");
    replyLogic(type);
}

function replyLogic(text){
    text = text.toLowerCase();
    let reply = "";

    if(text.includes("order")){
        reply = `You can check and track your orders anytime 📦<br><br>
        <button onclick="openPage('orders.php')">Go to Orders</button>`;
    }
    else if(text.includes("cancel")){
        reply = "To cancel an order, go to Orders → Select product → Cancel.";
    }
    else if(text.includes("delivery")){
        reply = "Your order usually arrives within 2–3 days 🚚<br>You can track it from the Orders section.";
    }
    else if(text.includes("payment")){
        reply = "We support UPI, Debit/Credit Cards, Net Banking and Cash on Delivery 💳";
    }
    else if(text.includes("contact")){
        reply = "📞 Call: 9876543210<br>📧 Email: support@zomazon.com";
    }
    else{
        reply = "I didn't understand that. Please choose an option below 👇";
    }

    // typing effect
    let typing = document.createElement("div");
    typing.className = "msg bot";
    typing.innerText = "Typing...";
    chat.appendChild(typing);
    chat.scrollTop = chat.scrollHeight;

    setTimeout(()=>{
        typing.remove();
        addMessage(reply, "bot");
        showOptions();
    }, 700);
}

function openPage(page){
    window.location.href = page;
}

function send(){
    let input = document.getElementById("input");
    let val = input.value.trim();
    if(!val) return;

    addMessage(val, "user");
    replyLogic(val);
    input.value = "";
}

// Enter key support
document.getElementById("input").addEventListener("keypress", function(e){
    if(e.key === "Enter") send();
});

// ✅ FRESH START every time the page loads — no localStorage
addMessage("Hi 👋 How can I help you?", "bot");
showOptions();

</script>

</body>
</html>