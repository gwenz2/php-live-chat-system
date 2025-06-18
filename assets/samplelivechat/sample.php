<?php
// Save message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = htmlspecialchars(trim($_POST['message']));
    if ($msg) {
        $line = date('H:i') . " User: $msg\n";
        file_put_contents('messages.txt', $line, FILE_APPEND | LOCK_EX);
    }
    exit;
}

// Fetch messages
if (isset($_GET['fetch'])) {
    if (file_exists('messages.txt')) {
        echo nl2br(file_get_contents('messages.txt'));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mini Live Chat</title>
    <style>
        body { font-family: sans-serif; background: #f9f9f9; }
        #msgs { background: #fff; border: 1px solid #ccc; height: 200px; overflow-y: auto; padding: 10px; margin-bottom: 10px; }
        #msgform { display: flex; }
        #msgform input[type="text"] { flex: 1; padding: 5px; }
        #msgform button { padding: 5px 10px; }
    </style>
</head>
<body>
    <h3>Mini Live Chat</h3>
    <div id="msgs"></div>
    <form id="msgform" autocomplete="off">
        <input type="text" id="msg" placeholder="Type message..." maxlength="200" required>
        <button type="submit">Send</button>
    </form>

    
    <script>
        const msgs = document.getElementById('msgs');
        const form = document.getElementById('msgform');
        const msgInput = document.getElementById('msg');

        function fetchMsgs() {
            fetch('?fetch=1')
                .then(r => r.text())
                .then(t => {
                    msgs.innerHTML = t;
                    msgs.scrollTop = msgs.scrollHeight;
                });
        }
        fetchMsgs();

        form.onsubmit = function(e) {
            e.preventDefault();
            const msg = msgInput.value.trim();
            if (!msg) return;
            const fd = new FormData();
            fd.append('message', msg);
            fetch('', { method: 'POST', body: fd })
                .then(() => {
                    msgInput.value = '';
                    fetchMsgs();
                });
        };

        // Optional: auto-refresh every 2 seconds
        setInterval(fetchMsgs, 2000);
    </script>
</body>
</html>