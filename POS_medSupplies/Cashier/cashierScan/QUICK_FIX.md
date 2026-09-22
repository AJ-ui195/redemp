# Quick Fix for Gradle Build Error

## The Problem
You're getting: `Error: -classpath requires class path specification`

This happens because `JAVA_HOME` is not set correctly or points to an invalid directory.

## Solution 1: Use the PowerShell Script (Easiest)

1. Open PowerShell in the project directory
2. Run:
   ```powershell
   .\set-java-home.ps1
   ```
3. Then run:
   ```powershell
   npx expo run:android
   ```

## Solution 2: Set JAVA_HOME Manually (Permanent Fix)

1. **Find your Java installation:**
   - Open File Explorer
   - Go to `C:\Program Files\Java\`
   - Look for a folder like `jdk-17.0.7` or `jdk-21.x.x`
   - Note the full path (e.g., `C:\Program Files\Java\jdk-17.0.7`)

2. **Set JAVA_HOME:**
   - Press `Win + X` → System → Advanced system settings
   - Click "Environment Variables"
   - Under "System variables", click "New"
   - Variable name: `JAVA_HOME`
   - Variable value: Your Java path (e.g., `C:\Program Files\Java\jdk-17.0.7`)
   - Click OK

3. **Add to PATH (if not already there):**
   - Edit the `Path` variable
   - Add: `%JAVA_HOME%\bin`
   - Click OK

4. **Restart your terminal/IDE** and try:
   ```bash
   npx expo run:android
   ```

## Solution 3: Use Expo Go Instead (No Build Required!)

If you just want to test the app, you don't need to build it:

```bash
npx expo start --lan
```

Then scan the QR code with Expo Go app on your phone. This avoids all the Gradle/Java issues!

## Solution 4: Use Android Studio

1. Open Android Studio
2. File → Open → Select `Cashier\cashierScan\android`
3. Let Gradle sync (it will handle Java automatically)
4. Click the Run button

## Verify Java is Working

Run these commands to check:
```powershell
java -version
javac -version
```

Both should show Java 17 or higher.
