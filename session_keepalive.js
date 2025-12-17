(function() {
    const sessionKeepAliveInterval = 10 * 60 * 1000;
    
    function keepSessionAlive() {
        fetch('keep_alive.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .catch(error => console.log('Session keep-alive ping sent'));
    }
    
    keepSessionAlive();
    
    setInterval(keepSessionAlive, sessionKeepAliveInterval);
    
    document.addEventListener('mousemove', function() {
        console.log('User activity detected - session active');
    }, { once: false, passive: true });
})();
