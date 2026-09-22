# Laravel Server Setup

## Quick Start

1. **Start the server:**
   ```
   Double-click start-server.bat
   ```
   OR
   ```
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. **Keep the server window open** while using the app!

3. **Server URL:** http://192.168.1.103:8000

## Important Notes

- The server MUST be running for the app to work
- Keep the terminal window open - closing it stops the server
- The server needs to run on `0.0.0.0:8000` (not `127.0.0.1:8000`) to be accessible from mobile devices
- Make sure Windows Firewall allows port 8000
- Make sure your mobile device and computer are on the same WiFi network

## Troubleshooting

### "Connection test timeout" Error

1. **Check if server is running:**
   - Look for a terminal window with "Laravel development server started"
   - Or run: `netstat -ano | findstr :8000`

2. **If server is not running:**
   - Double-click `start-server.bat`
   - Keep the window open

3. **If server is running but app still can't connect:**
   - Check that server is on `0.0.0.0:8000` (network accessible), not `127.0.0.1:8000` (localhost only)
   - Run `check-and-start-server.bat` to verify and fix
   - Check Windows Firewall settings
   - Ensure device and server are on same network

4. **To restart the server:**
   - Stop the current server (close the window or press Ctrl+C)
   - Run `start-server.bat` again
   - OR use `restart-server.bat` to automatically restart

## Server Status

- ✅ **Running correctly:** `TCP    0.0.0.0:8000           0.0.0.0:0              LISTENING`
- ❌ **Not network accessible:** `TCP    127.0.0.1:8000         0.0.0.0:0              LISTENING`

Use `check-and-start-server.bat` to automatically check and fix server status.

